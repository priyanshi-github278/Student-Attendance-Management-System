<?php
function e($value): string { 
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); 
}

function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf(): void {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['csrf_token'] ?? '';
        if (!is_string($token) || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
            http_response_code(419); 
            exit('Invalid CSRF Security Token. Please refresh and try again.');
        }
    }
}

function require_login(): void {
    if (empty($_SESSION['user_id'])) { 
        header('Location: ' . BASE_URL . '/index.php'); 
        exit; 
    }
}

function require_role(array $roles): void {
    require_login();
    if (!in_array($_SESSION['role'] ?? '', $roles, true)) { 
        http_response_code(403); 
        exit('Access Denied: Insufficient Role Permissions.'); 
    }
}

function valid_username(string $v): bool { 
    return (bool)preg_match('/^[A-Za-z][A-Za-z0-9_]{3,29}$/', $v); 
}

function valid_password(string $v): bool {
    return strlen($v) >= 8;
}

function valid_name(string $v): bool { 
    return (bool)preg_match("/^[\\p{L} .'-]{2,120}$/u", $v); 
}

function flash(string $message, string $type='info'): void { 
    $_SESSION['flash'] = [$message, $type]; 
}

function show_flash(): void {
    if (!empty($_SESSION['flash'])) {
        [$m, $t] = $_SESSION['flash']; 
        unset($_SESSION['flash']);
        echo '<div class="alert alert-'.e($t).'">'.e($m).'</div>';
    }
}

function page_header(string $title): void {
    echo '<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>'.e($title).' - '.APP_NAME.'</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="'.BASE_URL.'/assets/style.css">
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
  <div class="container-fluid px-4">
    <a class="navbar-brand fw-bold text-primary" href="'.BASE_URL.'/dashboard.php"><i class="bi bi-journal-check me-2"></i>'.APP_NAME.'</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navMenu">
      <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-center">';
    if (!empty($_SESSION['user_id'])) {
        echo '<li class="nav-item"><a class="nav-link" href="'.BASE_URL.'/dashboard.php">Dashboard</a></li>';
        if ($_SESSION['role'] === 'admin') {
            echo '<li class="nav-item"><a class="nav-link" href="'.BASE_URL.'/users.php">Users</a></li>
                  <li class="nav-item"><a class="nav-link" href="'.BASE_URL.'/cohorts.php">Cohorts</a></li>
                  <li class="nav-item"><a class="nav-link" href="'.BASE_URL.'/subjects.php">Subjects</a></li>
                  <li class="nav-item"><a class="nav-link" href="'.BASE_URL.'/students_import.php">CSV Import</a></li>';
        }
        if (in_array($_SESSION['role'], ['admin', 'teacher'])) {
            echo '<li class="nav-item"><a class="nav-link" href="'.BASE_URL.'/attendance.php">Attendance</a></li>
                  <li class="nav-item"><a class="nav-link" href="'.BASE_URL.'/reports.php">Reports/Moodle</a></li>';
        }
        if ($_SESSION['role'] === 'student') {
            echo '<li class="nav-item"><a class="nav-link" href="'.BASE_URL.'/my_attendance.php">My Attendance</a></li>';
        }
        echo '<li class="nav-item ms-lg-3"><span class="badge bg-secondary me-2">'.e(strtoupper($_SESSION['role'])).'</span><a class="btn btn-outline-danger btn-sm" href="'.BASE_URL.'/logout.php">Logout</a></li>';
    }
    echo '</ul>
    </div>
  </div>
</nav>
<main class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
        <h2 class="h3 fw-bold text-dark mb-0">'.e($title).'</h2>
    </div>'; 
    show_flash();
}

function page_footer(): void { 
    echo '</main>
<footer class="footer mt-auto py-3 bg-white border-top text-center text-muted">
    <div class="container">
        <small>&copy; '.date('Y').' '.APP_NAME.' | College Attendance & LMS System</small>
    </div>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>'; 
}