<?php
require_once __DIR__ . '/includes/bootstrap.php'; 
require_role(['admin']);

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf(); 
    $name  = trim($_POST['cohort_name'] ?? ''); 
    $year  = trim($_POST['academic_year'] ?? ''); 
    $dept  = trim($_POST['department'] ?? ''); 
    $sem   = trim($_POST['semester'] ?? ''); 
    $group = trim($_POST['group_name'] ?? 'W1');

    if ($name === '' || $year === '' || $dept === '' || $sem === '') {
        $error = 'All cohort fields are required.';
    } else {
        try {
            $s = $pdo->prepare('INSERT INTO cohorts(cohort_name, academic_year, department, semester, group_name) VALUES(?, ?, ?, ?, ?)');
            $s->execute([$name, $year, $dept, $sem, $group]);
            flash('Cohort created successfully.', 'success');
            header('Location: cohorts.php');
            exit;
        } catch (PDOException $e) {
            $error = 'Cohort name or combination already exists.';
        }
    }
}

$rows = $pdo->query('SELECT c.*, COUNT(s.id) as student_count FROM cohorts c LEFT JOIN students s ON s.cohort_id=c.id GROUP BY c.id ORDER BY c.cohort_name')->fetchAll();
page_header('Cohort & Academic Group Management');
?>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <h5 class="fw-bold mb-3"><i class="bi bi-folder-plus text-primary me-2"></i>Add New Cohort</h5>
                <?php if ($error): ?><div class="alert alert-danger py-2 small mb-3"><?= e($error) ?></div><?php endif; ?>
                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <div class="mb-2">
                        <label class="form-label small fw-semibold">Cohort Identifier / Name</label>
                        <input name="cohort_name" class="form-control form-control-sm" placeholder="e.g. TMSL-BCA_26-30" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small fw-semibold">Academic Session</label>
                        <input name="academic_year" class="form-control form-control-sm" placeholder="e.g. 2026-2030" required>
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
                        <label class="form-label small fw-semibold">Group / LMS Section</label>
                        <input name="group_name" class="form-control form-control-sm" placeholder="e.g. W1, K1" value="W1" required>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm w-100"><i class="bi bi-save me-1"></i>Create Cohort</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <h5 class="fw-bold mb-3"><i class="bi bi-diagram-3 me-2 text-primary"></i>Active Cohorts List</h5>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Cohort Identifier</th>
                                <th>Academic Session</th>
                                <th>Dept / Sem</th>
                                <th>Group</th>
                                <th>Students</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rows as $r): ?>
                                <tr>
                                    <td><code class="fw-bold"><?= e($r['cohort_name']) ?></code></td>
                                    <td><?= e($r['academic_year']) ?></td>
                                    <td><?= e($r['department'].' - '.$r['semester']) ?></td>
                                    <td><span class="badge bg-info text-dark"><?= e($r['group_name']) ?></span></td>
                                    <td><span class="badge bg-secondary"><?= $r['student_count'] ?> enrolled</span></td>
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