<?php
/** @var int $deletePatientId */
/** @var array<string, mixed> $deletingPatient */
/** @var Closure $escape */
/** @var array<string, mixed> $patientPageParams */
/** @var string $patientSearch */
?>

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
