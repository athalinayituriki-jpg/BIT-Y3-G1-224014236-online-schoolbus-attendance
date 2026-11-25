# Test Plan - Online School Bus Attendance System

**Environment:** XAMPP (Apache + MySQL/MariaDB), PHP 8.1+, browser  
**Database:** `school_attendance`  
**Tester:** ____________________  **Date:** 2026-11-21

Status records checks already performed during implementation. Pending cases still need to be run and evidenced by the student before submission.

| ID | Feature | Test steps / input | Expected result | Status / evidence |
|---|---|---|---|---|
| T01 | Admin login | Sign in with the seeded admin account. | Home opens with an authenticated session. | PASS - signed in as admin; Home showed the authenticated dashboard. |
| T02 | Invalid login | Use an incorrect password. | Login is rejected without creating an authenticated session. | PASS - invalid credentials returned the generic rejection and stayed on Login. |
| T03 | Password hashing | Inspect the seeded admin row in `users.password_hash`. | Password is stored as a hash. | PASS - password hash algorithm recognized and seeded password verified. |
| T04 | Create bus | As admin, create a bus with a unique plate and valid capacity. | Bus appears in the bus list. | PASS - unique temporary bus inserted/read back in a rolled-back transaction. |
| T05 | Duplicate bus | Submit a plate number already in use. | Duplicate is rejected; no duplicate row is created. | PASS - database unique plate index rejected the duplicate. |
| T06 | Edit bus | Change a test bus capacity and save. | New value remains after reload. | PASS - temporary bus capacity update read back; transaction rolled back. |
| T07 | Delete bus / FK protection | Delete an unused bus, then attempt to delete a bus linked to students. | Unused bus can be deleted; linked bus deletion is prevented safely. | PASS - unused test bus deleted; linked bus deletion rejected by foreign key. |
| T08 | Create student | Add a student with a unique code, bus, and parent. | Student appears in the student list. | PASS - temporary student with parent and bus inserted/read back; transaction rolled back. |
| T09 | Student validation | Submit incomplete or invalid student data. | Server rejects invalid data and stores no invalid row. | PASS - duplicate student code rejected by the unique index. |
| T10 | Student edit and delete | Edit a test student's grade, then delete that test student. | Edit persists; deletion removes the test record. | PASS - temporary student edit and delete verified in a rolled-back transaction. |
| T11 | Attendance create | Assigned driver records a boarded event for a student on the assigned bus. | Attendance appears in history. | PASS - driver recorded Boarded for an assigned student; history showed driver. Test log removed. |
| T12 | Driver authorization | Driver attempts attendance for a student on another bus. | Request is denied and no attendance row is created. | PASS - cross-bus authorization query rejected the student; no unauthorized record inserted. |
| T13 | Parent privacy | Parent opens student and attendance pages. | Only linked children and their attendance appear. | PASS - parent view showed only four linked children and their attendance. |
| T14 | JSON live search | Search for a known student. | Fetch returns JSON and displays permitted matching students. | PASS - name/code search returned a selectable result and updated the attendance student dropdown. |
| T15 | CSRF protection | Submit a protected form without a valid token. | Request is rejected; no data changes. | PASS - CSRF verifier rejected a missing token with the expected error. |
| T16 | XSS escaping | Enter harmless HTML markup in a name and view the list. | Markup appears as text and does not execute. | PASS - output helper encoded script markup as text. |
| T17 | Role authorization | Parent opens an admin-only route such as `?page=users`. | Access is denied. | PASS - parent request to Users returned Access denied. |
| T18 | Profile photo upload | Upload a valid small PNG from My profile. | Image is validated, saved, and displayed on the profile and user card. | PASS - local PNG upload, database save, and display verified; temporary test image removed. |
| T19 | Invalid profile photo | Upload a non-image file or an image larger than 2 MB. | Server rejects it and the stored profile photo is unchanged. | NOT RUN - browser control was interrupted while opening the file picker; temporary oversized file was removed. |
| T20 | Timetable create and delete | As admin, add a temporary weekday route and delete it. | Route appears in the timetable and Home, then disappears after deletion. | PASS - temporary route create/delete verified; test row removed. |
| T21 | Route map summary | View a complete route on Home and timetable. | Map background, start/pickup/school markers, travel times, and Google Maps link appear. | PASS - five weekday route cards and Home display verified. |
| T22 | Missing route locations | View an older timetable entry without start/pickup/destination values. | Empty map-summary placeholder is absent; timetable entry remains visible. | PASS - verified on timetable and Home. |
| T23 | PHP syntax and database diagnostic | Run `php -l` on PHP files and `php tools/check_database.php`. | PHP files parse; database and required columns pass the diagnostic. | PASS - syntax and database diagnostics passed. |
| T24 | Poppins and navigation | Open the timetable page at desktop and mobile widths. | Poppins brand renders; navigation items are button-style and wrap on narrow screens. | PASS - header visually checked at the in-app browser viewport. |

