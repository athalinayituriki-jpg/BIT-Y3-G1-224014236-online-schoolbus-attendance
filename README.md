# Online School Bus Attendance System

A vanilla PHP 8+ / MySQL project for managing school buses, students and bus attendance. It uses HTML templates rendered by PHP, responsive CSS3, browser JavaScript, PHP sessions, PDO prepared statements and a JSON live-search endpoint.

## Technology rules
- HTML5, CSS3 and responsive layout
- Vanilla JavaScript DOM/events, validation and Fetch API
- PHP with sessions, includes, controllers and views
- MySQL with primary/foreign keys and normalized tables
- PDO prepared statements; `password_hash()` / `password_verify()`
- Output escaping through `htmlspecialchars()` and CSRF tokens on forms
- No Laravel, React, Vue or Bootstrap

## Requirements
- PHP 8.1+ with PDO MySQL (mbstring is optional)
- MySQL 8+ or compatible MariaDB
- XAMPP/WAMP/MAMP or a PHP web server

## Setup (XAMPP)
1. Copy the `online_school_bus_attendance` folder into `C:\xampp\htdocs\`.
2. Start Apache and MySQL in the XAMPP Control Panel.
3. Open phpMyAdmin and import `database/schema.sql`.
4. Optionally import `database/seed.sql` for demonstration data. For an existing installation, import `database/migrate_parent_bookings.sql` and then `database/migrate_evening_attendance.sql`, once each, to add contacts, assigned buses, morning/evening bookings and absent attendance.
5. Check `config/config.php`. Default database: `school_attendance`; host `localhost`; user `root`; password empty. Change these values if your MySQL credentials differ.
6. Open `http://localhost/online_school_bus_attendance/public/`.
7. Register an account. The first registered account becomes an administrator; subsequent public registrations are parent accounts. Admins can create driver and additional admin accounts from Users.

## Demo login
If you imported `seed.sql`:
- Admin: `admin@example.com`
- Driver: `driver1@example.com`
- Parent accounts and passwords are listed below and in the Users page (admin).
- Admin/demo driver password: `Password123!`
- Parent accounts (email is username): `patrick.bikorimana@school.local` / `Patrick@Bus2026`; `fulgence.kayiranga@school.local` / `Fulgence@Bus2026`; `rose.mukamanzi@school.local` / `Rose@Bus2026`.

Seeded parent accounts are linked to 10 students: 4 for BIKORIMANA PATRICK, 3 for KAYIRANGA FULGENCE and 3 for MUKAMANZI ROSE. Parent passwords are stored using `password_hash()`. Change the suggested passwords before using the application with real families. The seed file is intended to be imported once into a fresh schema.

## Routes
All routes use `public/index.php?page=...`. Drivers can record students as boarded, alighted or absent. Server checks limit them to assigned buses.

| Route | Purpose | Access |
|---|---|---|
| `login` / `register` / `logout` | Authentication | Public / signed-in |
| `buses`, `bus-create`, `bus-edit`, `bus-save`, `bus-delete` | Full bus CRUD | Admin |
| `students`, `student-form`, `student-save`, `student-delete` | Full student CRUD | Admin; parents/drivers can view permitted records |
| `attendance`, `attendance-create`, `attendance-mark` | Read/create attendance | Admin/driver; parents view own children |
| `bookings`, `booking-form`, `booking-save`, `booking-cancel` | Parent seat reservations and availability | Parent account; own registered children only |
| `schedules`, `schedule-form`, `schedule-save`, `schedule-delete` | Weekly route timetable | Signed-in view; admin manage |
| `users`, `user-save`, `user-toggle` | User administration and parent account details | Admin |
| `api-students&q=...` | JSON live search | Admin/driver |

## Database design
The four core entities are `users`, `buses`, `students`, and `attendance_logs`. The requested weekday timetable is an additional `schedule_entries` table that stores route locations, travel times and assigned buses. `ride_bookings` reserves numbered seats by child, morning/evening trip and date.
- `users` stores hashed passwords and roles.
- `buses.driver_id` references `users.id`.
- `students.bus_id` references `buses.id`; `students.parent_id` references `users.id`.
- `attendance_logs.student_id` references `students.id`; `marked_by` references `users.id`.
- `ride_bookings` references the parent, child, bus and assigned timetable trip; unique keys prevent double booking a seat or booking a child twice on one trip.
- Drivers add phone contacts in My profile; parents see the driver name, photo, email, phone and assigned bus with their own child.
- Repeating user, bus and student details are stored once and referenced by foreign keys (3NF-oriented design).

## Parent bookings and driver details
Parents see only their linked children, related attendance, assigned bus routes and their own bookings. Home and the timetable show only the current weekday by default, with Saturday and Sunday clearly marked CLOSED in red. Scheduled morning and evening return times are displayed as fixed times without a live countdown. Admins can open Full week in the timetable to review and manage weekday routes. Parents choose a weekday and morning/evening trip, see available and occupied seat numbers, and reserve a seat before departure. The server checks parent ownership, bus assignment, route weekday, departure time, capacity and seat uniqueness.
## Security notes
- PDO prepared statements are used for database inputs.
- Passwords are stored using PHP's `password_hash()` and checked with `password_verify()`.
- Forms include a session CSRF token.
- User-visible output is escaped with `htmlspecialchars()`.
- Validation is performed on the server; role checks protect privileged routes.
- Driver attendance is restricted to students on the driver's assigned bus.
- Configure HTTPS, secure cookies, backups and stronger operational policies before production use. Do not use real children's data in a public demo.

