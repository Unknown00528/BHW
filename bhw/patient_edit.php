<?php
require_once "config.php";
require_once "includes/auth.php";
require_login();

$patient_id = (int)($_GET['id'] ?? 0);
$stmt = $conn->prepare("SELECT * FROM patients WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$patient = $stmt->get_result()->fetch_assoc();
if (!$patient) { http_response_code(404); exit('Patient not found.'); }

$error = '';
if (isset($_POST['update_patient'])) {
verify_csrf();
$full_name = trim($_POST['full_name'] ?? '');
$age = (int)($_POST['age'] ?? 0);
$sex = trim($_POST['sex'] ?? '');
$address = trim($_POST['address'] ?? '');
$contact = trim($_POST['contact'] ?? '');
if ($full_name === '' || $age < 0 || $sex === '') {
$error = 'Please complete all required fields.';
} else {
$update = $conn->prepare("UPDATE patients SET full_name = ?, age = ?, sex = ?, address = ?, contact = ? WHERE id = ?");
$update->bind_param("sisssi", $full_name, $age, $sex, $address, $contact, $patient_id);
$update->execute();
header("Location: patient_profile.php?id={$patient_id}");
exit;
}
}
include "includes/header.php";
include "includes/sidebar.php";
?>
<div class="p-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <a href="patient_profile.php?id=<?php echo $patient_id; ?>" class="text-success text-decoration-none">
                <i class="bi bi-arrow-left me-1">
                </i>Back to profile</a>
        <h2 class="fw-bold mt-2">Edit Patient</h2>
        <div class="card shadow-sm mt-3">
            <div class="card-body p-4">
                <?php if ($error): ?>
                <div class="alert alert-danger">
                    <?php echo e($error); ?>
                </div>
        <?php endif; ?>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
            <div class="mb-3">
                <label class="form-label">Patient Number</label>
                <input class="form-control" value="<?php echo e($patient['patient_no']); ?>" readonly>
            </div>
        <div class="mb-3">
            <label class="form-label">Full Name <span class="text-danger">*</span>
            </label>
        <input name="full_name" class="form-control" value="<?php echo e($_POST['full_name'] ?? $patient['full_name']); ?>" required>
    </div>
<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label">Age <span class="text-danger">*</span>
        </label>
    <input type="number" name="age" min="0" class="form-control" value="<?php echo e($_POST['age'] ?? (string)$patient['age']); ?>" required>
</div>
<div class="col-md-6 mb-3">
    <label class="form-label">Sex <span class="text-danger">*</span>
    </label>
<select name="sex" class="form-select" required>
    <option value="">Select</option>
    <option <?php echo ($_POST['sex'] ?? $patient['sex']) === 'Male' ? 'selected' : ''; ?>>Male</option>
    <option <?php echo ($_POST['sex'] ?? $patient['sex']) === 'Female' ? 'selected' : ''; ?>>Female</option>
</select>
</div>
</div>
<div class="mb-3">
    <label class="form-label">Address</label>
    <input name="address" class="form-control" value="<?php echo e($_POST['address'] ?? $patient['address']); ?>">
</div>
<div class="mb-4">
    <label class="form-label">Contact Number</label>
    <input name="contact" class="form-control" value="<?php echo e($_POST['contact'] ?? $patient['contact']); ?>">
</div>
<div class="d-flex gap-2">
    <a href="patient_profile.php?id=<?php echo $patient_id; ?>" class="btn btn-light">Cancel</a>
    <button name="update_patient" class="btn btn-success">
        <i class="bi bi-save me-1">
        </i>Save Changes</button>
</div>
</form>
</div>
</div>
</div>
</div>
</div>
<?php include "includes/footer.php"; ?>