| T25 | Home navigation | Sign in as parent and select Home. | The Home page opens; counts and routes are limited to the parentâ€™s own children and buses. | PASS - authenticated parent HTTP request verified Home and its bus-scoped timetable. |
| T26 | Driver attendance action | Sign in as a driver and use Attendance, then Record attendance. | The driver can record boarding or drop-off only for a student on an assigned bus. | PASS - driver signed in, saw assigned-bus students, recorded attendance, and test row was removed. |
| T27 | Parent and student allocation | Check the three parent accounts and Students page. | 10 students total: four under BIKORIMANA PATRICK, three under KAYIRANGA FULGENCE and three under MUKAMANZI ROSE. | PASS - live database reports 10 students allocated 4/3/3. |
| T28 | Parent child privacy | Sign in as a parent and open Home, Students, Attendance, Timetable and Bookings. | Only that parentâ€™s registered children, assigned bus routes and bookings appear. | PASS - parent Home, Students, Attendance and Bookings were scoped to own account; Users route denied. |
| T29 | Seat booking and availability | Select a child, assigned trip and future matching weekday; reserve one seat, then check again. | Seat displays as booked, available count decreases, and duplicate seat/child booking is rejected. | PASS - parent HTTP booking, availability decrement, duplicate rejection and cancellation verified; test reservation removed. |
| T30 | Driver contact and profile | Add a driver phone and photo from My profile; view the child transport card as the parent. | Assigned bus and driver name, photo, email and phone display for that child. | PARTIAL - parent card showed assigned bus, driver name, email and phone; photo upload portion was not run. |

| T31 | Current-day timetable and weekend closure | Open Home and timetable on Saturday or Sunday, then on a weekday. | Weekend shows a red CLOSED notice; weekday shows only that day's routes and static morning/evening times. No countdown is shown. | PARTIAL - Saturday Home and timetable showed CLOSED; weekday ranges appeared in full-week view. Current weekday-only view not run. |
| T32 | Evening travel and booking | Open a route with evening times and select Evening return when booking. | Evening range is displayed; available numbered seats are specific to the evening trip. | PASS - evening booking, times, listing and cancellation verified; temporary booking removed. |
| T33 | Driver marks absent | Sign in as a driver and submit Absent for an assigned student. | Absent is recorded with the driver as recorder and appears in the scoped attendance list. | PASS - driver created and viewed Absent for an assigned child; temporary record removed. |

## Test summary

| Total tests | Passed | Failed | Not run |
|---:|---:|---:|---:|
| 33 | 30 | 0 | 3 |

Test run date: 2026-11-21 (Africa/Kigali). Incomplete cases are T19, the photo portion of T30, and the weekday-only portion of T31. The `test screenshots/` folder is ready for evidence images; screenshots still need to be saved there before submission because browser capture was interrupted during the upload test.