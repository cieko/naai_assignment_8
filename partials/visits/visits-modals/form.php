<?php
/** @var array<int, array<string, mixed>> $doctors */
/** @var int $editVisitId */
/** @var Closure $escape */
/** @var array<int, array<string, mixed>> $patients */
/** @var array<int, string> $paymentStatuses */
/** @var bool $showEditVisitModal */
/** @var array<string, mixed> $visitFormValues */
/** @var string $visitModalTitle */
/** @var string $visitSubmitAction */
/** @var string $visitSubmitLabel */
?>

<div class="modal-backdrop">
    <div
        class="modal"
        role="dialog"
        aria-modal="true"
        aria-labelledby="visit-form-title">
        <div class="modal-header">
            <h3 id="visit-form-title"><?= $escape($visitModalTitle) ?></h3>

            <a
                href="<?= $escape(pageUrl([], 'visits')) ?>"
                class="modal-close"
                aria-label="Close visit form">
                <i class="ri-close-line" aria-hidden="true"></i>
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
                    inputmode="decimal"
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
