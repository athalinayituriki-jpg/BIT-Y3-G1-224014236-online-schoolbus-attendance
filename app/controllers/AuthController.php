<?php
declare(strict_types=1);

final class AuthController {
    public function login(PDO $pdo): void {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            verify_csrf();
            $email = filter_var(trim((string)($_POST['email'] ?? '')), FILTER_VALIDATE_EMAIL);
            $password = (string)($_POST['password'] ?? '');
            $stmt = $pdo->prepare('SELECT id, full_name, email, password_hash, role FROM users WHERE email = ? AND is_active = 1');
            $stmt->execute([$email ?: '']);
            $user = $stmt->fetch();
            if ($user && password_verify($password, $user['password_hash'])) {
                session_regenerate_id(true);
                unset($user['password_hash']);
                $_SESSION['user'] = $user;
                flash('Welcome, ' . $user['full_name'] . '.');
                redirect('index.php');
            }
            flash('Invalid email or password.', 'error');
        }
        require __DIR__ . '/../views/auth/login.html';
    }

    public function register(PDO $pdo): void {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            verify_csrf();
            $name = post_string('full_name', 120);
            $email = filter_var(trim((string)($_POST['email'] ?? '')), FILTER_VALIDATE_EMAIL);
            $password = (string)($_POST['password'] ?? '');
            if ($name === '' || !$email) throw new InvalidArgumentException('Enter a valid name and email.');
            if (strlen($password) < 8) throw new InvalidArgumentException('Password must be at least 8 characters.');
            $lock = $pdo->query("SELECT GET_LOCK('school_attendance_first_registration', 10)")->fetchColumn();
            if ((int)$lock !== 1) throw new RuntimeException('Registration is busy. Please try again.');
            try {
                // MySQL advisory lock serializes the first registration without implicit commits.
                $pdo->beginTransaction();
                $count = (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
                $role = $count === 0 ? 'admin' : 'parent';
                $stmt = $pdo->prepare('INSERT INTO users(full_name,email,password_hash,role) VALUES(?,?,?,?)');
                $stmt->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT), $role]);
                $pdo->commit();
                flash($role === 'admin' ? 'Your account is the first account and has administrator access. Please log in.' : 'Registration successful. Please log in.');
                redirect('index.php?page=login');
            } catch (Throwable $ex) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                if ($ex instanceof PDOException && (string)$ex->getCode() === '23000') {
                    flash('That email address is already registered.', 'error');
                } else { throw $ex; }
            } finally {
                $pdo->query("SELECT RELEASE_LOCK('school_attendance_first_registration')");
            }
        }
        require __DIR__ . '/../views/auth/register.html';
    }

    public function profile(PDO $pdo): void {
        require_login();
        $id = (int)$_SESSION['user']['id'];
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            verify_csrf();
            $action = (string)($_POST['action'] ?? 'profile');
            if ($action === 'photo') {
                $upload = $_FILES['profile_photo'] ?? null;
                if (!is_array($upload) || (int)($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                    throw new InvalidArgumentException('Choose a JPG, PNG or WebP image to upload.');
                }
                if ((int)($upload['size'] ?? 0) > 2 * 1024 * 1024) {
                    throw new InvalidArgumentException('Profile photos must be 2 MB or smaller.');
                }
                $temporaryFile = (string)($upload['tmp_name'] ?? '');
                if (!is_uploaded_file($temporaryFile)) throw new InvalidArgumentException('The uploaded file could not be verified.');
                $mime = (new finfo(FILEINFO_MIME_TYPE))->file($temporaryFile);
                $extensions = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
                $imageTypes = ['image/jpeg'=>IMAGETYPE_JPEG,'image/png'=>IMAGETYPE_PNG,'image/webp'=>IMAGETYPE_WEBP];
                $imageInfo = @getimagesize($temporaryFile);
                if (!isset($extensions[$mime]) || !$imageInfo || $imageInfo[2] !== $imageTypes[$mime] || $imageInfo[0] > 5000 || $imageInfo[1] > 5000) {
                    throw new InvalidArgumentException('Use a valid JPG, PNG or WebP image no larger than 5000 by 5000 pixels.');
                }
                $directory = dirname(__DIR__, 2) . '/public/uploads/avatars';
                if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
                    throw new RuntimeException('The profile photo folder could not be created.');
                }
                $filename = bin2hex(random_bytes(20)) . '.' . $extensions[$mime];
                if (!move_uploaded_file($temporaryFile, $directory . '/' . $filename)) {
                    throw new RuntimeException('The profile photo could not be saved. Check folder permissions.');
                }
                $oldQuery = $pdo->prepare('SELECT avatar_filename FROM users WHERE id=?');
                $oldQuery->execute([$id]);
                $oldFilename = $oldQuery->fetchColumn();
                $save = $pdo->prepare('UPDATE users SET avatar_filename=? WHERE id=?');
                $save->execute([$filename, $id]);
                $_SESSION['user']['avatar_filename'] = $filename;
                if (is_string($oldFilename) && $oldFilename !== '' && basename($oldFilename) === $oldFilename) {
                    $oldPath = $directory . '/' . $oldFilename;
                    if (is_file($oldPath)) @unlink($oldPath);
                }
                flash('Profile photo updated.');
                redirect('index.php?page=profile');
            }
            if ($action === 'profile') {
                $name = post_string('full_name', 120);
                $email = filter_var(trim((string)($_POST['email'] ?? '')), FILTER_VALIDATE_EMAIL);
                $phone = post_string('phone_number',30);
                if ($name === '' || !$email) throw new InvalidArgumentException('Enter a valid name and email.');
                if ($phone !== '' && !preg_match('/^\+?[0-9()\-\s]{7,25}$/',$phone)) throw new InvalidArgumentException('Enter a valid phone number or leave the field blank.');
                $q = $pdo->prepare('UPDATE users SET full_name=?, email=?, phone_number=? WHERE id=?');
                $q->execute([$name, $email, $phone, $id]);
                $_SESSION['user']['full_name'] = $name;
                $_SESSION['user']['email'] = $email;
                flash('Profile updated successfully.');
            } elseif ($action === 'password') {
                $current = (string)($_POST['current_password'] ?? '');
                $new = (string)($_POST['new_password'] ?? '');
                $confirm = (string)($_POST['confirm_password'] ?? '');
                $q = $pdo->prepare('SELECT password_hash FROM users WHERE id=?');
                $q->execute([$id]); $row = $q->fetch();
                if (!$row || !password_verify($current, $row['password_hash'])) throw new InvalidArgumentException('Current password is incorrect.');
                if (strlen($new) < 8) throw new InvalidArgumentException('New password must be at least 8 characters.');
                if ($new !== $confirm) throw new InvalidArgumentException('New passwords do not match.');
                $q = $pdo->prepare('UPDATE users SET password_hash=? WHERE id=?');
                $q->execute([password_hash($new, PASSWORD_DEFAULT), $id]);
                flash('Password changed successfully.');
            } else { throw new InvalidArgumentException('Invalid profile action.'); }
            redirect('index.php?page=profile');
        }
        $q = $pdo->prepare('SELECT id,full_name,email,phone_number,role,avatar_filename,created_at FROM users WHERE id=?');
        $q->execute([$id]); $profile = $q->fetch();
        require __DIR__ . '/../views/profile.html';
    }

    public function logout(): void {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time()-42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
        redirect('index.php?page=login');
    }
}
