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
| Load attribute
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT *
    FROM attributes
    WHERE id = ?
    LIMIT 1
");

$stmt->execute([$id]);

$attribute = $stmt->fetch();

if (!$attribute) {
    header('Location: index.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| Attribute types
|--------------------------------------------------------------------------
*/

$types = [
    'text'        => 'Text',
    'textarea'    => 'Textarea',
    'number'      => 'Number',
    'select'      => 'Select',
    'multiselect' => 'Multi Select',
    'boolean'     => 'Yes / No',
    'date'        => 'Date',
    'color'       => 'Color'
];

/*
|--------------------------------------------------------------------------
| Load existing options
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT *
    FROM attribute_options
    WHERE attribute_id = ?
    ORDER BY sort_order ASC, id ASC
");

$stmt->execute([$id]);

$existingOptions = $stmt->fetchAll();

$errors = [];


/*
|--------------------------------------------------------------------------
| Handle POST
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /*
     * CSRF
     */
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid security token.';
    }

    $name = trim($_POST['name'] ?? '');

    $type = $_POST['type'] ?? 'text';

    $isRequired = isset($_POST['is_required']) ? 1 : 0;

    $isActive = isset($_POST['is_active']) ? 1 : 0;

    /*
     * Existing options
     *
     * Format:
     *
     * existing_options[12] = English
     * existing_options[13] = Bengali
     */
    $postedExistingOptions =
        $_POST['existing_options'] ?? [];

    /*
     * New options:
     *
     * new_options[] = Hindi
     * new_options[] = Tamil
     */
    $newOptions =
        $_POST['new_options'] ?? [];


    /*
     * Basic validation
     */

    if ($name === '') {
        $errors[] = 'Attribute name is required.';
    }

    if (!array_key_exists($type, $types)) {
        $errors[] = 'Invalid attribute type.';
    }


    /*
     |--------------------------------------------------------------------------
     | Generate slug
     |--------------------------------------------------------------------------
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
            $errors[] = 'Unable to generate a valid slug.';
        }
    }


    /*
     |--------------------------------------------------------------------------
     | Duplicate check
     |--------------------------------------------------------------------------
     */

    if (empty($errors)) {

        $stmt = $pdo->prepare("
            SELECT id
            FROM attributes
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
                'Another attribute with this name already exists.';
        }
    }


    /*
     |--------------------------------------------------------------------------
     | Validate options
     |--------------------------------------------------------------------------
     */

    if (
        empty($errors) &&
        in_array(
            $type,
            ['select', 'multiselect'],
            true
        )
    ) {

        /*
         * Clean existing options
         */
        $cleanExistingOptions = [];

        foreach ($postedExistingOptions as $optionId => $optionValue) {

            $optionId = (int)$optionId;

            $optionValue = trim($optionValue);

            if ($optionId > 0 && $optionValue !== '') {

                $cleanExistingOptions[$optionId] =
                    $optionValue;
            }
        }


        /*
         * Clean new options
         */
        $cleanNewOptions = [];

        foreach ($newOptions as $optionValue) {

            $optionValue = trim($optionValue);

            if ($optionValue !== '') {
                $cleanNewOptions[] = $optionValue;
            }
        }


        /*
         * At least one option
         */
        if (
            count($cleanExistingOptions) === 0 &&
            count($cleanNewOptions) === 0
        ) {
            $errors[] =
                'Please provide at least one option for this attribute.';
        }

    } else {

        $cleanExistingOptions = [];
        $cleanNewOptions = [];
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
             * Update attribute
             */

            $stmt = $pdo->prepare("
                UPDATE attributes
                SET
                    name = ?,
                    slug = ?,
                    type = ?,
                    is_required = ?,
                    is_active = ?
                WHERE id = ?
            ");

            $stmt->execute([
                $name,
                $slug,
                $type,
                $isRequired,
                $isActive,
                $id
            ]);


            /*
             |--------------------------------------------------------------------------
             | Manage options
             |--------------------------------------------------------------------------
             */

            if (
                in_array(
                    $type,
                    ['select', 'multiselect'],
                    true
                )
            ) {

                /*
                 * Get all current option IDs
                 */
                $stmt = $pdo->prepare("
                    SELECT id
                    FROM attribute_options
                    WHERE attribute_id = ?
                ");

                $stmt->execute([$id]);

                $currentOptionIds =
                    array_map(
                        'intval',
                        $stmt->fetchAll(PDO::FETCH_COLUMN)
                    );


                /*
                 * Keep only valid existing options
                 */
                $validExistingOptions = [];

                foreach ($cleanExistingOptions as $optionId => $optionValue) {

                    if (
                        in_array(
                            (int)$optionId,
                            $currentOptionIds,
                            true
                        )
                    ) {

                        $validExistingOptions[$optionId] =
                            $optionValue;
                    }
                }


                /*
                 * Delete options removed by admin
                 */
                foreach ($currentOptionIds as $currentOptionId) {

                    if (
                        !array_key_exists(
                            $currentOptionId,
                            $validExistingOptions
                        )
                    ) {

                        $stmt = $pdo->prepare("
                            DELETE FROM attribute_options
                            WHERE id = ?
                              AND attribute_id = ?
                        ");

                        $stmt->execute([
                            $currentOptionId,
                            $id
                        ]);
                    }
                }


                /*
                 * Update existing options
                 */
                $sortOrder = 0;

                $updateOption = $pdo->prepare("
                    UPDATE attribute_options
                    SET
                        option_value = ?,
                        sort_order = ?
                    WHERE id = ?
                      AND attribute_id = ?
                ");

                foreach (
                    $validExistingOptions
                    as $optionId => $optionValue
                ) {

                    $updateOption->execute([
                        $optionValue,
                        $sortOrder++,
                        $optionId,
                        $id
                    ]);
                }


                /*
                 * Insert new options
                 */
                $insertOption = $pdo->prepare("
                    INSERT INTO attribute_options
                    (
                        attribute_id,
                        option_value,
                        sort_order
                    )
                    VALUES (?, ?, ?)
                ");

                foreach ($cleanNewOptions as $optionValue) {

                    $insertOption->execute([
                        $id,
                        $optionValue,
                        $sortOrder++
                    ]);
                }

            } else {

                /*
                 * If attribute type is changed from
                 * Select/Multi Select to another type,
                 * remove its options.
                 *
                 * Because attribute_options.attribute_id
                 * has ON DELETE CASCADE, these options
                 * can safely be removed.
                 */
                $stmt = $pdo->prepare("
                    DELETE FROM attribute_options
                    WHERE attribute_id = ?
                ");

                $stmt->execute([$id]);
            }


            /*
             * Everything successful
             */
            $pdo->commit();

            header('Location: index.php');
            exit;

        } catch (Throwable $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $errors[] =
                'Unable to update attribute. Please try again.';
        }
    }


    /*
     |--------------------------------------------------------------------------
     | Rebuild values for form after validation error
     |--------------------------------------------------------------------------
     */

    $attribute['name'] =
        $name;

    $attribute['slug'] =
        $slug ?? $attribute['slug'];

    $attribute['type'] =
        $type;

    $attribute['is_required'] =
        $isRequired;

    $attribute['is_active'] =
        $isActive;


    /*
     * Rebuild options shown in form
     */
    if (
        in_array(
            $type,
            ['select', 'multiselect'],
            true
        )
    ) {

        $existingOptions = [];

        foreach (
            $cleanExistingOptions
            as $optionId => $optionValue
        ) {

            $existingOptions[] = [
                'id' => $optionId,
                'attribute_id' => $id,
                'option_value' => $optionValue,
                'sort_order' => 0
            ];
        }

        /*
         * New options are shown separately
         */
        foreach ($cleanNewOptions as $optionValue) {

            $existingOptions[] = [
                'id' => 0,
                'attribute_id' => $id,
                'option_value' => $optionValue,
                'sort_order' => 0
            ];
        }
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
            Edit Attribute
        </h3>

        <div class="text-muted small">
            Update product attribute settings and options.
        </div>

    </div>

    <a
        href="index.php"
        class="btn btn-outline-secondary">

        Back

    </a>

</div>


<?php if ($errors): ?>

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


<div class="card admin-card">

    <div class="card-body p-4">

        <form method="post">

            <input
                type="hidden"
                name="id"
                value="<?= (int)$attribute['id'] ?>">

            <input
                type="hidden"
                name="csrf_token"
                value="<?= csrf_token() ?>">


            <!-- Attribute Name -->

            <div class="mb-3">

                <label
                    for="attributeName"
                    class="form-label">

                    Attribute Name

                </label>

                <input
                    type="text"
                    id="attributeName"
                    name="name"
                    class="form-control"
                    value="<?= esc($attribute['name']) ?>"
                    placeholder="e.g. ISBN, Author, Size, Color"
                    required>

                <div class="form-text">
                    A unique name used to identify this attribute.
                </div>

            </div>


            <!-- Type -->

            <div class="mb-3">

                <label
                    for="attributeType"
                    class="form-label">

                    Type

                </label>

                <select
                    name="type"
                    id="attributeType"
                    class="form-select"
                    required>

                    <?php foreach ($types as $value => $label): ?>

                        <option
                            value="<?= esc($value) ?>"
                            <?= $attribute['type'] === $value
                                ? 'selected'
                                : '' ?>>

                            <?= esc($label) ?>

                        </option>

                    <?php endforeach; ?>

                </select>

            </div>


            <!-- Options -->

            <div
                id="optionsSection"
                class="mb-4"
                style="display:none;">

                <div class="d-flex justify-content-between align-items-center mb-2">

                    <label class="form-label mb-0">
                        Options
                    </label>

                    <span class="small text-muted">
                        Select / Multi Select
                    </span>

                </div>


                <div id="optionsList">

                    <?php foreach ($existingOptions as $index => $option): ?>

                        <div
                            class="input-group mb-2 option-row">

                            <?php if ((int)$option['id'] > 0): ?>

                                <input
                                    type="text"
                                    name="existing_options[<?= (int)$option['id'] ?>]"
                                    class="form-control"
                                    value="<?= esc($option['option_value']) ?>"
                                    placeholder="Option value">

                                <button
                                    type="button"
                                    class="btn btn-outline-danger remove-option">

                                    Remove

                                </button>

                            <?php else: ?>

                                <input
                                    type="text"
                                    name="new_options[]"
                                    class="form-control"
                                    value="<?= esc($option['option_value']) ?>"
                                    placeholder="New option">

                                <button
                                    type="button"
                                    class="btn btn-outline-danger remove-option">

                                    Remove

                                </button>

                            <?php endif; ?>

                        </div>

                    <?php endforeach; ?>

                </div>


                <button
                    type="button"
                    id="addOption"
                    class="btn btn-sm btn-outline-primary">

                    + Add Option

                </button>

                <div class="form-text mt-2">
                    Add one option per field.
                </div>

            </div>


            <!-- Required -->

            <div class="form-check mb-2">

                <input
                    type="checkbox"
                    name="is_required"
                    class="form-check-input"
                    id="isRequired"
                    <?= $attribute['is_required']
                        ? 'checked'
                        : '' ?>>

                <label
                    class="form-check-label"
                    for="isRequired">

                    Required field

                </label>

            </div>


            <!-- Active -->

            <div class="form-check mb-4">

                <input
                    type="checkbox"
                    name="is_active"
                    class="form-check-input"
                    id="isActive"
                    <?= $attribute['is_active']
                        ? 'checked'
                        : '' ?>>

                <label
                    class="form-check-label"
                    for="isActive">

                    Active

                </label>

            </div>


            <!-- Buttons -->

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

        </form>

    </div>

</div>


<script>
document.addEventListener('DOMContentLoaded', function () {

    const typeSelect =
        document.getElementById('attributeType');

    const optionsSection =
        document.getElementById('optionsSection');

    const optionsList =
        document.getElementById('optionsList');

    const addOptionButton =
        document.getElementById('addOption');


    /*
     * Show/hide options section
     */

    function updateOptionsVisibility() {

        const needsOptions =
            typeSelect.value === 'select' ||
            typeSelect.value === 'multiselect';

        optionsSection.style.display =
            needsOptions ? 'block' : 'none';
    }


    /*
     * Add new option
     */

    addOptionButton.addEventListener('click', function () {

        const row =
            document.createElement('div');

        row.className =
            'input-group mb-2 option-row';

        row.innerHTML = `
            <input
                type="text"
                name="new_options[]"
                class="form-control"
                placeholder="New option">

            <button
                type="button"
                class="btn btn-outline-danger remove-option">

                Remove

            </button>
        `;

        optionsList.appendChild(row);

        /*
         * Focus newly created field
         */
        row.querySelector('input').focus();
    });


    /*
     * Remove option row
     */

    optionsList.addEventListener('click', function (event) {

        if (
            event.target.classList.contains(
                'remove-option'
            )
        ) {

            const row =
                event.target.closest('.option-row');

            if (row) {
                row.remove();
            }
        }
    });


    /*
     * Type change
     */

    typeSelect.addEventListener(
        'change',
        updateOptionsVisibility
    );


    /*
     * Initial state
     */

    updateOptionsVisibility();

});
</script>