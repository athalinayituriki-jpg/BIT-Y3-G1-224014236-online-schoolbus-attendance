USE school_attendance;
-- Demo accounts: Password123! (hash generated with PHP password_hash).
-- Import this seed only once into a freshly-created schema.
INSERT INTO users(full_name,email,password_hash,role,is_active) VALUES
('System Administrator','admin@example.com','$2y$12$xWa3U7cp7cTjpAhxiRdMLOjLO73yeaAWITFNHBZ.e8LqyuNH3Ek46','admin',1),
('Demo Driver One','driver1@example.com','$2y$12$xWa3U7cp7cTjpAhxiRdMLOjLO73yeaAWITFNHBZ.e8LqyuNH3Ek46','driver',1),
('Demo Driver Two','driver2@example.com','$2y$12$xWa3U7cp7cTjpAhxiRdMLOjLO73yeaAWITFNHBZ.e8LqyuNH3Ek46','driver',1),
('BIKORIMANA PATRICK','patrick.bikorimana@school.local','$2y$10$wpj1AV2R9uIiYbZyLyzPHeEi96KyNmEPEpoLjVN3g.oQJ7XVo1XiO','parent',1),
('KAYIRANGA FULGENCE','fulgence.kayiranga@school.local','$2y$10$I2w4xVdsDY7wA/2YeQrc9ubDXMimPPgaQB3TDoxF7bXhiSyT2IV/y','parent',1),
('MUKAMANZI ROSE','rose.mukamanzi@school.local','$2y$10$OIMNKhim2NjSGn.9lfM7IuSpYK91FKMnYUVhuXmplex/JwWHVJ4zm','parent',1);

INSERT INTO buses(bus_number,plate_number,capacity,driver_id)
SELECT 'BUS-01','RAB123A',30,id FROM users WHERE email='driver1@example.com';
INSERT INTO buses(bus_number,plate_number,capacity,driver_id)
SELECT 'BUS-02','RAC456B',25,id FROM users WHERE email='driver2@example.com';

-- Ten students split among the three named parent accounts (4, 3 and 3).
INSERT INTO students(full_name,student_code,grade,bus_id,parent_id) VALUES
('Aline Uwase','STU-001','Primary 1',1,4),
('Eric Niyonzima','STU-002','Primary 2',1,4),
('Diane Mukamana','STU-003','Primary 3',1,4),
('Samuel Habimana','STU-004','Primary 4',1,4),
('Grace Ingabire','STU-005','Primary 5',1,5),
('Patrick Mugisha','STU-006','Primary 1',2,5),
('Alice Uwamahoro','STU-007','Primary 2',2,5),
('David Tuyisenge','STU-008','Primary 3',2,6),
('Peace Mutoni','STU-009','Primary 4',2,6),
('Kevin Ishimwe','STU-010','Primary 5',2,6);

INSERT INTO attendance_logs(student_id,marked_by,status,event_time) VALUES
(1,2,'boarded',NOW()-INTERVAL 3 HOUR),
(2,2,'boarded',NOW()-INTERVAL 3 HOUR),
(3,2,'boarded',NOW()-INTERVAL 2 HOUR),
(6,3,'boarded',NOW()-INTERVAL 3 HOUR),
(7,3,'boarded',NOW()-INTERVAL 2 HOUR),
(1,2,'alighted',NOW()-INTERVAL 30 MINUTE),
(6,3,'alighted',NOW()-INTERVAL 25 MINUTE);

-- Kigali weekday routes assigned to a bus so parents can reserve numbered seats.
INSERT INTO schedule_entries(bus_id,day_of_week,route_name,origin_location,parking_location,destination_location,route_description,departure_time,arrival_time,evening_departure_time,evening_arrival_time,created_by)
SELECT (SELECT id FROM buses WHERE bus_number='BUS-01'),'Monday','Nyabugogo to Nyarutarama','Nyabugogo Taxi Park, Kigali, Rwanda','Remera Taxi Park, Kigali, Rwanda','Green Hills Academy, KG 270 St, Nyarutarama, Kigali, Rwanda','Weekday school route with a designated pickup point and scheduled arrival.','06:15','07:25','14:45','15:55',id FROM users WHERE email='admin@example.com'
UNION ALL SELECT (SELECT id FROM buses WHERE bus_number='BUS-02'),'Tuesday','Kimironko to Nyarutarama','Kimironko Taxi Park, Kigali, Rwanda','Remera Taxi Park, Kigali, Rwanda','Green Hills Academy, KG 270 St, Nyarutarama, Kigali, Rwanda','Weekday school route with a designated pickup point and scheduled arrival.','06:20','07:15','15:00','15:50',id FROM users WHERE email='admin@example.com'
UNION ALL SELECT (SELECT id FROM buses WHERE bus_number='BUS-01'),'Wednesday','Kicukiro to Nyarutarama','Kicukiro Centre, Kigali, Rwanda','Kigali Convention Centre, Kimihurura, Kigali, Rwanda','Green Hills Academy, KG 270 St, Nyarutarama, Kigali, Rwanda','Weekday school route with a designated pickup point and scheduled arrival.','06:10','07:30','14:50','16:10',id FROM users WHERE email='admin@example.com'
UNION ALL SELECT (SELECT id FROM buses WHERE bus_number='BUS-02'),'Thursday','Nyamirambo to Nyarutarama','Nyamirambo, Kigali, Rwanda','Nyabugogo Taxi Park, Kigali, Rwanda','Green Hills Academy, KG 270 St, Nyarutarama, Kigali, Rwanda','Weekday school route with a designated pickup point and scheduled arrival.','06:05','07:30','14:45','16:00',id FROM users WHERE email='admin@example.com'
UNION ALL SELECT (SELECT id FROM buses WHERE bus_number='BUS-01'),'Friday','Kigali City Centre to Nyarutarama','Kigali City Tower, KN 2 St, Kigali, Rwanda','Kigali Convention Centre, Kimihurura, Kigali, Rwanda','Green Hills Academy, KG 270 St, Nyarutarama, Kigali, Rwanda','Weekday school route with a designated pickup point and scheduled arrival.','06:15','07:30','14:50','15:55',id FROM users WHERE email='admin@example.com';