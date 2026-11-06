<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_role(['admin']);

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] === UPLOAD_ERR_OK) {
        $fileTmp = $_FILES['csv_file']['tmp_name'];
        $handle = fopen($fileTmp, 'r');
        
        if ($handle !== false) {
            // Read header
            $header = fgetcsv($handle);
            $importedCount = 0;
            
            $pdo->beginTransaction();
            try {
                while (($data = fgetcsv($handle)) !== false) {
                    if (count($data) >= 5) {
                        $username  = trim($data[0]);
                        $full_name = trim($data[1]);
                        $email     = trim($data[2]);
                        $roll_no   = trim($data[3]);
                        $cohort_name = trim($data[4]);

                        if ($username !== '' && $email !== '' && $roll_no !== '') {
                            // Find cohort ID
                            $stmtC = $pdo->prepare('SELECT id FROM cohorts WHERE cohort_name=?');
                            $stmtC->execute([$cohort_name]);
                            $cohortId = $stmtC->fetchColumn() ?: null;

                            // Create user if not exists
                            $stmtU = $pdo->prepare('INSERT INTO users(username, password, role, full_name, email) VALUES(?, ?, "student", ?, ?) ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id)');
                            $defaultPass = password_hash('Student@2026', PASSWORD_DEFAULT);
                            $stmtU->execute([$username, $defaultPass, $full_name, $email]);
                            $userId = (int)$pdo->lastInsertId();

                            // Insert student mapping
                            $stmtS = $pdo->prepare('INSERT INTO students(user_id, roll_no, cohort_id) VALUES(?, ?, ?) ON DUPLICATE KEY UPDATE cohort_id=VALUES(cohort_id)');
                            $stmtS->execute([$userId, $roll_no, $cohortId]);

                            $importedCount++;
                        }
                    }
                }
                $pdo->commit();
                $success = "Successfully imported $importedCount students!";
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = "Error processing CSV: " . $e->getMessage();
            }
            fclose($handle);
        } else {
            $error = "Unable to open uploaded file.";
        }
    } else {
        $error = "Please upload a valid CSV file.";
    }
}

page_header('CSV Batch Student Import');
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <h5 class="fw-bold mb-3"><i class="bi bi-file-earmark-spreadsheet text-success me-2"></i>Upload Student CSV Data</h5>
                <p class="text-muted small">Import students in bulk into cohorts (LMS format). Default password assigned will be <code>Student@2026</code>.</p>
                
                <?php if ($error): ?><div class="alert alert-danger py-2 mb-3"><?= e($error) ?></div><?php endif; ?>
                <?php if ($success): ?><div class="alert alert-success py-2 mb-3"><?= e($success) ?></div><?php endif; ?>

                <form method="post" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Select CSV File</label>
                        <input type="file" name="csv_file" class="form-control" accept=".csv" required>
                    </div>
                    <button type="submit" class="btn btn-success"><i class="bi bi-upload me-1"></i>Start CSV Import</button>
                </form>

                <hr class="my-4">
                <h6 class="fw-bold">CSV Format Template Required:</h6>
                <pre class="bg-light p-3 rounded text-dark small">username,full_name,email,roll_no,cohort_name
student101,Rahul Sharma,rahul@college.edu,TMSL-BCA-001,TMSL-BCA_26-30
student102,Priya Das,priya@college.edu,TMSL-BCA-002,TMSL-BCA_26-30</pre>
            </div>
        </div>
    </div>
</div>

<?php page_footer(); ?>