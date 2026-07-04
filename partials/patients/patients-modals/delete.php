<?php
/** @var int $deletePatientId */
/** @var array<string, mixed> $deletingPatient */
/** @var Closure $escape */
/** @var array<string, mixed> $patientPageParams */
/** @var string $patientSearch */
?>

<div class="modal-backdrop modal-backdrop--stacked">
    <div
        class="modal modal--confirm"
        role="dialog"
        aria-modal="true"
        aria-labelledby="patient-delete-title"
        aria-describedby="patient-delete-description">
        <div class="modal-header">
            <h3 id="patient-delete-title">Delete Patient</h3>

            <a
                href="<?= $escape(pageUrl($patientPageParams, 'patients')) ?>"
                class="modal-close"
                aria-label="Close delete patient dialog">
                <i class="ri-close-line" aria-hidden="true"></i>
            </a>
        </div>

        <form
            action=""
            method="post"
            class="modal-form">

            <div
                id="patient-delete-description"
                class="confirm-copy">
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
