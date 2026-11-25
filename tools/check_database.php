<?php
declare(strict_types=1);

// Run from the project root: php tools/check_database.php
require __DIR__ . '/../config/config.php';

$required = [
    'users' => ['id','full_name','email','password_hash','role','is_active','created_at'],
    'buses' => ['id','bus_number','plate_number','capacity','driver_id','created_at'],
    'students' => ['id','full_name','student_code','grade','bus_id','parent_id','created_at'],
    'attendance_logs' => ['id','student_id','marked_by','status','event_time'],
];

$failed = false;
echo "Database connection: OK\n";
foreach ($required as $table => $columns) {
    $stmt = $pdo->query("SHOW COLUMNS FROM `{$table}`");
    $actual = array_column($stmt->fetchAll(), 'Field');
    $missing = array_values(array_diff($columns, $actual));
    if ($missing) {
        $failed = true;
        echo "[FAIL] {$table}: missing " . implode(', ', $missing) . "\n";
    } else {
        echo "[PASS] {$table}: required columns present\n";
    }
}
if ($failed) {
    fwrite(STDERR, "Database schema does not match this application. Import database/schema.sql into an empty database or migrate carefully.\n");
    exit(1);
}
echo "Schema check: PASS\n";
