<?php

require_once __DIR__ . '/../../includes/admin_auth.php';
require_once __DIR__ . '/../../config/database.php';

require_admin();

$pdo = getPDO();

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);

if ($id <= 0) {
    header('Location: index.php');
    exit;
}


/*
|--------------------------------------------------------------------------
| Load category
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT *
    FROM categories
    WHERE id = ?
    LIMIT 1
");

$stmt->execute([$id]);

$category = $stmt->fetch();

if (!$category) {
    header('Location: index.php');
    exit;
}


/*
|--------------------------------------------------------------------------
| Load active attributes
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT
        id,
        name,
        slug,
        type,
        is_required,
        is_active
    FROM attributes
    WHERE is_active = 1
    ORDER BY name ASC
");

$attributes = $stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| Load currently mapped attributes
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        attribute_id,
        sort_order
    FROM category_attributes
    WHERE category_id = ?
    ORDER BY sort_order ASC, id ASC
");

$stmt->execute([$id]);

$currentMappings = $stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| Convert mapping to easy lookup
|--------------------------------------------------------------------------
*/

$mappedAttributes = [];

foreach ($currentMappings as $mapping) {

    $mappedAttributes[
        (int)$mapping['attribute_id']
    ] = (int)$mapping['sort_order'];
}


$errors = [];


