<?php
/** @var int $editPatientId */
/** @var Closure $escape */
/** @var array<int, string> $genderOptions */
/** @var array<string, mixed> $patientFormValues */
/** @var string $patientModalTitle */
/** @var array<string, mixed> $patientPageParams */
/** @var string $patientSearch */
/** @var string $patientSubmitAction */
/** @var string $patientSubmitLabel */
/** @var bool $showEditPatientModal */
?>

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
