<?php
declare(strict_types=1);
final class UserController {
    public function index(PDO $pdo): void {
        require_role(['admin']);
        $rows = $pdo->query('SELECT id,full_name,email,phone_number,role,avatar_filename,is_active,created_at FROM users ORDER BY id ASC')->fetchAll();
        require __DIR__ . '/../views/users/index.html';
    }
    public function save(PDO $pdo): void {
        require_role(['admin']); verify_csrf();
        $id = (int)($_POST['id'] ?? 0);
        $name = post_string('full_name',120);
        $email = filter_var(trim((string)($_POST['email'] ?? '')),FILTER_VALIDATE_EMAIL);
        $phone = post_string('phone_number',30);
        $role = (string)($_POST['role'] ?? 'parent');
        $password = (string)($_POST['password'] ?? '');
        if ($name === '' || !$email) throw new InvalidArgumentException('Enter a valid name and email.');
        if ($phone !== '' && !preg_match('/^\+?[0-9()\-\s]{7,25}$/',$phone)) throw new InvalidArgumentException('Enter a valid phone number or leave the field blank.');
        if (!in_array($role,['admin','driver','parent'],true)) throw new InvalidArgumentException('Invalid role.');
        if (!$id && strlen($password)<8) throw new InvalidArgumentException('New users need a password of at least 8 characters.');
        if ($id) {
            if ($id === (int)$_SESSION['user']['id'] && $role !== 'admin') throw new InvalidArgumentException('You cannot remove your own administrator role here.');
            if ($password !== '') {
                if (strlen($password)<8) throw new InvalidArgumentException('Password must be at least 8 characters.');
                $q=$pdo->prepare('UPDATE users SET full_name=?,email=?,phone_number=?,role=?,password_hash=? WHERE id=?');
                $q->execute([$name,$email,$phone,$role,password_hash($password,PASSWORD_DEFAULT),$id]);
            } else {
                $q=$pdo->prepare('UPDATE users SET full_name=?,email=?,phone_number=?,role=? WHERE id=?');
                $q->execute([$name,$email,$phone,$role,$id]);
            }
        } else {
            $q=$pdo->prepare('INSERT INTO users(full_name,email,phone_number,password_hash,role) VALUES(?,?,?,?,?)');
            $q->execute([$name,$email,$phone,password_hash($password,PASSWORD_DEFAULT),$role]);
        }
        flash('User account saved.'); redirect('index.php?page=users');
    }
    public function delete(PDO $pdo): void {
        require_role(['admin']); verify_csrf();
        $id=(int)($_POST['id']??0);
        if ($id <= 0) throw new InvalidArgumentException('Select a valid user.');
        if ($id === (int)$_SESSION['user']['id']) throw new InvalidArgumentException('You cannot delete your own account while logged in.');
        $q=$pdo->prepare("SELECT COUNT(*) FROM users WHERE role='admin' AND is_active=1"); $q->execute();
        $admins=(int)$q->fetchColumn();
        $q=$pdo->prepare("SELECT role,is_active FROM users WHERE id=?"); $q->execute([$id]); $target=$q->fetch();
        if (!$target) throw new InvalidArgumentException('User not found.');
        if ($target['role']==='admin' && (int)$target['is_active']===1 && $admins <= 1) throw new InvalidArgumentException('You cannot delete the last active administrator.');
        try {
            $q=$pdo->prepare('DELETE FROM users WHERE id=?'); $q->execute([$id]);
        } catch (PDOException $ex) {
            if ((string)$ex->getCode()==='23000') throw new InvalidArgumentException('This user is linked to buses, students or attendance. Reassign those records before deleting the account.');
            throw $ex;
        }
        flash('User deleted.'); redirect('index.php?page=users');
    }
    public function toggle(PDO $pdo): void {
        require_role(['admin']); verify_csrf();
        $id=(int)($_POST['id']??0);
        if ($id === (int)$_SESSION['user']['id']) throw new InvalidArgumentException('You cannot deactivate your own account.');
        $q=$pdo->prepare('UPDATE users SET is_active=1-is_active WHERE id=?'); $q->execute([$id]);
        flash('User status updated.'); redirect('index.php?page=users');
    }
}
