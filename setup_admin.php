<?php
require_once __DIR__ . '/includes/bootstrap.php';
$count = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='admin'")->fetchColumn();
$error = '';

if ($count > 0) { 
    page_header('Setup Complete'); 
    echo '<div class="alert alert-warning">An Administrator account already exists. Please delete <code>setup_admin.php</code>.</div>'; 
    page_footer(); 
    exit; 
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $name     = trim($_POST['full_name'] ?? ''); 
    $username = trim($_POST['username'] ?? ''); 
    $email    = trim($_POST['email'] ?? ''); 
    $password = $_POST['password'] ?? '';

    if (!valid_name($name)) { $error = 'Please enter a valid full name.'; }
    elseif (!valid_username($username)) { $error = 'Username must be 4–30 alphanumeric characters.'; }
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $error = 'Please enter a valid email address.'; }
    elseif (!valid_password($password)) { $error = 'Password must be at least 8 characters long.'; }
    else {
        $stmt = $pdo->prepare("INSERT INTO users(username, password, role, full_name, email) VALUES(?, ?, 'admin', ?, ?)");
        try {
            $stmt->execute([$username, password_hash($password, PASSWORD_DEFAULT), $name, $email]); 
            flash('Administrator created! You may now sign in.', 'success'); 
            header('Location: index.php'); 
            exit;
        } catch(PDOException $e) {
            $error = 'Username or email already exists.';
        }
    }
}
page_header('Initialize Administrator Account');
?>
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card shadow-sm border-0">
            <div class="card-body p-4">
                <h4 class="card-title fw-bold mb-3"><i class="bi bi-person-gear text-primary me-2"></i>First-Time Admin Setup</h4>
                <?php if ($error): ?><div class="alert alert-danger py-2 small mb-3"><?= e($error) ?></div><?php endif; ?>
                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Full Name</label>
                        <input name="full_name" class="form-control" required placeholder="e.g. System Administrator">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Username</label>
                        <input name="username" class="form-control" required placeholder="e.g. admin">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Email Address</label>
                        <input type="email" name="email" class="form-control" required placeholder="admin@college.edu">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Password</label>
                        <input type="password" name="password" class="form-control" required placeholder="Minimum 8 characters">
                    </div>
                    <button type="submit" class="btn btn-primary w-100 py-2"><i class="bi bi-check-circle me-1"></i>Create System Administrator</button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php page_footer(); ?>