<?php
declare(strict_types=1);

final class AttendanceController {
    public function index(PDO $pdo): void {
        require_role(['admin','driver','parent']);
        $sql='SELECT a.*,s.full_name AS student_name,s.student_code,b.bus_number,u.full_name AS marked_by FROM attendance_logs a JOIN students s ON s.id=a.student_id JOIN buses b ON b.id=s.bus_id JOIN users u ON u.id=a.marked_by';
        $params=[];
        if($_SESSION['user']['role']==='parent') {$sql.=' WHERE s.parent_id=?';$params[]=$_SESSION['user']['id'];}
        elseif($_SESSION['user']['role']==='driver') {$sql.=' WHERE b.driver_id=?';$params[]=$_SESSION['user']['id'];}
        $sql.=' ORDER BY a.event_time DESC LIMIT 250';
        $q=$pdo->prepare($sql);$q->execute($params);$rows=$q->fetchAll();
        require __DIR__ . '/../views/attendance/index.html';
    }
    public function mark(PDO $pdo): void {
        require_role(['admin','driver']); verify_csrf();
        $student=(int)($_POST['student_id']??0); $status=$_POST['status']??'';
        if(!in_array($status,['boarded','alighted','absent'],true)) throw new InvalidArgumentException('Choose boarded, alighted or absent.');
        if($_SESSION['user']['role']==='driver'){
            $q=$pdo->prepare('SELECT s.id FROM students s JOIN buses b ON b.id=s.bus_id WHERE s.id=? AND b.driver_id=?');
            $q->execute([$student,$_SESSION['user']['id']]);
            if(!$q->fetch()) throw new InvalidArgumentException('You can only record attendance for students on your assigned bus.');
        }
        $q=$pdo->prepare('INSERT INTO attendance_logs(student_id,marked_by,status,event_time) VALUES(?,?,?,NOW())');
        $q->execute([$student,$_SESSION['user']['id'],$status]);
        flash('Attendance recorded.'); redirect('index.php?page=attendance');
    }
    public function create(PDO $pdo): void {
        require_role(['admin','driver']);
        if($_SESSION['user']['role']==='driver'){
            $q=$pdo->prepare('SELECT s.id,s.full_name,s.student_code FROM students s JOIN buses b ON b.id=s.bus_id WHERE b.driver_id=? ORDER BY s.full_name');
            $q->execute([$_SESSION['user']['id']]); $students=$q->fetchAll();
        } else $students=$pdo->query('SELECT id,full_name,student_code FROM students ORDER BY full_name')->fetchAll();
        require __DIR__ . '/../views/attendance/form.html';
    }
}
