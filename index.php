<?php
require_once __DIR__ . '/includes/bootstrap.php';
if (!empty($_SESSION['user_id'])) { header('Location: dashboard.php'); exit; }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $q = $pdo->prepare('SELECT COUNT(*) FROM login_attempts WHERE username=? AND ip_address=? AND attempted_at > (NOW() - INTERVAL 15 MINUTE)');
    $q->execute([$username, $ip]); 
    $blocked = (int)$q->fetchColumn() >= 5;

    if ($blocked) {
        $error = 'Too many failed login attempts. Please wait 15 minutes security cooldown.';
    } else {
        $q = $pdo->prepare('SELECT * FROM users WHERE username=? AND is_active=1 LIMIT 1'); 
        $q->execute([$username]); 
        $user = $q->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $pdo->prepare('DELETE FROM login_attempts WHERE username=? AND ip_address=?')->execute([$username, $ip]);
            session_regenerate_id(true);
            $_SESSION['user_id']   = (int)$user['id']; 
            $_SESSION['username']  = $user['username']; 
            $_SESSION['full_name'] = $user['full_name']; 
            $_SESSION['role']      = $user['role'];
            header('Location: dashboard.php'); 
            exit;
        }
        $pdo->prepare('INSERT INTO login_attempts(username, ip_address) VALUES(?, ?)')->execute([$username, $ip]);
        $error = 'Invalid username or password credentials.';
    }
}
page_header('Portal Authentication');
?>
<div class="row justify-content-center">
    <div class="col-md-5 col-lg-4">
        <div class="card shadow-sm border-0 mt-3">
            <div class="card-body p-4">
                <div class="text-center mb-4">
                    <i class="bi bi-shield-lock-fill text-primary display-4"></i>
                    <h4 class="fw-bold mt-2">CampusAttend LMS</h4>
                    <p class="text-muted small">Sign in to access your portal</p>
                </div>
                <?php if ($error): ?>
                    <div class="alert alert-danger py-2 small mb-3"><?= e($error) ?></div>
                <?php endif; ?>
                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Username</label>
                        <input type="text" name="username" class="form-control" required placeholder="Enter username">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Password</label>
                        <input type="password" name="password" class="form-control" required placeholder="Enter password">
                    </div>
                    <button type="submit" class="btn btn-primary w-100 py-2 font-weight-bold shadow-sm">
                        <i class="bi bi-box-arrow-in-right me-1"></i> Sign In
                    </button>
                </form>
            </div>
            <div class="card-footer bg-light text-center py-3">
                <small class="text-muted">First-time system setup? <a href="setup_admin.php" class="text-decoration-none">Run Administrator Setup</a></small>
            </div>
        </div>
    </div>
</div>
<?php page_footer(); ?>