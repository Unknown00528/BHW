<?php
require_once "config.php";
require_once "includes/auth.php";
require_login();

if (isset($_POST['add'])) {
verify_csrf();
$stmt = $conn->prepare("INSERT INTO consultations (patient_id, consultation_date, complaint, assessment, action_taken, followup_date) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->bind_param("isssss", $_POST['patient_id'], $_POST['consultation_date'], $_POST['complaint'], $_POST['assessment'], $_POST['action_taken'], $_POST['followup_date']);
$stmt->execute();
header("Location: consultations.php"); exit;
}
$patients = $conn->query("SELECT id, full_name FROM patients ORDER BY full_name");
$rows = $conn->query("SELECT c.*, p.full_name FROM consultations c JOIN patients p ON p.id=c.patient_id ORDER BY c.id DESC");
include "includes/header.php"; include "includes/sidebar.php";
?>
<div class="p-4">
    <div class="d-flex justify-content-between mb-3">
        <h2>Consultations</h2>
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#add">New Consultation</button>
    </div>
<div class="card shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Patient</th>
                        <th>Date</th>
                        <th>Complaint</th>
                        <th>Assessment</th>
                        <th>Follow-up</th>
                    </tr>
            </thead>
        <tbody>
            <?php while($r=$rows->fetch_assoc()): ?>
            <tr>
                <td>
                    <?php echo htmlspecialchars($r['full_name']); ?>
                </td>
            <td>
                <?php echo $r['consultation_date']; ?>
            </td>
        <td>
            <?php echo htmlspecialchars($r['complaint']); ?>
        </td>
    <td>
        <?php echo htmlspecialchars($r['assessment']); ?>
    </td>
<td>
    <?php echo $r['followup_date']; ?>
</td>
</tr>
<?php endwhile; ?>
</tbody>
</table>
</div>
</div>
</div>
</div>
<div class="modal fade" id="add">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5>New Consultation</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal">
                    </button>
            </div>
        <div class="modal-body">
            <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
            <div class="mb-3">
                <label>Patient</label>
                <select name="patient_id" class="form-select" required>
                    <option value="">Select patient</option>
                    <?php while($p=$patients->fetch_assoc()): ?>
                    <option value="<?php echo $p['id']; ?>">
                        <?php echo htmlspecialchars($p['full_name']); ?>
                    </option>
            <?php endwhile; ?>
        </select>
</div>
<div class="mb-3">
    <label>Date</label>
    <input type="date" name="consultation_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
</div>
<div class="mb-3">
    <label>Complaint</label>
    <textarea name="complaint" class="form-control">
    </textarea>
</div>
<div class="mb-3">
    <label>Assessment</label>
    <textarea name="assessment" class="form-control">
    </textarea>
</div>
<div class="mb-3">
    <label>Action Taken</label>
    <textarea name="action_taken" class="form-control">
    </textarea>
</div>
<div class="mb-3">
    <label>Follow-up Date</label>
    <input type="date" name="followup_date" class="form-control">
</div>
</div>
<div class="modal-footer">
    <button name="add" class="btn btn-success">Save</button>
</div>
</form>
</div>
</div>
</div>
<?php include "includes/footer.php"; ?>
