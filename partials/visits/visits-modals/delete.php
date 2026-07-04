<?php
/** @var int $deleteVisitId */
/** @var array<string, mixed> $deletingVisit */
/** @var Closure $escape */
?>

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
