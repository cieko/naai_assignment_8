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
    <?php include __DIR__ . '/patients-modals/form.php'; ?>

<?php endif; ?>

<?php if ($showDeletePatientModal): ?>
    <?php include __DIR__ . '/patients-modals/delete.php'; ?>

<?php endif; ?>

<?php include __DIR__ . '/patients-main/management.php'; ?>
