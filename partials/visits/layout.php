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
    <?php include __DIR__ . '/visits-modals/form.php'; ?>

<?php endif; ?>

<?php if ($showDeleteVisitModal): ?>
    <?php include __DIR__ . '/visits-modals/delete.php'; ?>

<?php endif; ?>

<?php include __DIR__ . '/visits-main/management.php'; ?>
