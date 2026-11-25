<?php
declare(strict_types=1);

function e(mixed $value): string {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function travel_duration(string $departure, string $arrival): string {
    $start = strtotime($departure);
    $end = strtotime($arrival);
    if ($start === false || $end === false) return 'Time unavailable';
    $minutes = (int)(($end - $start) / 60);
    if ($minutes < 0) $minutes += 1440;
    if ($minutes === 0) return '0 minutes';
    $hours = intdiv($minutes, 60);
    $remaining = $minutes % 60;
    $parts = [];
    if ($hours > 0) $parts[] = $hours . ' hr' . ($hours === 1 ? '' : 's');
    if ($remaining > 0) $parts[] = $remaining . ' min';
    return implode(' ', $parts);
}
function redirect(string $path): never {
    header('Location: ' . $path);
    exit;
}

function require_login(): void {
    if (empty($_SESSION['user'])) {
        redirect('index.php?page=login');
    }
}

function require_role(array $roles): void {
    require_login();
    if (!in_array($_SESSION['user']['role'] ?? '', $roles, true)) {
        http_response_code(403);
        exit('Access denied.');
    }
}

function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . e($_SESSION['csrf_token'] ?? '') . '">';
}

function verify_csrf(): void {
    $sent = $_POST['csrf_token'] ?? '';
    $saved = $_SESSION['csrf_token'] ?? '';
    if (!is_string($sent) || !is_string($saved) || $saved === '' || !hash_equals($saved, $sent)) {
        http_response_code(419);
        exit('Invalid or expired form token. Refresh the page and try again.');
    }
}

function post_string(string $key, int $max = 255): string {
    $raw = $_POST[$key] ?? '';
    if (!is_string($raw)) {
        throw new InvalidArgumentException("Invalid value for {$key}.");
    }
    $value = trim($raw);
    $length = function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
    if ($length > $max) {
        throw new InvalidArgumentException("{$key} is too long.");
    }
    return $value;
}

function flash(string $message, string $type = 'success'): void {
    $_SESSION['flash'] = ['message' => $message, 'type' => $type];
}

