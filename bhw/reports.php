<?php
require_once "config.php";
require_once "includes/auth.php";
require_login();

$report = $_GET['report'] ?? 'patients';
$date_from = $_GET['date_from'] ?? date('Y-m-01');
$date_to = $_GET['date_to'] ?? date('Y-m-d');
$allowed = ['patients', 'consultations', 'home_visits', 'vaccinations', 'medicines', 'followups'];
if (!in_array($report, $allowed, true)) $report = 'patients';

$config = [
'patients' => ['title' => 'Patient Report', 'sql' => "SELECT patient_no AS 'Patient ID', full_name AS 'Full Name', age AS Age, sex AS Sex, address AS Address, contact AS Contact FROM patients ORDER BY full_name", 'date' => false],
'consultations' => ['title' => 'Consultation Report', 'sql' => "SELECT p.patient_no AS 'Patient ID', p.full_name AS 'Patient', c.consultation_date AS Date, c.complaint AS Complaint, c.assessment AS Assessment FROM consultations c JOIN patients p ON p.id = c.patient_id WHERE c.consultation_date BETWEEN ? AND ? ORDER BY c.consultation_date DESC", 'date' => true],
'home_visits' => ['title' => 'Home Visit Report', 'sql' => "SELECT p.patient_no AS 'Patient ID', p.full_name AS 'Patient', h.visit_date AS Date, h.reason AS Reason, h.findings AS Findings FROM home_visits h JOIN patients p ON p.id = h.patient_id WHERE h.visit_date BETWEEN ? AND ? ORDER BY h.visit_date DESC", 'date' => true],
'vaccinations' => ['title' => 'Vaccination Report', 'sql' => "SELECT p.patient_no AS 'Patient ID', p.full_name AS 'Patient', v.vaccine AS Vaccine, v.dose AS Dose, v.vaccination_date AS Date, v.next_date AS 'Next Date' FROM vaccinations v JOIN patients p ON p.id = v.patient_id WHERE v.vaccination_date BETWEEN ? AND ? ORDER BY v.vaccination_date DESC", 'date' => true],
'medicines' => ['title' => 'Medicine Distribution Report', 'sql' => "SELECT p.patient_no AS 'Patient ID', p.full_name AS 'Patient', m.medicine_name AS Medicine, m.quantity AS Quantity, m.distribution_date AS Date, m.purpose AS Purpose FROM medicines m JOIN patients p ON p.id = m.patient_id WHERE m.distribution_date BETWEEN ? AND ? ORDER BY m.distribution_date DESC", 'date' => true],
'followups' => ['title' => 'Follow-up Report', 'sql' => "SELECT p.patient_no AS 'Patient ID', p.full_name AS 'Patient', f.followup_date AS Date, f.reason AS Reason, CASE WHEN f.status = 'Pending' AND f.followup_date < CURDATE() THEN 'Overdue' ELSE f.status END AS Status, f.notes AS Notes FROM followups f JOIN patients p ON p.id = f.patient_id ORDER BY f.followup_date ASC", 'date' => false]
][$report];

$stmt = $conn->prepare($config['sql']);
if ($config['date']) $stmt->bind_param('ss', $date_from, $date_to);
$stmt->execute();
$rows = $stmt->get_result();

if (isset($_GET['export']) && $_GET['export'] === 'csv') {
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $report . '-report.csv"');
$output = fopen('php://output', 'w');
$fields = $rows->fetch_fields();
fputcsv($output, array_map(fn($field) => $field->name, $fields));
while ($row = $rows->fetch_assoc()) fputcsv($output, $row);
fclose($output);
exit;
}
include "includes/header.php";
include "includes/sidebar.php";
?>
<div class="p-4">
    <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
            <h2 class="fw-bold mb-1">Reports</h2>
            <p class="text-muted mb-0">Generate printable health activity reports</p>
        </div>
    <button class="btn btn-outline-secondary" onclick="window.print()">
        <i class="bi bi-printer me-1">
        </i>Print</button>
</div>
<div class="card shadow-sm mb-4">
    <div class="card-body">
        <form class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label">Report</label>
                <select name="report" class="form-select">
                    <option value="patients" <?php echo $report === 'patients' ? 'selected' : ''; ?>>Patients</option>
                    <option value="consultations" <?php echo $report === 'consultations' ? 'selected' : ''; ?>>Consultations</option>
                    <option value="home_visits" <?php echo $report === 'home_visits' ? 'selected' : ''; ?>>Home Visits</option>
                    <option value="vaccinations" <?php echo $report === 'vaccinations' ? 'selected' : ''; ?>>Vaccinations</option>
                    <option value="medicines" <?php echo $report === 'medicines' ? 'selected' : ''; ?>>Medicine Distribution</option>
                    <option value="followups" <?php echo $report === 'followups' ? 'selected' : ''; ?>>Follow-ups</option>
                </select>
        </div>
    <div class="col-md-3">
        <label class="form-label">From</label>
        <input type="date" name="date_from" class="form-control" value="<?php echo e($date_from); ?>">
    </div>
<div class="col-md-3">
    <label class="form-label">To</label>
    <input type="date" name="date_to" class="form-control" value="<?php echo e($date_to); ?>">
</div>
<div class="col-auto">
    <button class="btn btn-success">
        <i class="bi bi-file-earmark-bar-graph me-1">
        </i>Generate</button>
</div>
<div class="col-auto">
    <a class="btn btn-outline-primary" href="reports.php?report=<?php echo e($report); ?>&date_from=<?php echo e($date_from); ?>&date_to=<?php echo e($date_to); ?>&export=csv">
        <i class="bi bi-download me-1">
        </i>CSV</a>
</div>
</form>
</div>
</div>
<div class="card shadow-sm">
    <div class="card-body">
        <h5 class="mb-3">
            <?php echo e($config['title']); ?>
            <span class="badge bg-light text-dark">
                <?php echo $rows->num_rows; ?> records</span>
        </h5>
    <div class="table-responsive">
        <table class="table table-striped">
            <thead>
                <tr>
                    <?php foreach ($rows->fetch_fields() as $field): ?>
                    <th>
                        <?php echo e($field->name); ?>
                    </th>
            <?php endforeach; ?>
        </tr>
</thead>
<tbody>
    <?php while ($row = $rows->fetch_assoc()): ?>
    <tr>
        <?php foreach ($row as $value): ?>
        <td>
            <?php echo e((string)$value); ?>
        </td>
<?php endforeach; ?>
</tr>
<?php endwhile; if ($rows->num_rows === 0): ?>
<tr>
    <td colspan="8" class="text-center text-muted py-4">No records for this report.</td>
</tr>
<?php endif; ?>
</tbody>
</table>
</div>
</div>
</div>
</div>
<?php include "includes/footer.php"; ?>
