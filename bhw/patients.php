<?php
require_once "config.php";
require_once "includes/auth.php";
require_login();

$message = '';
$error = '';
if (!empty($_SESSION['flash_message'])) {
$message = $_SESSION['flash_message'];
unset($_SESSION['flash_message']);
}
if (!empty($_SESSION['flash_error'])) {
$error = $_SESSION['flash_error'];
unset($_SESSION['flash_error']);
}

if (isset($_POST['add_patient'])) {
verify_csrf();
$full_name = trim($_POST['full_name'] ?? '');
$age = (int)$_POST['age'];
$sex = trim($_POST['sex'] ?? '');
$address = trim($_POST['address'] ?? '');
$contact = trim($_POST['contact'] ?? '');

$duplicate = $conn->prepare("SELECT id FROM patients WHERE LOWER(full_name) = LOWER(?) AND age = ? AND COALESCE(address, '') = ? LIMIT 1");
$duplicate->bind_param("sis", $full_name, $age, $address);
$duplicate->execute();
if ($duplicate->get_result()->num_rows > 0) {
$error = 'A patient with the same name, age, and address already exists.';
} else {
$conn->begin_transaction();
try {
$temporary_patient_no = uniqid('TEMP-', true);
$stmt = $conn->prepare("INSERT INTO patients (patient_no, full_name, age, sex, address, contact) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->bind_param("ssisss", $temporary_patient_no, $full_name, $age, $sex, $address, $contact);
$stmt->execute();

$patient_id = $conn->insert_id;
$patient_no = sprintf('PAT-%06d', $patient_id);
$update_stmt = $conn->prepare("UPDATE patients SET patient_no = ? WHERE id = ?");
$update_stmt->bind_param("si", $patient_no, $patient_id);
$update_stmt->execute();

$conn->commit();
$message = 'Patient successfully registered.';
} catch (Throwable $exception) {
$conn->rollback();
$error = 'Unable to save patient record.';
}
}
}

$search = trim($_GET['search'] ?? '');
$like = "%{$search}%";
$stmt = $conn->prepare("SELECT * FROM patients WHERE full_name LIKE ? OR patient_no LIKE ? OR contact LIKE ? ORDER BY id DESC");
$stmt->bind_param("sss", $like, $like, $like);
$stmt->execute();
$patients = $stmt->get_result();
include "includes/header.php";
include "includes/sidebar.php";
?>
<div class="p-4">
    <div class="d-flex justify-content-between mb-3">
        <div>
            <h2 class="fw-bold mb-1">Patients</h2>
            <p class="text-muted mb-0">Manage the barangay patient registry</p>
        </div>
    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addPatient">
        <i class="bi bi-person-plus">
        </i> Add Patient
</button>
</div>

<?php if ($message): ?>
<div class="alert alert-success">
    <i class="bi bi-check-circle me-2">
    </i>
<?php echo e($message); ?>
</div>
<?php endif; ?>
<?php if ($error): ?>
<div class="alert alert-danger">
    <i class="bi bi-exclamation-circle me-2">
    </i>
<?php echo e($error); ?>
</div>
<?php endif; ?>

<div class="card shadow-sm">
    <div class="card-body">
        <form class="row g-2 mb-3" method="GET">
            <div class="col-md-8">
                <input type="search" name="search" class="form-control" placeholder="Search patient name, ID, or contact" value="<?php echo e($search); ?>">
            </div>
        <div class="col-auto">
            <button class="btn btn-outline-success">
                <i class="bi bi-search">
                </i> Search</button>
    </div>
<div class="col-auto">
    <a href="patients.php" class="btn btn-outline-secondary">Clear</a>
</div>
</form>
<div class="table-responsive">
    <table class="table table-hover">
        <thead>
            <tr>
                <th>Patient ID</th>
                <th>Name</th>
                <th>Age</th>
                <th>Sex</th>
                <th>Address</th>
                <th>Contact</th>
                <th>Action</th>
            </tr>
    </thead>
<tbody>
    <?php while ($row = $patients->fetch_assoc()): ?>
    <tr>
        <td>
            <span class="badge bg-success-subtle text-success">
                <?php echo e($row['patient_no']); ?>
            </span>
    </td>
<td class="fw-semibold">
    <?php echo e($row['full_name']); ?>
</td>
<td>
    <?php echo e($row['age']); ?>
</td>
<td>
    <?php echo e($row['sex']); ?>
</td>
<td>
    <?php echo e($row['address']); ?>
</td>
<td>
    <?php echo e($row['contact']); ?>
</td>
<td class="text-nowrap">
    <a href="patient_profile.php?id=<?php echo (int)$row['id']; ?>" class="btn btn-sm btn-outline-success">
        <i class="bi bi-eye">
        </i>
</a>
<a href="patient_edit.php?id=<?php echo (int)$row['id']; ?>" class="btn btn-sm btn-outline-secondary">
    <i class="bi bi-pencil">
    </i>
</a>
<form method="POST" action="patient_delete.php" class="d-inline">
    <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
    <input type="hidden" name="id" value="<?php echo (int)$row['id']; ?>">
    <button class="btn btn-sm btn-outline-danger" data-confirm="Delete this patient? Patients with linked health records cannot be deleted." aria-label="Delete patient">
        <i class="bi bi-trash">
        </i>
</button>
</form>
</td>
</tr>
<?php endwhile; if ($patients->num_rows === 0): ?>
<tr>
    <td colspan="7" class="text-center text-muted py-4">No patients found.</td>
</tr>
<?php endif; ?>
</tbody>
</table>
</div>
</div>
</div>
</div>

<div class="modal fade" id="addPatient">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
                <div class="modal-header">
                    <h5 class="modal-title">Add Patient</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal">
                    </button>
            </div>
        <div class="modal-body">
            <div class="mb-3">
                <label class="form-label">Patient Number</label>
                <input type="text" class="form-control" value="Auto-generated after saving" readonly>
            </div>
        <div class="mb-3">
            <label class="form-label">Full Name</label>
            <input type="text" name="full_name" class="form-control" required>
        </div>
    <div class="row">
        <div class="col-md-6">
            <label class="form-label">Age</label>
            <input type="number" name="age" class="form-control" required>
        </div>
    <div class="col-md-6">
        <label class="form-label">Sex</label>
        <select name="sex" class="form-select" required>
            <option value="">Select</option>
            <option>Male</option>
            <option>Female</option>
        </select>
</div>
</div>
<div class="mb-3 mt-3">
    <label class="form-label">Address</label>
    <input type="text" name="address" class="form-control">
</div>
<div class="mb-3">
    <label class="form-label">Contact Number</label>
    <input type="text" name="contact" class="form-control">
</div>
</div>
<div class="modal-footer">
    <button type="submit" name="add_patient" class="btn btn-success">Save Patient</button>
</div>
</form>
</div>
</div>
</div>
<?php include "includes/footer.php"; ?>
