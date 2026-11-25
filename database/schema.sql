CREATE DATABASE IF NOT EXISTS school_attendance CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE school_attendance;

CREATE TABLE users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  phone_number VARCHAR(30) NULL,
  avatar_filename VARCHAR(80) NULL,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('admin','driver','parent') NOT NULL DEFAULT 'parent',
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE buses (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  bus_number VARCHAR(30) NOT NULL UNIQUE,
  plate_number VARCHAR(20) NOT NULL UNIQUE,
  capacity SMALLINT UNSIGNED NOT NULL,
  driver_id INT UNSIGNED NULL,
  route_id INT UNSIGNED NULL,
  photo_filename VARCHAR(80) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_bus_driver FOREIGN KEY (driver_id) REFERENCES users(id) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE students (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(120) NOT NULL,
  student_code VARCHAR(30) NOT NULL UNIQUE,
  grade VARCHAR(30) NOT NULL,
  bus_id INT UNSIGNED NOT NULL,
  parent_id INT UNSIGNED NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_student_bus FOREIGN KEY (bus_id) REFERENCES buses(id) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_student_parent FOREIGN KEY (parent_id) REFERENCES users(id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE attendance_logs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  student_id INT UNSIGNED NOT NULL,
  marked_by INT UNSIGNED NOT NULL,
  status ENUM('boarded','alighted','absent') NOT NULL,
  event_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_attendance_student FOREIGN KEY (student_id) REFERENCES students(id) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_attendance_user FOREIGN KEY (marked_by) REFERENCES users(id) ON UPDATE CASCADE ON DELETE RESTRICT,
  INDEX idx_attendance_student_time (student_id,event_time),
  INDEX idx_attendance_time (event_time)
) ENGINE=InnoDB;

CREATE TABLE schedule_entries (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  bus_id INT UNSIGNED NULL,
  day_of_week ENUM('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday') NOT NULL,
  route_name VARCHAR(100) NOT NULL,
  origin_location VARCHAR(180) NOT NULL,
  parking_location VARCHAR(180) NOT NULL,
  destination_location VARCHAR(180) NOT NULL,
  route_description VARCHAR(500) NOT NULL,
  departure_time TIME NOT NULL,
  arrival_time TIME NOT NULL,
  evening_departure_time TIME NULL,
  evening_arrival_time TIME NULL,
  created_by INT UNSIGNED NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_schedule_bus FOREIGN KEY (bus_id) REFERENCES buses(id) ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT fk_schedule_creator FOREIGN KEY (created_by) REFERENCES users(id) ON UPDATE CASCADE ON DELETE RESTRICT,
  INDEX idx_schedule_day_time (day_of_week,departure_time)
) ENGINE=InnoDB;
ALTER TABLE buses ADD CONSTRAINT fk_bus_route FOREIGN KEY (route_id) REFERENCES schedule_entries(id) ON UPDATE CASCADE ON DELETE SET NULL;

CREATE TABLE ride_bookings (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  parent_id INT UNSIGNED NOT NULL,
  student_id INT UNSIGNED NOT NULL,
  bus_id INT UNSIGNED NOT NULL,
  schedule_entry_id INT UNSIGNED NOT NULL,
  travel_date DATE NOT NULL,
  trip_period ENUM('morning','evening') NOT NULL DEFAULT 'morning',
  seat_number SMALLINT UNSIGNED NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_booking_parent FOREIGN KEY (parent_id) REFERENCES users(id) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_booking_student FOREIGN KEY (student_id) REFERENCES students(id) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_booking_bus FOREIGN KEY (bus_id) REFERENCES buses(id) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_booking_schedule FOREIGN KEY (schedule_entry_id) REFERENCES schedule_entries(id) ON UPDATE CASCADE ON DELETE RESTRICT,
  UNIQUE KEY uq_booking_bus_trip_seat (bus_id,schedule_entry_id,travel_date,trip_period,seat_number),
  UNIQUE KEY uq_booking_student_trip (student_id,schedule_entry_id,travel_date,trip_period),
  INDEX idx_booking_parent_date (parent_id,travel_date)
) ENGINE=InnoDB;