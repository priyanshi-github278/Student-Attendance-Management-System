<?php
require_once __DIR__ . '/includes/bootstrap.php'; 
require_login();

page_header('Dashboard');
$role = $_SESSION['role'];

// Fetch overall counters
$totalStudents = (int)$pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();
$totalCohorts  = (int)$pdo->query("SELECT COUNT(*) FROM cohorts")->fetchColumn();
$totalSubjects = (int)$pdo->query("SELECT COUNT(*) FROM subjects")->fetchColumn();

// If student, fetch personal stats
$studentStats = null;
if ($role === 'student') {
    $q = $pdo->prepare("SELECT COUNT(*) as total, SUM(status='Present') as present, SUM(status='Absent') as absent, SUM(status='Late') as late FROM attendance a JOIN students st ON st.id=a.student_id WHERE st.user_id=?");
    $q->execute([$_SESSION['user_id']]);
    $studentStats = $q->fetch();
}
?>

<div class="row g-4 mb-4">
    <div class="col-12">
        <div class="p-4 bg-white rounded shadow-sm d-flex align-items-center justify-content-between border-start border-4 border-primary">
            <div>
                <h3 class="fw-bold mb-1">Welcome, <?= e($_SESSION['full_name']) ?>!</h3>
                <p class="text-muted mb-0">Role: <span class="badge bg-primary text-uppercase"><?= e($role) ?></span> | College Attendance Management System</p>
            </div>
            <i class="bi bi-person-badge display-4 text-primary opacity-50"></i>
        </div>
    </div>
</div>

<?php if ($role === 'student'): ?>
    <?php 
    $tot = (int)($studentStats['total'] ?? 0);
    $pres = (int)($studentStats['present'] ?? 0);
    $pct = $tot > 0 ? round(($pres / $tot) * 100, 1) : 100;
    ?>
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm bg-white">
                <div class="card-body text-center p-4">
                    <h6 class="text-uppercase text-muted fw-bold">Overall Attendance</h6>
                    <div class="display-4 fw-bold text-<?= $pct >= 75 ? 'success' : 'danger' ?> my-2"><?= $pct ?>%</div>
                    <span class="badge bg-<?= $pct >= 75 ? 'success' : 'danger' ?>"><?= $pct >= 75 ? 'Good Standing' : 'Warning: Below 75%' ?></span>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="card border-0 shadow-sm bg-white h-100">
                <div class="card-body p-4 d-flex align-items-center justify-content-around text-center">
                    <div><h3 class="fw-bold text-dark mb-0"><?= $tot ?></h3><small class="text-muted">Total Classes</small></div>
                    <div class="vr"></div>
                    <div><h3 class="fw-bold text-success mb-0"><?= $pres ?></h3><small class="text-muted">Attended</small></div>
                    <div class="vr"></div>
                    <div><h3 class="fw-bold text-danger mb-0"><?= (int)($studentStats['absent'] ?? 0) ?></h3><small class="text-muted">Absent</small></div>
                </div>
            </div>
        </div>
    </div>
<?php else: ?>
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm bg-primary text-white p-4">
                <h6 class="text-uppercase opacity-75 fw-semibold">Enrolled Students</h6>
                <h2 class="display-5 fw-bold mb-0"><?= $totalStudents ?></h2>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm bg-info text-white p-4">
                <h6 class="text-uppercase opacity-75 fw-semibold">Cohorts / Groups</h6>
                <h2 class="display-5 fw-bold mb-0"><?= $totalCohorts ?></h2>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm bg-success text-white p-4">
                <h6 class="text-uppercase opacity-75 fw-semibold">Active Subjects</h6>
                <h2 class="display-5 fw-bold mb-0"><?= $totalSubjects ?></h2>
            </div>
        </div>
    </div>
<?php endif; ?>

<div class="row g-4 mb-4">
    <?php if ($role === 'admin'): ?>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-4">
                    <h5 class="fw-bold"><i class="bi bi-person-lines-fill me-2 text-primary"></i>User Management</h5>
                    <p class="text-muted small">Add, edit, and assign roles for teachers and students.</p>
                    <a href="users.php" class="btn btn-outline-primary btn-sm rounded-pill">Manage Users</a>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-4">
                    <h5 class="fw-bold"><i class="bi bi-diagram-3 me-2 text-info"></i>Cohort Management</h5>
                    <p class="text-muted small">Configure academic sessions (e.g. TMSL-BCA_26-30) & groups.</p>
                    <a href="cohorts.php" class="btn btn-outline-info btn-sm rounded-pill">Manage Cohorts</a>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-4">
                    <h5 class="fw-bold"><i class="bi bi-file-earmark-spreadsheet me-2 text-success"></i>CSV Batch Import</h5>
                    <p class="text-muted small">Bulk import students & cohorts using CSV upload format.</p>
                    <a href="students_import.php" class="btn btn-outline-success btn-sm rounded-pill">Batch Import</a>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if (in_array($role, ['admin', 'teacher'])): ?>
        <div class="col-md-6 mt-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-4">
                    <h5 class="fw-bold"><i class="bi bi-check-circle-fill me-2 text-primary"></i>Mark Attendance</h5>
                    <p class="text-muted small">Record daily class attendance subject-wise for assigned cohorts.</p>
                    <a href="attendance.php" class="btn btn-primary btn-sm rounded-pill">Mark Attendance Now</a>
                </div>
            </div>
        </div>
        <div class="col-md-6 mt-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-4">
                    <h5 class="fw-bold"><i class="bi bi-file-earmark-bar-graph me-2 text-warning"></i>Reports & Moodle Export</h5>
                    <p class="text-muted small">Generate analytics reports, CSV exports & Moodle LMS format.</p>
                    <a href="reports.php" class="btn btn-warning text-dark btn-sm rounded-pill">View Reports</a>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($role === 'student'): ?>
        <div class="col-md-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <h5 class="fw-bold"><i class="bi bi-clock-history me-2 text-primary"></i>Attendance Records</h5>
                    <p class="text-muted small">View complete breakdown of subject-wise and date-wise attendance records.</p>
                    <a href="my_attendance.php" class="btn btn-primary rounded-pill">View Detailed History</a>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php page_footer(); ?>