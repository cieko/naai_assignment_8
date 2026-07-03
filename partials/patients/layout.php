<?php

/** @var mysqli $conn */

handlePatientActions($conn);

$patientFormData = pullSessionFormData('patient_form_data');
$flash = pullFlashMessage('patient_flash');
$patientSearch = getSearchTerm('search');
$patients = getAllPatients($conn, $patientSearch);

$showAddPatientModal = (($_GET['add'] ?? '') === 'new');
$editPatientId = (int)($_GET['edit'] ?? 0);
$deletePatientId = (int)($_GET['delete_patient'] ?? 0);

$editingPatient = $editPatientId > 0
    ? getPatientById($conn, $editPatientId)
    : null;
$deletingPatient = $deletePatientId > 0
    ? getPatientById($conn, $deletePatientId)
    : null;

$showEditPatientModal = $editPatientId > 0 && $editingPatient !== null;
$showPatientFormModal = $showAddPatientModal || $showEditPatientModal;
$showDeletePatientModal = $deletePatientId > 0 && $deletingPatient !== null;

if ($editPatientId > 0 && $editingPatient === null) {
    $flash ??= [
        'type' => 'error',
        'message' => 'The selected patient could not be found.',
    ];
}

if ($deletePatientId > 0 && $deletingPatient === null) {
    $flash ??= [
        'type' => 'error',
        'message' => 'The selected patient could not be found.',
    ];
}

$patientFormValues = [
    'patient_name' => $patientFormData['patient_name']
        ?? ($editingPatient['patient_name'] ?? ''),
    'age' => $patientFormData['age']
        ?? ($editingPatient['age'] ?? ''),
    'gender' => $patientFormData['gender']
        ?? ($editingPatient['gender'] ?? ''),
    'disease' => $patientFormData['disease']
        ?? ($editingPatient['disease'] ?? ''),
];

$patientModalTitle = $showEditPatientModal
    ? 'Edit Patient'
    : 'Add New Patient';

$patientSubmitAction = $showEditPatientModal
    ? 'update_patient'
    : 'create_patient';

$patientSubmitLabel = $showEditPatientModal
    ? 'Update Patient'
    : 'Save Patient';

$escape = static function ($value): string {
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        'UTF-8'
    );
};

$genderOptions = ['Male', 'Female', 'Other'];
$patientPageParams = $patientSearch !== ''
    ? ['search' => $patientSearch]
    : [];

?>

<?php include __DIR__ . '/../flash.php'; ?>

<?php if ($showPatientFormModal): ?>

    <div class="modal-backdrop">
        <div class="modal">
            <div class="modal-header">
                <h3><?= $escape($patientModalTitle) ?></h3>

                <a
                    href="<?= $escape(pageUrl($patientPageParams, 'patients')) ?>"
                    class="modal-close">
                    <i class="ri-close-line"></i>
                </a>
            </div>

            <form
                action=""
                method="post"
                class="modal-form">

                <?php if ($showEditPatientModal): ?>

                    <input
                        type="hidden"
                        name="patient_id"
                        value="<?= $editPatientId ?>">

                <?php endif; ?>

                <?php if ($patientSearch !== ''): ?>

                    <input
                        type="hidden"
                        name="search"
                        value="<?= $escape($patientSearch) ?>">

                <?php endif; ?>

                <div class="form-group">
                    <label for="patient_name">Patient Name</label>

                    <input
                        id="patient_name"
                        name="patient_name"
                        type="text"
                        value="<?= $escape($patientFormValues['patient_name']) ?>"
                        required>
                </div>

                <div class="form-group">
                    <label for="age">Age</label>

                    <input
                        id="age"
                        name="age"
                        type="number"
                        min="0"
                        max="150"
                        value="<?= $escape($patientFormValues['age']) ?>"
                        required>
                </div>

                <div class="form-group">
                    <label for="gender">Gender</label>

                    <select
                        id="gender"
                        name="gender"
                        required>

                        <option value="">Select Gender</option>

                        <?php foreach ($genderOptions as $gender): ?>

                            <option
                                value="<?= $escape($gender) ?>"
                                <?= $patientFormValues['gender'] === $gender ? 'selected' : '' ?>>
                                <?= $escape($gender) ?>
                            </option>

                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="disease">Disease</label>

                    <input
                        id="disease"
                        name="disease"
                        type="text"
                        value="<?= $escape($patientFormValues['disease']) ?>"
                        placeholder="e.g. Hypertension">
                </div>

                <div class="modal-footer">
                    <a
                        href="<?= $escape(pageUrl($patientPageParams, 'patients')) ?>"
                        class="secondary-btn">
                        Cancel
                    </a>

                    <button
                        type="submit"
                        name="<?= $escape($patientSubmitAction) ?>"
                        class="primary-btn">
                        <?= $escape($patientSubmitLabel) ?>
                    </button>
                </div>
            </form>
        </div>
    </div>

