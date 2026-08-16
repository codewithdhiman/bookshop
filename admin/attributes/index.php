<?php
require_once __DIR__ . '/../../includes/admin_auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/admin_header.php';

require_admin();

$pdo = getPDO();

$stmt = $pdo->query("
    SELECT
        a.*,
        COUNT(DISTINCT ca.category_id) AS category_count
    FROM attributes a
    LEFT JOIN category_attributes ca
        ON ca.attribute_id = a.id
    GROUP BY a.id
    ORDER BY a.created_at DESC
");

$attributes = $stmt->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h3 class="mb-1">Product Attributes</h3>
        <div class="text-muted small">
            Manage dynamic attributes used by different product categories.
        </div>
    </div>

    <a href="create.php" class="btn btn-primary">
        Add Attribute
    </a>
</div>

<div class="card admin-card">
    <div class="table-responsive">
        <table class="table align-middle mb-0">

            <thead>
                <tr>
                    <th>ID</th>
                    <th>Attribute</th>
                    <th>Type</th>
                    <th>Required</th>
                    <th>Categories</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>

            <tbody>

            <?php if (!$attributes): ?>

                <tr>
                    <td colspan="7" class="text-center py-5 text-muted">
                        No attributes found.
                    </td>
                </tr>

            <?php else: ?>

                <?php foreach ($attributes as $attribute): ?>

                    <tr>

                        <td>
                            <?= (int)$attribute['id'] ?>
                        </td>

                        <td>
                            <div class="fw-semibold">
                                <?= esc($attribute['name']) ?>
                            </div>

                            <div class="small text-muted">
                                <?= esc($attribute['slug']) ?>
                            </div>
                        </td>

                        <td>
                            <span class="badge bg-light text-dark border">
                                <?= esc(ucfirst($attribute['type'])) ?>
                            </span>
                        </td>

                        <td>
                            <?php if ($attribute['is_required']): ?>
                                <span class="badge bg-warning text-dark">
                                    Required
                                </span>
                            <?php else: ?>
                                <span class="text-muted">
                                    Optional
                                </span>
                            <?php endif; ?>
                        </td>

                        <td>
                            <?= (int)$attribute['category_count'] ?>
                        </td>

                        <td>
                            <?php if ($attribute['is_active']): ?>

                                <span class="badge"
                                      style="background:var(--success);color:#fff;">
                                    Active
                                </span>

                            <?php else: ?>

                                <span class="badge bg-secondary">
                                    Inactive
                                </span>

                            <?php endif; ?>
                        </td>

                        <td class="text-end">

                            <div class="btn-group">

                                <a
                                    href="edit.php?id=<?= (int)$attribute['id'] ?>"
                                    class="btn btn-sm btn-outline-secondary">
                                    Edit
                                </a>

                                <a
                                    href="delete.php?id=<?= (int)$attribute['id'] ?>"
                                    class="btn btn-sm <?= $attribute['is_active']
                                        ? 'btn-outline-danger'
                                        : 'btn-outline-success' ?>"
                                    onclick="return confirm('Are you sure?')">

                                    <?= $attribute['is_active']
                                        ? 'Deactivate'
                                        : 'Activate' ?>

                                </a>

                            </div>

                        </td>

                    </tr>

                <?php endforeach; ?>

            <?php endif; ?>

            </tbody>

        </table>
    </div>
</div>