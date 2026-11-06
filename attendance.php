<?php
require_once __DIR__ . '/includes/bootstrap.php'; 
require_role(['admin', 'teacher']);

$error = '';
$subjects = ($_SESSION['role'] === 'admin')
    ? $pdo->query('SELECT * FROM subjects ORDER BY subject_code')->fetchAll()
    : $pdo->prepare('SELECT * FROM subjects WHERE teacher_id=? ORDER BY subject_code');

if ($_SESSION['role'] === 'teacher') {
    $subjects->execute([$_SESSION['user_id']]);
    $subjects = $subjects->fetchAll();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $sid  = (int)($_POST['subject_id'] ?? 0);
    $date = $_POST['attendance_date'] ?? '';
    $records = $_POST['status'] ?? [];

    if ($sid <= 0 || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        $error = 'Please select a valid subject and date.';
    } else {
        $students = $pdo->query('SELECT id FROM students')->fetchAll(PDO::FETCH_COLUMN);
        $save = $pdo->prepare('INSERT INTO attendance(student_id, subject_id, attendance_date, status, marked_by) VALUES(?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE status=VALUES(status), marked_by=VALUES(marked_by)');
        
        foreach ($records as $studentId => $status) {
            if (in_array((int)$studentId, array_map('intval', $students), true) && in_array($status, ['Present', 'Absent', 'Late'], true)) {
                $save->execute([(int)$studentId, $sid, $date, $status, $_SESSION['user_id']]);
            }
        }
        flash('Attendance updated successfully!', 'success');
        header('Location: attendance.php');
        exit;
    }
}

$students = $pdo->query('SELECT s.id, s.roll_no, u.full_name, c.cohort_name, c.group_name FROM students s JOIN users u ON u.id=s.user_id LEFT JOIN cohorts c ON c.id=s.cohort_id ORDER BY s.roll_no')->fetchAll();

page_header('Mark Daily Attendance');
?>

<div class="card border-0 shadow-sm">
    <div class="card-body p-4">
        <?php if ($error): ?><div class="alert alert-danger py-2 mb-3"><?= e($error) ?></div><?php endif; ?>
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <div class="row g-3 mb-4 bg-light p-3 rounded">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Select Subject</label>
                    <select name="subject_id" class="form-select" required>
                        <option value="">-- Choose Subject --</option>
                        <?php foreach ($subjects as $s): ?>
                            <option value="<?= $s['id'] ?>"><?= e($s['subject_code'].' - '.$s['subject_name'].' ('.$s['department'].')') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Attendance Date</label>
                    <input type="date" name="attendance_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>Roll No.</th>
                            <th>Student Name</th>
                            <th>Cohort / Group</th>
                            <th style="width: 200px;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $st): ?>
                            <tr>
                                <td><code><?= e($st['roll_no']) ?></code></td>
                                <td class="fw-bold"><?= e($st['full_name']) ?></td>
                                <td><span class="badge bg-secondary"><?= e(($st['cohort_name'] ?? 'N/A').' '.$st['group_name']) ?></span></td>
                                <td>
                                    <select name="status[<?= $st['id'] ?>]" class="form-select form-select-sm">
                                        <option value="Present" selected>Present</option>
                                        <option value="Absent">Absent</option>
                                        <option value="Late">Late</option>
                                    </select>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                <button type="submit" class="btn btn-primary px-4"><i class="bi bi-check2-square me-1"></i>Save Class Attendance</button>
            </div>
        </form>
    </div>
</div>

<?php page_footer(); ?>