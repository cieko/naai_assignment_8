<?php

/** @var mysqli $conn */

handleVisitActions($conn);

$visitFormData = pullSessionFormData('visit_form_data');
$flash = pullFlashMessage('visit_flash');
$visits = getAllVisitRecords($conn);
$patients = getAllPatients($conn);
$doctors = getVisitDoctors($conn);

$showAddVisitModal = (($_GET['add'] ?? '') === 'new');
$editVisitId = (int)($_GET['edit'] ?? 0);
$deleteVisitId = (int)($_GET['delete_visit'] ?? 0);

$editingVisit = $editVisitId > 0
    ? getVisitRecordById($conn, $editVisitId)
    : null;
$deletingVisit = $deleteVisitId > 0
    ? getVisitRecordById($conn, $deleteVisitId)
    : null;

$showEditVisitModal = $editVisitId > 0 && $editingVisit !== null;
$showVisitFormModal = $showAddVisitModal || $showEditVisitModal;
$showDeleteVisitModal = $deleteVisitId > 0 && $deletingVisit !== null;

if ($editVisitId > 0 && $editingVisit === null) {
    $flash ??= [
        'type' => 'error',
        'message' => 'The selected visit record could not be found.',
    ];
}

if ($deleteVisitId > 0 && $deletingVisit === null) {
    $flash ??= [
        'type' => 'error',
        'message' => 'The selected visit record could not be found.',
    ];
}

$visitFormValues = [
    'patient_id' => (int)($visitFormData['patient_id']
        ?? ($editingVisit['patient_id'] ?? 0)),
    'doctor_id' => (int)($visitFormData['doctor_id']
        ?? ($editingVisit['doctor_id'] ?? 0)),
    'admission_date' => $visitFormData['admission_date']
        ?? ($editingVisit['admission_date'] ?? ''),
    'discharge_date' => $visitFormData['discharge_date']
        ?? ($editingVisit['discharge_date'] ?? ''),
    'bed_number' => $visitFormData['bed_number']
        ?? ($editingVisit['bed_number'] ?? ''),
    'visit_date' => $visitFormData['visit_date']
        ?? ($editingVisit['visit_date'] ?? ''),
    'diagnosis' => $visitFormData['diagnosis']
        ?? ($editingVisit['diagnosis'] ?? ''),
    'notes' => $visitFormData['notes']
        ?? ($editingVisit['notes'] ?? ''),
    'amount' => $visitFormData['amount']
        ?? ($editingVisit !== null
            ? number_format((float)$editingVisit['amount'], 2, '.', '')
            : ''),
    'payment_status' => $visitFormData['payment_status']
        ?? ($editingVisit['payment_status'] ?? 'Pending'),
    'payment_date' => $visitFormData['payment_date']
        ?? ($editingVisit['payment_date'] ?? ''),
];

$visitModalTitle = $showEditVisitModal
    ? 'Edit Visit'
    : 'Add New Visit';

$visitSubmitAction = $showEditVisitModal
    ? 'update_visit'
    : 'create_visit';

$visitSubmitLabel = $showEditVisitModal
    ? 'Update Visit'
    : 'Save Visit';

$paymentStatuses = ['Pending', 'Partial', 'Paid'];

$escape = static function ($value): string {
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        'UTF-8'
    );
};

?>

<?php include __DIR__ . '/../flash.php'; ?>

