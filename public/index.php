<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';

foreach (glob(__DIR__ . '/../app/controllers/*.php') ?: [] as $controllerFile) {
    require_once $controllerFile;
}

$page = isset($_GET['page']) && is_string($_GET['page']) ? $_GET['page'] : 'dashboard';
$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

try {
    $auth = new AuthController();
    $buses = new BusController();
    $students = new StudentController();
    $attendance = new AttendanceController();
    $users = new UserController();
    $schedules = new ScheduleController();
    $bookings = new BookingController();

    $postOnly = static function () use ($method): void {
        if ($method !== 'POST') {
            http_response_code(405);
            header('Allow: POST');
            exit('Method not allowed.');
        }
    };

    switch ($page) {
        case 'login': $auth->login($pdo); break;
        case 'register': $auth->register($pdo); break;
        case 'profile': $auth->profile($pdo); break;
        case 'logout':
            $postOnly();
            require_login();
            verify_csrf();
            $auth->logout();
            break;

        case 'buses': $buses->index($pdo); break;
        case 'bus-create': $buses->create($pdo); break;
        case 'bus-edit': $buses->edit($pdo); break;
        case 'bus-save': $postOnly(); $buses->save($pdo); break;
        case 'bus-delete': $postOnly(); $buses->delete($pdo); break;

        case 'students': $students->index($pdo); break;
        case 'student-form': $students->form($pdo); break;
        case 'student-save': $postOnly(); $students->save($pdo); break;
        case 'student-delete': $postOnly(); $students->delete($pdo); break;

        case 'attendance': $attendance->index($pdo); break;
        case 'attendance-create': $attendance->create($pdo); break;
        case 'attendance-mark': $postOnly(); $attendance->mark($pdo); break;

        case 'users': $users->index($pdo); break;
        case 'user-save': $postOnly(); $users->save($pdo); break;
        case 'user-toggle': $postOnly(); $users->toggle($pdo); break;
        case 'user-delete': $postOnly(); $users->delete($pdo); break;

        case 'bookings': $bookings->index($pdo); break;
        case 'booking-form': $bookings->create($pdo); break;
        case 'booking-save': $postOnly(); $bookings->save($pdo); break;
        case 'booking-cancel': $postOnly(); $bookings->cancel($pdo); break;

        case 'schedules': $schedules->index($pdo); break;
        case 'schedule-form': $schedules->form($pdo); break;
        case 'schedule-save': $postOnly(); $schedules->save($pdo); break;
        case 'schedule-delete': $postOnly(); $schedules->delete($pdo); break;



        case 'api-students':
            require_role(['admin', 'driver']);
            header('Content-Type: application/json; charset=utf-8');
            header('Cache-Control: no-store');

            $term = trim((string)($_GET['q'] ?? ''));
            $length = function_exists('mb_strlen') ? mb_strlen($term, 'UTF-8') : strlen($term);
            if ($length > 80) {
                http_response_code(400);
                echo json_encode(['error' => 'Search term must be 80 characters or fewer']);
                exit;
            }

            // Group the OR terms so the driver restriction applies to both.
            $sql = 'SELECT s.id, s.full_name, s.student_code
                    FROM students s
                    WHERE (LOWER(s.full_name) LIKE LOWER(?) OR LOWER(s.student_code) LIKE LOWER(?))';
            $params = ['%' . $term . '%', '%' . $term . '%'];

            if (($_SESSION['user']['role'] ?? '') === 'driver') {
                $sql .= ' AND s.bus_id IN (SELECT b.id FROM buses b WHERE b.driver_id = ?)';
                $params[] = (int)$_SESSION['user']['id'];
            }
            $sql .= ' ORDER BY s.full_name ASC LIMIT 20';

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC), JSON_THROW_ON_ERROR);
            exit;

        case 'home':
        case 'dashboard':
        case '':
            require_login();
            $role=$_SESSION['user']['role']; $userId=(int)$_SESSION['user']['id']; $todayName=date('l');
            $routeFields='id,bus_id,day_of_week,route_name,origin_location,parking_location,destination_location,route_description,departure_time,arrival_time,evening_departure_time,evening_arrival_time';
            $todaySql='SELECT '.$routeFields.' FROM schedule_entries WHERE day_of_week=?'; $todayParams=[$todayName];
            if($role==='parent') { $todaySql.=' AND bus_id IN (SELECT DISTINCT bus_id FROM students WHERE parent_id=?)'; $todayParams[]=$userId; }
            elseif($role==='driver') { $todaySql.=' AND bus_id IN (SELECT id FROM buses WHERE driver_id=?)'; $todayParams[]=$userId; }
            $todaySql.=' ORDER BY departure_time'; $q=$pdo->prepare($todaySql); $q->execute($todayParams); $scheduleRows=$q->fetchAll();
            $routeDate=date('Y-m-d'); $routeDayName=$todayName; $showingNextDay=false; $isClosedDay=in_array($todayName,['Saturday','Sunday'],true);
            if($isClosedDay) $scheduleRows=[];
            if($role==='parent') {
                $q=$pdo->prepare('SELECT COUNT(*) FROM students WHERE parent_id=?'); $q->execute([$userId]); $studentCount=(int)$q->fetchColumn();
                $q=$pdo->prepare('SELECT COUNT(DISTINCT bus_id) FROM students WHERE parent_id=?'); $q->execute([$userId]); $busCount=(int)$q->fetchColumn();
                $q=$pdo->prepare('SELECT COUNT(*) FROM attendance_logs a JOIN students s ON s.id=a.student_id WHERE s.parent_id=?'); $q->execute([$userId]); $attendanceCount=(int)$q->fetchColumn();
            } elseif($role==='driver') {
                $q=$pdo->prepare('SELECT COUNT(*) FROM students s JOIN buses b ON b.id=s.bus_id WHERE b.driver_id=?'); $q->execute([$userId]); $studentCount=(int)$q->fetchColumn();
                $q=$pdo->prepare('SELECT COUNT(*) FROM buses WHERE driver_id=?'); $q->execute([$userId]); $busCount=(int)$q->fetchColumn();
                $q=$pdo->prepare('SELECT COUNT(*) FROM attendance_logs a JOIN students s ON s.id=a.student_id JOIN buses b ON b.id=s.bus_id WHERE b.driver_id=?'); $q->execute([$userId]); $attendanceCount=(int)$q->fetchColumn();
            } else {
                $studentCount=(int)$pdo->query('SELECT COUNT(*) FROM students')->fetchColumn();
                $busCount=(int)$pdo->query('SELECT COUNT(*) FROM buses')->fetchColumn();
                $attendanceCount=(int)$pdo->query('SELECT COUNT(*) FROM attendance_logs')->fetchColumn();
            }
            $counts=['students'=>$studentCount,'buses'=>$busCount,'attendance'=>$attendanceCount];
            $fleetRows=$pdo->query('SELECT b.bus_number,b.plate_number,b.photo_filename,u.full_name AS driver_name,s.route_name FROM buses b LEFT JOIN users u ON u.id=b.driver_id LEFT JOIN schedule_entries s ON s.id=b.route_id ORDER BY b.bus_number')->fetchAll();
            require __DIR__ . '/../app/views/dashboard.html';
            break;
        default:
            http_response_code(404);
            echo 'Page not found.';
    }
} catch (InvalidArgumentException $ex) {
    flash($ex->getMessage(), 'error');
    redirect('index.php');
} catch (PDOException $ex) {
    error_log('[Database operation] ' . $ex->getMessage());
    http_response_code(500);
    echo '<h1>Database operation failed</h1>';
    echo '<p>Check the PHP error log for details. Verify that database/schema.sql matches the installed database.</p>';
} catch (Throwable $ex) {
    error_log('[Application error] ' . $ex->getMessage());
    http_response_code(500);
    echo '<h1>Application error</h1>';
    echo '<p>An unexpected error occurred. Check the PHP error log for details.</p>';
}

