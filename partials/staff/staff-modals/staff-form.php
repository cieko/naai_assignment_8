<?php
/** @var array<string, mixed> $departments */
/** @var string $departmentModalUrl */
/** @var int $editStaffId */
/** @var Closure $escape */
/** @var bool $showEditStaffModal */
/** @var array<string, mixed> $staffFormValues */
/** @var string $staffModalTitle */
/** @var string $staffSubmitAction */
/** @var string $staffSubmitLabel */
?>

<div class="modal-backdrop">
    <div class="modal">
        <div class="modal-header">
            <h3><?= $escape($staffModalTitle) ?></h3>

            <a
                href="<?= $escape(pageUrl([], 'staff')) ?>"
                class="modal-close">
                <i class="ri-close-line"></i>
            </a>
        </div>

        <form
            action=""
            method="post"
            class="modal-form">

            <?php if ($showEditStaffModal): ?>

                <input
                    type="hidden"
                    name="doctor_id"
                    value="<?= (int)$editStaffId ?>">

            <?php endif; ?>

            <div class="form-group">
                <label for="doctor_name">
                    Doctor Name
                </label>

                <input
                    id="doctor_name"
                    name="doctor_name"
                    type="text"
                    value="<?= $escape($staffFormValues['doctor_name']) ?>"
                    required>
            </div>

            <div class="form-group">
                <label for="department">
                    Department
                </label>

                <div class="inline-field">
                    <select
                        id="department"
                        name="department_id"
                        required>

                        <option value="">
                            Select Department
                        </option>

                        <?php foreach ($departments as $department): ?>

                            <?php
                            $departmentId = (int)$department['department_id'];
                            $isSelected = $departmentId === (int)$staffFormValues['department_id'];
                            ?>

                            <option
                                value="<?= $departmentId ?>"
                                <?= $isSelected ? 'selected' : '' ?>>
                                <?= $escape($department['department_name']) ?>
                            </option>

                        <?php endforeach; ?>
                    </select>

                    <a
                        href="<?= $escape($departmentModalUrl) ?>"
                        class="add-department-link">
                        <i class="ri-add-circle-line"></i>
                        Add Department
                    </a>
                </div>

                <?php if (empty($departments)): ?>

                    <p class="field-note">
                        Create at least one department before saving staff.
                    </p>

                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="specialization">
                    Specialization
                </label>

                <input
                    id="specialization"
                    name="specialization"
                    type="text"
                    value="<?= $escape($staffFormValues['specialization']) ?>">
            </div>

            <div class="form-group">
                <label for="phone">
                    Phone
                </label>

                <input
                    id="phone"
                    name="phone"
                    type="text"
                    value="<?= $escape($staffFormValues['phone']) ?>">
            </div>

            <div class="modal-footer">
                <a
                    href="<?= $escape(pageUrl([], 'staff')) ?>"
                    class="secondary-btn">
                    Cancel
                </a>

                <button
                    type="submit"
                    name="<?= $escape($staffSubmitAction) ?>"
                    class="primary-btn">
                    <?= $escape($staffSubmitLabel) ?>
                </button>
            </div>
        </form>
    </div>
</div>