<?php if ($showVisitFormModal): ?>

    <div class="modal-backdrop">
        <div class="modal">
            <div class="modal-header">
                <h3><?= $escape($visitModalTitle) ?></h3>

                <a
                    href="<?= $escape(pageUrl([], 'visits')) ?>"
                    class="modal-close">
                    <i class="ri-close-line"></i>
                </a>
            </div>

            <form
                action=""
                method="post"
                class="modal-form">

                <?php if ($showEditVisitModal): ?>

                    <input
                        type="hidden"
                        name="admission_id"
                        value="<?= $editVisitId ?>">

                <?php endif; ?>

                <div class="form-group">
                    <label for="patient_id">Patient</label>

                    <select
                        id="patient_id"
                        name="patient_id"
                        required>

                        <option value="">Select Patient</option>

                        <?php foreach ($patients as $patient): ?>

                            <option
                                value="<?= (int)$patient['patient_id'] ?>"
                                <?= (int)$visitFormValues['patient_id'] === (int)$patient['patient_id'] ? 'selected' : '' ?>>
                                <?= $escape($patient['patient_name']) ?>
                            </option>

                        <?php endforeach; ?>
                    </select>

                    <?php if (empty($patients)): ?>

                        <p class="field-note">
                            Add patients first before saving a visit record.
                        </p>

                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="doctor_id">Doctor</label>

                    <select
                        id="doctor_id"
                        name="doctor_id"
                        required>

                        <option value="">Select Doctor</option>

                        <?php foreach ($doctors as $doctor): ?>

                            <option
                                value="<?= (int)$doctor['doctor_id'] ?>"
                                <?= (int)$visitFormValues['doctor_id'] === (int)$doctor['doctor_id'] ? 'selected' : '' ?>>
                                <?= $escape($doctor['doctor_name']) ?>
                                -
                                <?= $escape($doctor['department_name']) ?>
                            </option>

                        <?php endforeach; ?>
                    </select>

                    <?php if (empty($doctors)): ?>

                        <p class="field-note">
                            Add staff records first before saving a visit record.
                        </p>

                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="admission_date">Admission Date</label>

                    <input
                        id="admission_date"
                        name="admission_date"
                        type="date"
                        value="<?= $escape($visitFormValues['admission_date']) ?>"
                        required>
                </div>

                <div class="form-group">
                    <label for="visit_date">Visit Date</label>

                    <input
                        id="visit_date"
                        name="visit_date"
                        type="date"
                        value="<?= $escape($visitFormValues['visit_date']) ?>"
                        required>
                </div>

                <div class="form-group">
                    <label for="discharge_date">Discharge Date</label>

                    <input
                        id="discharge_date"
                        name="discharge_date"
                        type="date"
                        value="<?= $escape($visitFormValues['discharge_date']) ?>">
                </div>

                <div class="form-group">
                    <label for="bed_number">Bed Number</label>

                    <input
                        id="bed_number"
                        name="bed_number"
                        type="text"
                        value="<?= $escape($visitFormValues['bed_number']) ?>"
                        placeholder="e.g. B-204">
                </div>

                <div class="form-group">
                    <label for="diagnosis">Diagnosis</label>

                    <input
                        id="diagnosis"
                        name="diagnosis"
                        type="text"
                        value="<?= $escape($visitFormValues['diagnosis']) ?>"
                        placeholder="e.g. Viral Fever">
                </div>

                <div class="form-group">
                    <label for="notes">Notes</label>

                    <textarea
                        id="notes"
                        name="notes"
                        rows="4"
                        placeholder="Consultation notes or treatment updates"><?= $escape($visitFormValues['notes']) ?></textarea>
                </div>

                <div class="form-group">
                    <label for="amount">Treatment Cost</label>

                    <input
                        id="amount"
                        name="amount"
                        type="number"
                        min="0"
                        step="0.01"
                        value="<?= $escape($visitFormValues['amount']) ?>"
                        required>
                </div>

                <div class="form-group">
                    <label for="payment_status">Payment Status</label>

                    <select
                        id="payment_status"
                        name="payment_status"
                        required>

                        <?php foreach ($paymentStatuses as $paymentStatus): ?>

                            <option
                                value="<?= $escape($paymentStatus) ?>"
                                <?= $visitFormValues['payment_status'] === $paymentStatus ? 'selected' : '' ?>>
                                <?= $escape($paymentStatus) ?>
                            </option>

                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="payment_date">Payment Date</label>

                    <input
                        id="payment_date"
                        name="payment_date"
                        type="date"
                        value="<?= $escape($visitFormValues['payment_date']) ?>">
                </div>

                <div class="modal-footer">
                    <a
                        href="<?= $escape(pageUrl([], 'visits')) ?>"
                        class="secondary-btn">
                        Cancel
                    </a>

                    <button
                        type="submit"
                        name="<?= $escape($visitSubmitAction) ?>"
                        class="primary-btn">
                        <?= $escape($visitSubmitLabel) ?>
                    </button>
                </div>
            </form>
        </div>
    </div>

