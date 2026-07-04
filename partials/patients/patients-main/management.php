<?php
/** @var Closure $escape */
/** @var array<string, mixed> $patientPageParams */
/** @var array<int, array<string, mixed>> $patients */
/** @var string $patientSearch */
?>

<section
    class="staff"
    aria-labelledby="patients-heading">
    <div class="staff-header">
        <div class="headline">
            <h2 id="patients-heading">Patient Management</h2>
            <p>View and manage all registered patients and their primary disease records.</p>
        </div>

        <a
            href="<?= $escape(pageUrl(array_merge($patientPageParams, ['add' => 'new']), 'patients')) ?>"
            class="staff-add-btn">
            Add New Patient
        </a>
    </div>

    <div
        class="staff-card"
        tabindex="0">
        <table class="staff-table">
            <caption class="sr-only">
                Registered patient records with age, gender, disease, and available actions.
            </caption>

            <thead>
                <tr>
                    <th scope="col">Patient Name</th>
                    <th scope="col">Age</th>
                    <th scope="col">Gender</th>
                    <th scope="col">Disease</th>
                    <th scope="col">Actions</th>
                </tr>
            </thead>

            <tbody>

                <?php if (empty($patients)): ?>

                    <tr>
                        <td
                            colspan="5"
                            class="staff-empty">
                            <?= $patientSearch !== ''
                                ? 'No patients matched "' . $escape($patientSearch) . '".'
                                : 'No patients have been added yet.' ?>
                        </td>
                    </tr>

                <?php else: ?>

                    <?php foreach ($patients as $patient): ?>

                        <tr>
                            <th scope="row"><?= $escape($patient['patient_name']) ?></th>
                            <td><?= (int)$patient['age'] ?></td>
                            <td><?= $escape($patient['gender']) ?></td>
                            <td><?= $escape($patient['disease'] ?: 'Not set') ?></td>

                            <td>
                                <div class="staff-actions">
                                    <a
                                        href="<?= $escape(pageUrl(array_merge($patientPageParams, ['edit' => (int)$patient['patient_id']]), 'patients')) ?>"
                                        class="staff-action-btn staff-edit"
                                        aria-label="Edit <?= $escape($patient['patient_name']) ?>">
                                        <i class="ri-edit-box-line" aria-hidden="true"></i>
                                    </a>

                                    <a
                                        href="<?= $escape(pageUrl(array_merge($patientPageParams, ['delete_patient' => (int)$patient['patient_id']]), 'patients')) ?>"
                                        class="staff-action-btn staff-delete"
                                        aria-label="Delete <?= $escape($patient['patient_name']) ?>">
                                        <i class="ri-delete-bin-line" aria-hidden="true"></i>
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