/*
|--------------------------------------------------------------------------
| POST
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /*
     * CSRF
     */
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid security token.';
    }


    /*
     * Category fields
     */
    $name = trim($_POST['name'] ?? '');

    $description = trim(
        $_POST['description'] ?? ''
    );

    $status = isset($_POST['status']) ? 1 : 0;


    /*
     * Selected attributes
     *
     * Example:
     *
     * attribute_ids[]
     * attribute_ids[] 
     */
    $selectedAttributes =
        $_POST['attribute_ids'] ?? [];


    /*
     * Convert IDs to integers
     */
    $selectedAttributes = array_map(
        'intval',
        $selectedAttributes
    );

    /*
     * Remove duplicate IDs
     */
    $selectedAttributes =
        array_values(
            array_unique($selectedAttributes)
        );


    /*
     * Validation
     */

    if ($name === '') {
        $errors[] = 'Category name is required.';
    }


    /*
     * Generate slug
     */

    if (empty($errors)) {

        $slug = strtolower($name);

        $slug = preg_replace(
            '/[^a-z0-9]+/i',
            '-',
            $slug
        );

        $slug = trim($slug, '-');

        if ($slug === '') {
            $errors[] =
                'Unable to generate a valid slug.';
        }
    }


    /*
     * Duplicate slug
     */

    if (empty($errors)) {

        $stmt = $pdo->prepare("
            SELECT id
            FROM categories
            WHERE slug = ?
              AND id != ?
            LIMIT 1
        ");

        $stmt->execute([
            $slug,
            $id
        ]);

        if ($stmt->fetch()) {

            $errors[] =
                'Another category with this name already exists.';
        }
    }


    /*
     * Validate selected attributes
     *
     * Only active attributes are allowed.
     */

    if (
        empty($errors) &&
        !empty($selectedAttributes)
    ) {

        $placeholders =
            implode(
                ',',
                array_fill(
                    0,
                    count($selectedAttributes),
                    '?'
                )
            );

        $stmt = $pdo->prepare("
            SELECT id
            FROM attributes
            WHERE id IN ($placeholders)
              AND is_active = 1
        ");

        $stmt->execute($selectedAttributes);

        $validAttributeIds =
            array_map(
                'intval',
                $stmt->fetchAll(PDO::FETCH_COLUMN)
            );


        /*
         * Make sure all submitted IDs are valid
         */
        foreach ($selectedAttributes as $attributeId) {

            if (
                !in_array(
                    $attributeId,
                    $validAttributeIds,
                    true
                )
            ) {

                $errors[] =
                    'One or more selected attributes are invalid.';

                break;
            }
        }
    }


    /*
     |--------------------------------------------------------------------------
     | Save
     |--------------------------------------------------------------------------
     */

    if (empty($errors)) {

        try {

            $pdo->beginTransaction();


            /*
             * Update category
             */

            $stmt = $pdo->prepare("
                UPDATE categories
                SET
                    name = ?,
                    slug = ?,
                    description = ?,
                    status = ?
                WHERE id = ?
            ");

            $stmt->execute([
                $name,
                $slug,
                $description !== ''
                    ? $description
                    : null,
                $status,
                $id
            ]);


            /*
             |--------------------------------------------------------------------------
             | Remove existing mappings
             |--------------------------------------------------------------------------
             */

            $stmt = $pdo->prepare("
                DELETE FROM category_attributes
                WHERE category_id = ?
            ");

            $stmt->execute([$id]);


            /*
             |--------------------------------------------------------------------------
             | Insert new mappings
             |--------------------------------------------------------------------------
             */

            if (!empty($selectedAttributes)) {

                $stmt = $pdo->prepare("
                    INSERT INTO category_attributes
                    (
                        category_id,
                        attribute_id,
                        sort_order
                    )
                    VALUES (?, ?, ?)
                ");

                $sortOrder = 0;

                foreach ($selectedAttributes as $attributeId) {

                    $stmt->execute([
                        $id,
                        $attributeId,
                        $sortOrder++
                    ]);
                }
            }


            /*
             * Commit
             */

            $pdo->commit();


            /*
             * Redirect
             */

            header(
                'Location: index.php?updated=1'
            );

            exit;


        } catch (Throwable $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $errors[] =
                'Unable to update category. Please try again.';
        }
    }


    /*
     * Keep submitted values after validation error
     */

    $category['name'] =
        $name;

    $category['slug'] =
        $slug ?? $category['slug'];

    $category['description'] =
        $description;

    $category['status'] =
        $status;


    $mappedAttributes = [];

    foreach ($selectedAttributes as $sortOrder => $attributeId) {

        $mappedAttributes[$attributeId] =
            $sortOrder;
    }
}


/*
|--------------------------------------------------------------------------
| Admin header
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../../includes/admin_header.php';

?>


<div class="d-flex justify-content-between align-items-center mb-3">

    <div>

        <h3 class="mb-1">
            Edit Category
        </h3>

        <div class="text-muted small">
            Update category details and product attributes.
        </div>

    </div>


    <a
        href="index.php"
        class="btn btn-outline-secondary">

        Back

    </a>

</div>


<?php if (!empty($errors)): ?>

    <div class="alert alert-danger">

        <ul class="mb-0">

            <?php foreach ($errors as $error): ?>

                <li>
                    <?= esc($error) ?>
                </li>

            <?php endforeach; ?>

        </ul>

    </div>

<?php endif; ?>


<form method="post">

    <input
        type="hidden"
        name="id"
        value="<?= (int)$category['id'] ?>">


    <input
        type="hidden"
        name="csrf_token"
        value="<?= csrf_token() ?>">


    <!--
    |--------------------------------------------------------------------------
    | Category Details
    |--------------------------------------------------------------------------
    -->

    <div class="card admin-card mb-4">

        <div class="card-body p-4">

            <h5 class="mb-3">
                Category Details
            </h5>


            <div class="mb-3">

                <label
                    for="categoryName"
                    class="form-label">

                    Category Name

                </label>

                <input
                    type="text"
                    id="categoryName"
                    name="name"
                    class="form-control"
                    value="<?= esc($category['name']) ?>"
                    required>

            </div>


            <div class="mb-3">

                <label
                    for="categoryDescription"
                    class="form-label">

                    Description

                </label>

                <textarea
                    id="categoryDescription"
                    name="description"
                    class="form-control"
                    rows="4"
                    placeholder="Category description..."><?= esc($category['description'] ?? '') ?></textarea>

            </div>


            <div class="form-check">

                <input
                    type="checkbox"
                    name="status"
                    id="categoryStatus"
                    class="form-check-input"
                    <?= $category['status']
                        ? 'checked'
                        : '' ?>>

                <label
                    for="categoryStatus"
                    class="form-check-label">

                    Active

                </label>

            </div>

        </div>

    </div>


    <!--
    |--------------------------------------------------------------------------
    | Product Attributes
    |--------------------------------------------------------------------------
    -->

    <div class="card admin-card mb-4">

        <div class="card-body p-4">

            <div class="d-flex justify-content-between align-items-center mb-1">

                <div>

                    <h5 class="mb-1">
                        Product Attributes
                    </h5>

                    <div class="text-muted small">
                        Select the attributes that products in this
                        category should have.
                    </div>

                </div>

                <span
                    id="selectedCount"
                    class="badge bg-secondary">

                    0 selected

                </span>

            </div>


            <?php if (empty($attributes)): ?>

                <div class="alert alert-info mt-3 mb-0">

                    No active attributes are available.

                    <a href="../attributes/create.php">
                        Create an attribute
                    </a>
                    first.

                </div>

            <?php else: ?>

                <div class="row mt-3">

                    <?php foreach ($attributes as $attribute): ?>

                        <?php

                        $attributeId =
                            (int)$attribute['id'];

                        $isChecked =
                            array_key_exists(
                                $attributeId,
                                $mappedAttributes
                            );

                        ?>

                        <div class="col-md-6 col-lg-4 mb-3">

                            <label
                                class="attribute-option w-100">

                                <input
                                    type="checkbox"
                                    name="attribute_ids[]"
                                    value="<?= $attributeId ?>"
                                    class="form-check-input attribute-checkbox me-2"
                                    <?= $isChecked
                                        ? 'checked'
                                        : '' ?>>

                                <span>

                                    <strong>
                                        <?= esc($attribute['name']) ?>
                                    </strong>

                                    <small
                                        class="text-muted d-block ms-4">

                                        <?= esc(
                                            ucfirst(
                                                $attribute['type']
                                            )
                                        ) ?>

                                        <?php if ($attribute['is_required']): ?>

                                            · Required

                                        <?php else: ?>

                                            · Optional

                                        <?php endif; ?>

                                    </small>

                                </span>

                            </label>

                        </div>

                    <?php endforeach; ?>

                </div>

            <?php endif; ?>

        </div>

    </div>


    <!--
    |--------------------------------------------------------------------------
    | Buttons
    |--------------------------------------------------------------------------
    -->

    <div class="d-flex gap-2">

        <button
            type="submit"
            class="btn btn-primary">

            Save Changes

        </button>


        <a
            href="index.php"
            class="btn btn-outline-secondary">

            Cancel

        </a>

    </div>

</form>


<style>

.attribute-option {

    display: flex;

    align-items: center;

    gap: 8px;

    padding: 14px 16px;

    border: 1px solid #dee2e6;

    border-radius: 10px;

    cursor: pointer;

    transition:
        background-color .2s ease,
        border-color .2s ease,
        box-shadow .2s ease;

}


.attribute-option:hover {

    background: #f8f9fa;

    border-color: #adb5bd;

}


.attribute-option:has(
    .attribute-checkbox:checked
) {

    background: #f1f8f4;

    border-color: #198754;

    box-shadow:
        0 2px 8px rgba(25, 135, 84, .08);

}


.attribute-checkbox {

    width: 18px;

    height: 18px;

    flex-shrink: 0;

}

</style>


<script>

document.addEventListener(
    'DOMContentLoaded',
    function () {

        const checkboxes =
            document.querySelectorAll(
                '.attribute-checkbox'
            );

        const selectedCount =
            document.getElementById(
                'selectedCount'
            );


        function updateCount() {

            const count =
                document.querySelectorAll(
                    '.attribute-checkbox:checked'
                ).length;


            selectedCount.textContent =
                count + (
                    count === 1
                        ? ' selected'
                        : ' selected'
                );


            if (count > 0) {

                selectedCount.classList.remove(
                    'bg-secondary'
                );

                selectedCount.classList.add(
                    'bg-success'
                );

            } else {

                selectedCount.classList.remove(
                    'bg-success'
                );

                selectedCount.classList.add(
                    'bg-secondary'
                );
            }
        }


        checkboxes.forEach(
            function (checkbox) {

                checkbox.addEventListener(
                    'change',
                    updateCount
                );

            }
        );


        updateCount();

    }
);

</script>