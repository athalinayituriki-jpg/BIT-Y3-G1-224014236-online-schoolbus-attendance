<?php
declare(strict_types=1);

final class StudentController {
    public function index(PDO $pdo): void {
        require_role(['admin','driver','parent']);
        $sql='SELECT s.*, b.bus_number,b.plate_number,b.capacity,b.driver_id,u.full_name AS parent_name,d.full_name AS driver_name,d.email AS driver_email,d.phone_number AS driver_phone,d.avatar_filename AS driver_avatar FROM students s LEFT JOIN buses b ON b.id=s.bus_id LEFT JOIN users u ON u.id=s.parent_id LEFT JOIN users d ON d.id=b.driver_id';
        if ($_SESSION['user']['role'] === 'parent') {
            $stmt = $pdo->prepare($sql . ' WHERE s.parent_id=? ORDER BY s.id DESC');
            $stmt->execute([(int)$_SESSION['user']['id']]);
            $rows = $stmt->fetchAll();
        } elseif ($_SESSION['user']['role'] === 'driver') {
            $stmt = $pdo->prepare($sql . ' WHERE s.bus_id IN (SELECT id FROM buses WHERE driver_id=?) ORDER BY s.id DESC');
            $stmt->execute([(int)$_SESSION['user']['id']]);
            $rows = $stmt->fetchAll();
        } else {
            $rows = $pdo->query($sql . ' ORDER BY s.id DESC')->fetchAll();
        }
        require __DIR__ . '/../views/students/index.html';
    }
    public function form(PDO $pdo): void {
        require_role(['admin']);
        $student=null;
        if (!empty($_GET['id'])) { $q=$pdo->prepare('SELECT * FROM students WHERE id=?'); $q->execute([(int)$_GET['id']]); $student=$q->fetch(); if(!$student) exit('Student not found.'); }
        $buses=$pdo->query('SELECT id,bus_number FROM buses ORDER BY bus_number')->fetchAll();
        $parents=$pdo->query("SELECT id,full_name FROM users WHERE role='parent' AND is_active=1 ORDER BY full_name")->fetchAll();
        require __DIR__ . '/../views/students/form.html';
    }
    public function save(PDO $pdo): void {
        require_role(['admin']); verify_csrf();
        $id=(int)($_POST['id']??0); $name=post_string('full_name',120); $code=post_string('student_code',30);
        $grade=post_string('grade',30); $bus=(int)($_POST['bus_id']??0); $parent=(int)($_POST['parent_id']??0);
        if($name===''||$code===''||$grade==='') throw new InvalidArgumentException('Complete all student fields.');
        $check=$pdo->prepare('SELECT id FROM buses WHERE id=?'); $check->execute([$bus]); if(!$check->fetch()) throw new InvalidArgumentException('Select a valid bus.');
        $check=$pdo->prepare("SELECT id FROM users WHERE id=? AND role='parent'"); $check->execute([$parent]); if(!$check->fetch()) throw new InvalidArgumentException('Select a valid parent.');
        if($id){$q=$pdo->prepare('UPDATE students SET full_name=?,student_code=?,grade=?,bus_id=?,parent_id=? WHERE id=?');$q->execute([$name,$code,$grade,$bus,$parent,$id]);}
        else {$q=$pdo->prepare('INSERT INTO students(full_name,student_code,grade,bus_id,parent_id) VALUES(?,?,?,?,?)');$q->execute([$name,$code,$grade,$bus,$parent]);}
        flash('Student saved.'); redirect('index.php?page=students');
    }
    public function delete(PDO $pdo): void {
        require_role(['admin']); verify_csrf();
        $q=$pdo->prepare('DELETE FROM students WHERE id=?'); $q->execute([(int)($_POST['id']??0)]);
        flash('Student deleted.'); redirect('index.php?page=students');
    }
}
