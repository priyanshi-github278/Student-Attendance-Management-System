<?php
require_once __DIR__ . '/includes/bootstrap.php'; 
require_role(['admin', 'teacher']);

if (isset($_GET['export']) && in_array($_GET['export'], ['attendance', 'moodle'], true)) {
    $sql = 'SELECT u.username, u.full_name, st.roll_no, s.subject_code, s.subject_name, a.attendance_date, a.status FROM attendance a JOIN students st ON st.id=a.student_id JOIN users u ON u.id=st.user_id JOIN subjects s ON s.id=a.subject_id ORDER BY a.attendance_date, u.username';
    $rows = $pdo->query($sql)->fetchAll();
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="'.($_GET['export'] === 'moodle' ? 'moodle_attendance_export.csv' : 'attendance_report.csv').'"');
    $out = fopen('php://output', 'w');
    
    if ($_GET['export'] === 'moodle') {
        fputcsv($out, ['username', 'firstname', 'lastname', 'course1', 'attendance_date', 'attendance_status']);
        foreach ($rows as $r) {
            $parts = preg_split('/\s+/', trim($r['full_name']), 2);
            fputcsv($out, [$r['username'], $parts[0] ?? '', $parts[1] ?? '', $r['subject_code'], $r['attendance_date'], $r['status']]);
        }
    } else {
        fputcsv($out, ['username', 'full_name', 'roll_no', 'subject_code', 'subject_name', 'attendance_date', 'status']);
        foreach ($rows as $r) fputcsv($out, array_values($r));
    }
    fclose($out);
    exit;
}

$rows = $pdo->query('SELECT s.subject_code, s.subject_name, COUNT(a.id) total, SUM(a.status="Present") present, SUM(a.status="Absent") absent, SUM(a.status="Late") late FROM subjects s LEFT JOIN attendance a ON a.subject_id=s.id GROUP BY s.id ORDER BY s.subject_code')->fetchAll();

page_header('Reports & Moodle LMS Integration');
?>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-4">
        <h5 class="fw-bold mb-3"><i class="bi bi-download me-2 text-primary"></i>Export Attendance Data</h5>
        <a class="btn btn-success me-2" href="?export=attendance"><i class="bi bi-file-earmark-spreadsheet me-1"></i>Standard CSV Report</a>
        <a class="btn btn-primary" href="?export=moodle"><i class="bi bi-box-seam me-1"></i>Moodle LMS Compatible CSV</a>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-4">
        <h5 class="fw-bold mb-3"><i class="bi bi-bar-chart me-2 text-primary"></i>Subject Attendance Summary</h5>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-light">
                    <tr>
                        <th>Subject</th>
                        <th>Total Records</th>
                        <th>Present</th>
                        <th>Absent</th>
                        <th>Late</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $r): ?>
                        <tr>
                            <td class="fw-bold"><?= e($r['subject_code'].' - '.$r['subject_name']) ?></td>
                            <td><?= $r['total'] ?></td>
                            <td><span class="badge bg-success"><?= $r['present'] ?? 0 ?></span></td>
                            <td><span class="badge bg-danger"><?= $r['absent'] ?? 0 ?></span></td>
                            <td><span class="badge bg-warning text-dark"><?= $r['late'] ?? 0 ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php page_footer(); ?>