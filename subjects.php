<?php
require_once __DIR__ . '/includes/bootstrap.php'; 
require_role(['admin']);

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $code    = strtoupper(trim($_POST['subject_code'] ?? ''));
    $name    = trim($_POST['subject_name'] ?? '');
    $dept    = trim($_POST['department'] ?? '');
    $sem     = trim($_POST['semester'] ?? '');
    $teacher = (int)($_POST['teacher_id'] ?? 0);

    if ($code === '' || $name === '' || $dept === '' || $sem === '') {
        $error = 'Please fill in all subject details.';
    } else {
        try {
            $s = $pdo->prepare('INSERT INTO subjects(subject_code, subject_name, department, semester, teacher_id) VALUES(?, ?, ?, ?, ?)');
            $s->execute([$code, $name, $dept, $sem, $teacher ?: null]);
            flash('Subject added successfully.', 'success');
            header('Location: subjects.php');
            exit;
        } catch (PDOException $e) {
            $error = 'Subject code already exists.';
        }
    }
}

$teachers = $pdo->query("SELECT id, full_name FROM users WHERE role='teacher' AND is_active=1 ORDER BY full_name")->fetchAll();
$subjects = $pdo->query('SELECT s.*, u.full_name as teacher_name FROM subjects s LEFT JOIN users u ON u.id=s.teacher_id ORDER BY s.department, s.semester, s.subject_code')->fetchAll();

page_header('Subject Management & Teacher Allocation');
?>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <h5 class="fw-bold mb-3"><i class="bi bi-book text-primary me-2"></i>Add Subject</h5>
                <?php if ($error): ?><div class="alert alert-danger py-2 small mb-3"><?= e($error) ?></div><?php endif; ?>
                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <div class="mb-2">
                        <label class="form-label small fw-semibold">Subject Code</label>
                        <input name="subject_code" class="form-control form-control-sm" placeholder="e.g. BCA101" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small fw-semibold">Subject Name</label>
                        <input name="subject_name" class="form-control form-control-sm" placeholder="e.g. Programming in C" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small fw-semibold">Department</label>
                        <input name="department" class="form-control form-control-sm" placeholder="e.g. BCA, BBA" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small fw-semibold">Semester</label>
                        <input name="semester" class="form-control form-control-sm" placeholder="e.g. Sem 1, Sem 2" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Assigned Faculty</label>
                        <select name="teacher_id" class="form-select form-select-sm">
                            <option value="0">-- Select Faculty --</option>
                            <?php foreach ($teachers as $t): ?>
                                <option value="<?= $t['id'] ?>"><?= e($t['full_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm w-100"><i class="bi bi-plus-circle me-1"></i>Save Subject</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <h5 class="fw-bold mb-3"><i class="bi bi-journal-text me-2 text-primary"></i>Assigned Subjects & Faculty</h5>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Code</th>
                                <th>Subject Name</th>
                                <th>Dept / Sem</th>
                                <th>Assigned Teacher</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($subjects as $s): ?>
                                <tr>
                                    <td><code class="fw-bold"><?= e($s['subject_code']) ?></code></td>
                                    <td class="fw-semibold"><?= e($s['subject_name']) ?></td>
                                    <td><?= e($s['department'].' - '.$s['semester']) ?></td>
                                    <td>
                                        <?php if ($s['teacher_name']): ?>
                                            <span class="badge bg-primary"><i class="bi bi-person me-1"></i><?= e($s['teacher_name']) ?></span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Unassigned</span>
                                        <?php endif; ?>
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

<?php page_footer(); ?>