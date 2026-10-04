<?php
require_once "config.php";
require_once "includes/auth.php";
require_login();

function count_query(mysqli $conn, string $sql): int
{
return (int)$conn->query($sql)->fetch_assoc()['total'];
}

$patients = count_query($conn, "SELECT COUNT(*) AS total FROM patients");
$consultations = count_query($conn, "SELECT COUNT(*) AS total FROM consultations WHERE consultation_date = CURDATE()");
$visits = count_query($conn, "SELECT COUNT(*) AS total FROM home_visits WHERE visit_date >= DATE_FORMAT(CURDATE(), '%Y-%m-01')");
$vaccinations = count_query($conn, "SELECT COUNT(*) AS total FROM vaccinations WHERE vaccination_date >= DATE_FORMAT(CURDATE(), '%Y-%m-01')");
$medicines = count_query($conn, "SELECT COUNT(*) AS total FROM medicines");
$pending_followups = count_query($conn, "SELECT COUNT(*) AS total FROM followups WHERE status = 'Pending' AND followup_date >= CURDATE()");
$overdue_followups = count_query($conn, "SELECT COUNT(*) AS total FROM followups WHERE status = 'Pending' AND followup_date < CURDATE()");

$monthly_activity = ['labels' => [], 'consultations' => [], 'visits' => [], 'vaccinations' => [], 'medicines' => []];
$activity_result = $conn->query("SELECT DATE_FORMAT(activity_date, '%b %Y') AS month_label, DATE_FORMAT(activity_date, '%Y-%m') AS month_key, SUM(consultations) AS consultations, SUM(visits) AS visits, SUM(vaccinations) AS vaccinations, SUM(medicines) AS medicines FROM (SELECT consultation_date AS activity_date, 1 AS consultations, 0 AS visits, 0 AS vaccinations, 0 AS medicines FROM consultations WHERE consultation_date >= DATE_SUB(CURDATE(), INTERVAL 5 MONTH) UNION ALL SELECT visit_date, 0, 1, 0, 0 FROM home_visits WHERE visit_date >= DATE_SUB(CURDATE(), INTERVAL 5 MONTH) UNION ALL SELECT vaccination_date, 0, 0, 1, 0 FROM vaccinations WHERE vaccination_date >= DATE_SUB(CURDATE(), INTERVAL 5 MONTH) UNION ALL SELECT distribution_date, 0, 0, 0, 1 FROM medicines WHERE distribution_date >= DATE_SUB(CURDATE(), INTERVAL 5 MONTH)) activity GROUP BY month_key, month_label ORDER BY month_key");
while ($activity = $activity_result->fetch_assoc()) {
$monthly_activity['labels'][] = $activity['month_label'];
$monthly_activity['consultations'][] = (int)$activity['consultations'];
$monthly_activity['visits'][] = (int)$activity['visits'];
$monthly_activity['vaccinations'][] = (int)$activity['vaccinations'];
$monthly_activity['medicines'][] = (int)$activity['medicines'];
}

$sex_stats = ['Male' => 0, 'Female' => 0];
$sex_result = $conn->query("SELECT sex, COUNT(*) AS total FROM patients GROUP BY sex");
while ($sex = $sex_result->fetch_assoc()) {
if (isset($sex_stats[$sex['sex']])) $sex_stats[$sex['sex']] = (int)$sex['total'];
}

include "includes/header.php";
include "includes/sidebar.php";
?>
<div class="p-4">
    <div class="d-flex justify-content-between align-items-start mb-4">
        <div>
            <h2 class="fw-bold mb-1">Dashboard</h2>
            <p class="text-muted mb-0">Health center activity overview</p>
        </div>
    <span class="badge bg-success-subtle text-success p-2">
        <?php echo e($_SESSION['role']); ?>
    </span>
</div>

<div class="row g-3">
    <?php
    $cards = [
    ["Total Patients", $patients, "bi-people", "success"], ["Today's Consultations", $consultations, "bi-heart-pulse", "danger"],
    ["Home Visits This Month", $visits, "bi-house", "primary"], ["Vaccinations This Month", $vaccinations, "bi-capsule", "info"],
    ["Medicine Distributions", $medicines, "bi-bandaid", "secondary"], ["Pending Follow-ups", $pending_followups, "bi-calendar-check", "warning"],
    ["Overdue Follow-ups", $overdue_followups, "bi-exclamation-triangle", "danger"]
    ];
    foreach ($cards as $card):
    ?>
    <div class="col-sm-6 col-xl-3">
        <div class="card shadow-sm border-0">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <div class="text-muted">
                        <?php echo $card[0]; ?>
                    </div>
                <h2 class="fw-bold">
                    <?php echo $card[1]; ?>
                </h2>
        </div>
    <i class="bi <?php echo $card[2]; ?> fs-2 text-<?php echo $card[3]; ?>">
    </i>
</div>
</div>
</div>
<?php endforeach; ?>
</div>

<div class="row mt-4">
    <div class="col-md-8">
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0">Health Activities</h5>
                <small class="text-muted">Last six months</small>
            </div>
        <div class="card-body">
            <canvas id="activityChart">
            </canvas>
    </div>
</div>
</div>
<div class="col-md-4">
    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <h5 class="mb-0">Follow-up Alerts</h5>
        </div>
    <div class="card-body">
        <a href="followups.php?status=overdue" class="alert alert-danger d-flex justify-content-between py-2 text-decoration-none">
            <span>
                <i class="bi bi-exclamation-circle me-2">
                </i>Overdue</span>
        <strong>
            <?php echo $overdue_followups; ?>
        </strong>
</a>
<a href="followups.php?status=today" class="alert alert-warning d-flex justify-content-between py-2 text-decoration-none">
    <span>
        <i class="bi bi-calendar-event me-2">
        </i>Due today</span>
<strong>
    <?php echo count_query($conn, "SELECT COUNT(*) AS total FROM followups WHERE status = 'Pending' AND followup_date = CURDATE()"); ?>
</strong>
</a>
<a href="patients.php" class="btn btn-success w-100 mb-2">
    <i class="bi bi-person-plus me-1">
    </i>Add Patient</a>
<a href="consultations.php" class="btn btn-outline-primary w-100">
    <i class="bi bi-plus-circle me-1">
    </i>New Consultation</a>
</div>
</div>
</div>
</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js">
</script>
<script>
    new Chart(document.getElementById('activityChart'), {
    type: 'line',
    data: {
    labels: <?php echo json_encode($monthly_activity['labels']); ?>,
    datasets: [{
    label: 'Consultations', data: <?php echo json_encode($monthly_activity['consultations']); ?>, borderColor: '#198754', tension: .3
    }, {
    label: 'Home Visits', data: <?php echo json_encode($monthly_activity['visits']); ?>, borderColor: '#0d6efd', tension: .3
    }, {
    label: 'Vaccinations', data: <?php echo json_encode($monthly_activity['vaccinations']); ?>, borderColor: '#0dcaf0', tension: .3
    }, {
    label: 'Medicines', data: <?php echo json_encode($monthly_activity['medicines']); ?>, borderColor: '#6c757d', tension: .3
    }]
    },
    options: { responsive: true, maintainAspectRatio: false }
    });
    document.getElementById('activityChart').parentElement.style.height = '320px';
</script>
<?php include "includes/footer.php"; ?>
