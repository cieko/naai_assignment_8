<?php
/** @var int $deleteStaffId */
/** @var array<string, mixed> $deletingStaff */
/** @var Closure $escape */
?>

<div class="modal-backdrop modal-backdrop--stacked">
    <div class="modal modal--confirm">
        <div class="modal-header">
            <h3>Delete Staff</h3>

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

            <div class="confirm-copy">
                <p>
                    Delete
                    <strong><?= $escape($deletingStaff['doctor_name']) ?></strong>
                    from the staff list?
                </p>
                <span>This action cannot be undone.</span>
            </div>

            <input
                type="hidden"
                name="doctor_id"
                value="<?= $deleteStaffId ?>">

            <div class="modal-footer">
                <a
                    href="<?= $escape(pageUrl([], 'staff')) ?>"
                    class="secondary-btn">
                    Cancel
                </a>

                <button
                    type="submit"
                    name="delete_staff"
                    class="danger-btn">
                    Delete Staff
                </button>
            </div>
        </form>
    </div>
</div>