## Test evidence / test plan
See `TEST_PLAN.md` for the test cases and recorded results. Save screenshot evidence in the `test screenshots/` folder before submission.

| # | Test | Steps | Expected result |
|---|---|---|---|
| 1 | First registration | Register a valid first account | Account is created with admin role |
| 2 | Parent registration | Register another valid account | Account is created with parent role |
| 3 | Login success | Login with valid credentials | Home opens and session is created |
| 4 | Login failure | Use incorrect password | Generic invalid-credentials message |
| 5 | Password hashing | Inspect `users.password_hash` in DB | Stored value is a hash, not plain password |
| 6 | Create bus | Admin adds unique bus and capacity | Bus appears in bus list |
| 7 | Duplicate bus | Add a bus with an existing plate/number | Database rejects duplicate; no duplicate row |
| 8 | Edit/delete bus | Admin updates bus, then delete unused bus | Changes persist; linked bus deletion is restricted |
| 9 | Create student | Admin enters valid student, bus and parent | Student appears in list |
| 10 | Invalid student | Submit missing name or invalid parent/bus | Server rejects invalid data |
| 11 | Student CRUD | Admin edits and deletes a test student | Updated data appears; deleted row is removed |
| 12 | Attendance create | Driver marks assigned student boarded | Attendance row is saved |
| 13 | Driver authorization | Driver tries to mark a student on another bus | Request is denied |
| 14 | Parent privacy | Parent opens students/attendance | Only their linked children are shown |
| 15 | JSON search | Type at least two letters in student search | Fetch receives JSON and shows matching students |
| 16 | CSRF failure | Submit a form without/with invalid token | Request is rejected with 419 |
| 17 | XSS output | Save a name containing HTML markup | Markup is displayed as escaped text |
| 18 | Role protection | Parent manually opens admin route | Access denied |

## Demo plan (2â€“3 minutes)
1. Show login and role-based home page (20 seconds).
2. As admin, create a bus and a parent/driver account (35 seconds).
3. Create a student and show edit/delete options (35 seconds).
4. Log in as driver and record boarded/alighted attendance (30 seconds).
5. Log in as parent and show only their child's attendance (20 seconds).
6. Show the live JSON search and briefly point out the schema and test plan (20 seconds).

## GitHub submission
Create a repository named `BIT-Y3-G1-<REGNO>-SchoolBusAttendance`, commit this folder, and push it to GitHub. Add `bemmy2005` as a collaborator with the repository's collaborator settings. Submit the repository URL and a short project summary on the LMS. The repository push and collaborator invitation must be completed by the student in their own GitHub account.


## Database diagnostic
From a terminal opened in the project folder, run:

```bash
php tools/check_database.php
```

It verifies the connection and the required table columns. It does not modify records.

## Typography
The interface uses Poppins with a sans-serif fallback. The font is loaded from Google Fonts; the rest of the interface uses vanilla HTML, CSS, JavaScript, PHP and MySQL.


## User profiles and administration
- Every signed-in user can open **My profile** to update their name/email and change their password after verifying the current password.
- The first public registration in a completely empty database becomes the administrator. Later public registrations become parents.
- Administrators can view all accounts, create accounts, edit names/emails/roles, reset passwords, activate/deactivate accounts, and delete accounts. The signed-in administrator cannot delete or deactivate their own account; the last active administrator cannot be deleted.
- `database/seed.sql` provides demo users, two buses, ten sample students and sample attendance records. Import it only once. When using this seed, sign in with `admin@example.com` and password `Password123!`.
- To make your own account the first administrator, import `schema.sql` only (do not import `seed.sql`), then register your account first.

## Weekly timetable and route maps
The signed-in home page shows the timetable below the attendance overview. Administrators can add, edit and delete routes by day, including departure/arrival times, start location, parking/pickup point, school destination and road notes. The timetable groups trips by day and shows location and parking markers. Each complete route has a Google Maps driving-directions link. It uses official Google Maps URLs and does not require a Maps API key. Existing routes need their start, parking and destination locations filled in by an administrator before their map link can be opened.

## Profile photos
Signed-in users can upload a JPG, PNG or WebP profile picture from **My profile**, up to 2 MB. Image type and dimensions are checked, the image is stored using a randomized filename, and user cards display it when available.

### Interactive Google Maps preview
Google Maps directions links work without an API key. To embed the interactive driving map inside each route card, create a Google Maps Embed API key, restrict it to Maps Embed API and this site's HTTP referrer, then copy `config/maps.local.example.php` to `config/maps.local.php` and return the key from that file. Google requires a key for every Maps Embed API request; its current usage page lists Maps Embed as no-charge, but a billing-enabled Cloud project is required.
