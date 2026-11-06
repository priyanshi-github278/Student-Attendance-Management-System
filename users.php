<?php
require_once __DIR__ . '/includes/bootstrap.php'; 
require_role(['admin']);

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf(); 
    $name     = trim($_POST['full_name'] ?? ''); 
    $username = trim($_POST['username'] ?? ''); 
    $email    = trim($_POST['email'] ?? ''); 
    $password = $_POST['password'] ?? ''; 
    $role     = $_POST['role'] ?? 'student';

    if (!valid_name($name)) $error = 'Invalid full name format.';
    elseif (!valid_username($username)) $error = 'Username must be 4–30 alphanumeric characters.';
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $error = 'Invalid email address.';
    elseif (!valid_password($password)) $error = 'Password must be at least 8 characters.';
    elseif (!in_array($role, ['teacher', 'student'], true)) $error = 'Invalid role specified.';
    else {
        try {
            $pdo->beginTransaction();
            $s = $pdo->prepare('INSERT INTO users(username, password, role, full_name, email) VALUES(?, ?, ?, ?, ?)');
            $s->execute([$username, password_hash($password, PASSWORD_DEFAULT), $role, $name, $email]); 
            $uid = (int)$pdo->lastInsertId();

            if ($role === 'student') {
                $roll   = trim($_POST['roll_no'] ?? ''); 
                $cohort = (int)($_POST['cohort_id'] ?? 0);
                if ($roll === '') throw new Exception('Roll number is mandatory for student account.');
                $s = $pdo->prepare('INSERT INTO students(user_id, roll_no, cohort_id) VALUES(?, ?, ?)'); 
                $s->execute([$uid, $roll, $cohort ?: null]);
            }

            $pdo->commit(); 
            flash('User registered successfully!', 'success'); 
            header('Location: users.php'); 
            exit;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $error = 'Failed to create user. Duplicate username, email, or roll number detected.';
        }
    }
}

$users = $pdo->query('SELECT id, username, full_name, email, role, is_active, created_at FROM users ORDER BY id DESC')->fetchAll();
$cohorts = $pdo->query('SELECT * FROM cohorts ORDER BY cohort_name')->fetchAll();

page_header('User Management');
?>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <h5 class="fw-bold mb-3"><i class="bi bi-person-plus text-primary me-2"></i>Create New User</h5>
                <?php if ($error): ?><div class="alert alert-danger py-2 small mb-3"><?= e($error) ?></div><?php endif; ?>
                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <div class="mb-2">
                        <label class="form-label small fw-semibold">Full Name</label>
                        <input name="full_name" class="form-control form-control-sm" required placeholder="e.g. Prof. Anirban">
                    </div>
                    <div class="mb-2">
                        <label class="form-label small fw-semibold">Username</label>
                        <input name="username" class="form-control form-control-sm" required placeholder="e.g. anirban_prof">
                    </div>
                    <div class="mb-2">
                        <label class="form-label small fw-semibold">Email</label>
                        <input type="email" name="email" class="form-control form-control-sm" required placeholder="name@college.edu">
                    </div>
                    <div class="mb-2">
                        <label class="form-label small fw-semibold">Temporary Password</label>
                        <input type="password" name="password" class="form-control form-control-sm" required placeholder="Min 8 characters">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Role</label>
                        <select name="role" class="form-select form-select-sm" id="roleSelect" onchange="toggleStudentFields()">
                            <option value="student">Student</option>
                            <option value="teacher">Teacher</option>
                        </select>
                    </div>

                    <div id="studentFields">
                        <div class="mb-2">
                            <label class="form-label small fw-semibold">Roll Number (Student)</label>
                            <input name="roll_no" class="form-control form-control-sm" placeholder="e.g. BCA2026001">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Assign Cohort / Group</label>
                            <select name="cohort_id" class="form-select form-select-sm">
                                <option value="0">-- Select Cohort --</option>
                                <?php foreach ($cohorts as $c): ?>
                                    <option value="<?= $c['id'] ?>"><?= e($c['cohort_name'].' ('.$c['group_name'].')') ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-sm w-100"><i class="bi bi-save me-1"></i>Save User</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <h5 class="fw-bold mb-3"><i class="bi bi-people me-2 text-primary"></i>System Users List</h5>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Name</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $u): ?>
                                <tr>
                                    <td class="fw-semibold"><?= e($u['full_name']) ?></td>
                                    <td><code><?= e($u['username']) ?></code></td>
                                    <td><?= e($u['email']) ?></td>
                                    <td>
                                        <span class="badge bg-<?= $u['role'] === 'admin' ? 'danger' : ($u['role'] === 'teacher' ? 'primary' : 'secondary') ?>">
                                            <?= e(strtoupper($u['role'])) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= $u['is_active'] ? 'success' : 'warning' ?>">
                                            <?= $u['is_active'] ? 'Active' : 'Disabled' ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function toggleStudentFields() {
    var role = document.getElementById('roleSelect').value;
    document.getElementById('studentFields').style.display = (role === 'student') ? 'block' : 'none';
}
</script>

<?php page_footer(); ?>