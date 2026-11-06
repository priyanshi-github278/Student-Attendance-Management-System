<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_role(['student']);

$userId = $_SESSION['user_id'];

// Fetch detailed attendance for student
$stmt = $pdo->prepare('SELECT s.subject_code, s.subject_name, a.attendance_date, a.status FROM attendance a JOIN students st ON st.id=a.student_id JOIN subjects s ON s.id=a.subject_id WHERE st.user_id=? ORDER BY a.attendance_date DESC');
$stmt->execute([$userId]);
$records = $stmt->fetchAll();

page_header('My Attendance Records');
?>

<div class="card border-0 shadow-sm">
    <div class="card-body p-4">
        <h5 class="fw-bold mb-3"><i class="bi bi-journal-check text-primary me-2"></i>Subject Attendance Log</h5>
        <?php if (empty($records)): ?>
            <div class="alert alert-info">No attendance records have been logged for your account yet.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Date</th>
                            <th>Subject Code</th>
                            <th>Subject Name</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($records as $r): ?>
                            <tr>
                                <td><?= e($r['attendance_date']) ?></td>
                                <td><code><?= e($r['subject_code']) ?></code></td>
                                <td class="fw-semibold"><?= e($r['subject_name']) ?></td>
                                <td>
                                    <?php if ($r['status'] === 'Present'): ?>
                                        <span class="badge bg-success">Present</span>
                                    <?php elseif ($r['status'] === 'Absent'): ?>
                                        <span class="badge bg-danger">Absent</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark">Late</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php page_footer(); ?>