USE school_attendance;

ALTER TABLE attendance_logs MODIFY status ENUM('boarded','alighted','absent') NOT NULL;
ALTER TABLE schedule_entries ADD COLUMN evening_departure_time TIME NULL AFTER arrival_time;
ALTER TABLE schedule_entries ADD COLUMN evening_arrival_time TIME NULL AFTER evening_departure_time;
ALTER TABLE ride_bookings ADD COLUMN trip_period ENUM('morning','evening') NOT NULL DEFAULT 'morning' AFTER travel_date;
ALTER TABLE ride_bookings DROP INDEX uq_booking_bus_trip_seat,
  ADD UNIQUE KEY uq_booking_bus_trip_seat (bus_id,schedule_entry_id,travel_date,trip_period,seat_number);
ALTER TABLE ride_bookings DROP INDEX uq_booking_student_trip,
  ADD UNIQUE KEY uq_booking_student_trip (student_id,schedule_entry_id,travel_date,trip_period);

UPDATE schedule_entries SET evening_departure_time='14:45',evening_arrival_time='15:55' WHERE route_name='Nyabugogo to Nyarutarama';
UPDATE schedule_entries SET evening_departure_time='15:00',evening_arrival_time='15:50' WHERE route_name='Kimironko to Nyarutarama';
UPDATE schedule_entries SET evening_departure_time='14:50',evening_arrival_time='16:10' WHERE route_name='Kicukiro to Nyarutarama';
UPDATE schedule_entries SET evening_departure_time='14:45',evening_arrival_time='16:00' WHERE route_name='Nyamirambo to Nyarutarama';
UPDATE schedule_entries SET evening_departure_time='14:50',evening_arrival_time='15:55' WHERE route_name='Kigali City Centre to Nyarutarama';