<?php endif; ?>

<?php if ($showDeletePatientModal): ?>

    <div class="modal-backdrop modal-backdrop--stacked">
        <div class="modal modal--confirm">
            <div class="modal-header">
                <h3>Delete Patient</h3>

                <a
                    href="<?= $escape(pageUrl($patientPageParams, 'patients')) ?>"
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
                        Delete
                        <strong><?= $escape($deletingPatient['patient_name']) ?></strong>
                        from the patient list?
                    </p>
                    <span>
                        This also removes related admissions, visit history, and billing records.
                    </span>
                </div>

                <input
                    type="hidden"
                    name="patient_id"
                    value="<?= $deletePatientId ?>">

                <?php if ($patientSearch !== ''): ?>

                    <input
                        type="hidden"
                        name="search"
                        value="<?= $escape($patientSearch) ?>">

                <?php endif; ?>

                <div class="modal-footer">
                    <a
                        href="<?= $escape(pageUrl($patientPageParams, 'patients')) ?>"
                        class="secondary-btn">
                        Cancel
                    </a>

                    <button
                        type="submit"
                        name="delete_patient"
                        class="danger-btn">
                        Delete Patient
                    </button>
                </div>
            </form>
        </div>
    </div>

<?php endif; ?>

<section class="staff">
    <div class="staff-header">
        <div class="headline">
            <h2>Patient Management</h2>
            <p>View and manage all registered patients and their primary disease records.</p>
        </div>

        <a
            href="<?= $escape(pageUrl(array_merge($patientPageParams, ['add' => 'new']), 'patients')) ?>"
            class="staff-add-btn">
            Add New Patient
        </a>
    </div>

    <div class="staff-card">
        <table class="staff-table">
            <thead>
                <tr>
                    <th>Patient Name</th>
                    <th>Age</th>
                    <th>Gender</th>
                    <th>Disease</th>
                    <th>Actions</th>
                </tr>
            </thead>

            <tbody>

                <?php if (empty($patients)): ?>

                    <tr>
                        <td
                            colspan="6"
                            class="staff-empty">
                            <?= $patientSearch !== ''
                                ? 'No patients matched "' . $escape($patientSearch) . '".'
                                : 'No patients have been added yet.' ?>
                        </td>
                    </tr>

                <?php else: ?>

                    <?php foreach ($patients as $patient): ?>

                        <tr>
                            <td><?= $escape($patient['patient_name']) ?></td>
                            <td><?= (int)$patient['age'] ?></td>
                            <td><?= $escape($patient['gender']) ?></td>
                            <td><?= $escape($patient['disease'] ?: 'Not set') ?></td>

                            <td>
                                <div class="staff-actions">
                                    <a
                                        href="<?= $escape(pageUrl(array_merge($patientPageParams, ['edit' => (int)$patient['patient_id']]), 'patients')) ?>"
                                        class="staff-action-btn staff-edit"
                                        aria-label="Edit <?= $escape($patient['patient_name']) ?>">
                                        <i class="ri-edit-box-line"></i>
                                    </a>

                                    <a
                                        href="<?= $escape(pageUrl(array_merge($patientPageParams, ['delete_patient' => (int)$patient['patient_id']]), 'patients')) ?>"
                                        class="staff-action-btn staff-delete"
                                        aria-label="Delete <?= $escape($patient['patient_name']) ?>">
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
