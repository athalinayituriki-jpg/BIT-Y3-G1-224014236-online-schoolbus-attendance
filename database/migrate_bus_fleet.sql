USE school_attendance;
ALTER TABLE buses ADD COLUMN route_id INT UNSIGNED NULL AFTER driver_id, ADD COLUMN photo_filename VARCHAR(80) NULL AFTER route_id, ADD CONSTRAINT fk_bus_route FOREIGN KEY (route_id) REFERENCES schedule_entries(id) ON UPDATE CASCADE ON DELETE SET NULL;