<?php endif; ?>

<?php if ($showDeleteVisitModal): ?>

    <div class="modal-backdrop modal-backdrop--stacked">
        <div class="modal modal--confirm">
            <div class="modal-header">
                <h3>Delete Visit</h3>

                <a
                    href="<?= $escape(pageUrl([], 'visits')) ?>"
                    class="modal-close">
                    <i class="ri-close-line"></i>
                </a>
            </div>

            <form
                action=""
                method="post"
                class="modal-form">

                <div class="confirm-copy">
                    <p>
                        Delete the visit record for
                        <strong><?= $escape($deletingVisit['patient_name']) ?></strong>
                        with
                        <strong><?= $escape($deletingVisit['doctor_name']) ?></strong>?
                    </p>
                    <span>This removes the linked admission, visit history, and billing record.</span>
                </div>

                <input
                    type="hidden"
                    name="admission_id"
                    value="<?= $deleteVisitId ?>">

                <div class="modal-footer">
                    <a
                        href="<?= $escape(pageUrl([], 'visits')) ?>"
                        class="secondary-btn">
                        Cancel
                    </a>

                    <button
                        type="submit"
                        name="delete_visit"
                        class="danger-btn">
                        Delete Visit
                    </button>
                </div>
            </form>
        </div>
    </div>

<?php endif; ?>

<section class="staff">
    <div class="staff-header">
        <div class="headline">
            <h2>Visit Management</h2>
            <p>Track admissions, consultations, discharge details, and billing records.</p>
        </div>

        <a
            href="<?= $escape(pageUrl(['add' => 'new'], 'visits')) ?>"
            class="staff-add-btn">
            Add New Visit
        </a>
    </div>

    <div class="staff-card">
        <table class="staff-table staff-table--wide">
            <thead>
                <tr>
                    <th>Patient</th>
                    <th>Doctor</th>
                    <th>Department</th>
                    <th>Admission Date</th>
                    <th>Discharge Date</th>
                    <th>Treatment Cost</th>
                    <th>Payment Status</th>
                    <th>Actions</th>
                </tr>
            </thead>

            <tbody>

                <?php if (empty($visits)): ?>

                    <tr>
                        <td
                            colspan="8"
                            class="staff-empty">
                            No visit records have been added yet.
                        </td>
                    </tr>

                <?php else: ?>

                    <?php foreach ($visits as $visit): ?>

                        <tr>
                            <td><?= $escape($visit['patient_name']) ?></td>
                            <td><?= $escape($visit['doctor_name']) ?></td>
                            <td><?= $escape($visit['department_name']) ?></td>
                            <td><?= $escape($visit['admission_date']) ?></td>
                            <td><?= $escape($visit['discharge_date'] ?: 'Admitted') ?></td>
                            <td>Rs. <?= $escape(number_format((float)$visit['amount'], 2)) ?></td>
                            <td><?= $escape($visit['payment_status']) ?></td>

                            <td>
                                <div class="staff-actions">
                                    <a
                                        href="<?= $escape(pageUrl(['edit' => (int)$visit['admission_id']], 'visits')) ?>"
                                        class="staff-action-btn staff-edit"
                                        aria-label="Edit visit for <?= $escape($visit['patient_name']) ?>">
                                        <i class="ri-edit-box-line"></i>
                                    </a>

                                    <a
                                        href="<?= $escape(pageUrl(['delete_visit' => (int)$visit['admission_id']], 'visits')) ?>"
                                        class="staff-action-btn staff-delete"
                                        aria-label="Delete visit for <?= $escape($visit['patient_name']) ?>">
                                        <i class="ri-delete-bin-line"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>

                    <?php endforeach; ?>

                <?php endif; ?>

            </tbody>
        </table>
    </div>
</section>
