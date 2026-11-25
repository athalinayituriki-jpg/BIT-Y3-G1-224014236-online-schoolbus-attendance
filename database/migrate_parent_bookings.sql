USE school_attendance;

ALTER TABLE users ADD COLUMN phone_number VARCHAR(30) NULL AFTER email;
ALTER TABLE schedule_entries ADD COLUMN bus_id INT UNSIGNED NULL AFTER id;
ALTER TABLE schedule_entries ADD CONSTRAINT fk_schedule_bus FOREIGN KEY (bus_id) REFERENCES buses(id) ON UPDATE CASCADE ON DELETE SET NULL;

CREATE TABLE ride_bookings (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  parent_id INT UNSIGNED NOT NULL,
  student_id INT UNSIGNED NOT NULL,
  bus_id INT UNSIGNED NOT NULL,
  schedule_entry_id INT UNSIGNED NOT NULL,
  travel_date DATE NOT NULL,
  seat_number SMALLINT UNSIGNED NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_booking_parent FOREIGN KEY (parent_id) REFERENCES users(id) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_booking_student FOREIGN KEY (student_id) REFERENCES students(id) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_booking_bus FOREIGN KEY (bus_id) REFERENCES buses(id) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_booking_schedule FOREIGN KEY (schedule_entry_id) REFERENCES schedule_entries(id) ON UPDATE CASCADE ON DELETE RESTRICT,
  UNIQUE KEY uq_booking_bus_trip_seat (bus_id,schedule_entry_id,travel_date,seat_number),
  UNIQUE KEY uq_booking_student_trip (student_id,schedule_entry_id,travel_date),
  INDEX idx_booking_parent_date (parent_id,travel_date)
) ENGINE=InnoDB;

-- Assign existing weekday trips to the two demo buses. Adjust these in the admin timetable as needed.
UPDATE schedule_entries SET bus_id=(SELECT id FROM buses WHERE bus_number='BUS-01') WHERE day_of_week IN ('Monday','Wednesday','Friday');
SET @secondary_bus=(SELECT id FROM buses ORDER BY id LIMIT 1 OFFSET 1);
UPDATE schedule_entries SET bus_id=@secondary_bus WHERE day_of_week IN ('Tuesday','Thursday') AND @secondary_bus IS NOT NULL;