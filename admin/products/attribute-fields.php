<?php

require_once __DIR__ . '/../../includes/admin_auth.php';
require_once __DIR__ . '/../../config/database.php';

require_admin();

$pdo = getPDO();

$categoryId = (int)($_GET['category_id'] ?? 0);

if ($categoryId <= 0) {
    exit;
}


/*
|--------------------------------------------------------------------------
| Load attributes assigned to category
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        a.id,
        a.name,
        a.slug,
        a.type,
        a.is_required
    FROM category_attributes ca
    INNER JOIN attributes a
        ON a.id = ca.attribute_id
    WHERE ca.category_id = ?
      AND a.is_active = 1
    ORDER BY ca.sort_order ASC, a.name ASC
");

$stmt->execute([$categoryId]);

$attributes = $stmt->fetchAll();


foreach ($attributes as $attribute):

    $attributeId =
        (int)$attribute['id'];

    $name =
        $attribute['name'];

    $type =
        $attribute['type'];

    $required =
        (int)$attribute['is_required'];

    ?>

    <div class="mb-3">

        <label class="form-label">

            <?= esc($name) ?>

            <?php if ($required): ?>

                <span class="text-danger">*</span>

            <?php endif; ?>

        </label>


        <?php

        /*
        |--------------------------------------------------------------------------
        | Select
        |--------------------------------------------------------------------------
        */

        if ($type === 'select'):

            $stmtOptions = $pdo->prepare("
                SELECT
                    id,
                    option_value
                FROM attribute_options
                WHERE attribute_id = ?
                ORDER BY sort_order ASC, id ASC
            ");

            $stmtOptions->execute([
                $attributeId
            ]);

            $options =
                $stmtOptions->fetchAll();

            ?>

            <select
                name="attributes[<?= $attributeId ?>]"
                class="form-select"
                <?= $required ? 'required' : '' ?>>

                <option value="">
                    Select <?= esc($name) ?>
                </option>

                <?php foreach ($options as $option): ?>

                    <option
                        value="<?= (int)$option['id'] ?>">

                        <?= esc(
                            $option['option_value']
                        ) ?>

                    </option>

                <?php endforeach; ?>

            </select>


        <?php

        /*
        |--------------------------------------------------------------------------
        | Multi Select
        |--------------------------------------------------------------------------
        */

        elseif ($type === 'multiselect'):

            $stmtOptions = $pdo->prepare("
                SELECT
                    id,
                    option_value
                FROM attribute_options
                WHERE attribute_id = ?
                ORDER BY sort_order ASC, id ASC
            ");

            $stmtOptions->execute([
                $attributeId
            ]);

            $options =
                $stmtOptions->fetchAll();

            ?>

            <select
                name="attributes[<?= $attributeId ?>][]"
                class="form-select"
                multiple
                <?= $required ? 'required' : '' ?>>

                <?php foreach ($options as $option): ?>

                    <option
                        value="<?= (int)$option['id'] ?>">

                        <?= esc(
                            $option['option_value']
                        ) ?>

                    </option>

                <?php endforeach; ?>

            </select>

            <div class="form-text">
                Hold Ctrl / Command to select multiple values.
            </div>


        <?php

        /*
        |--------------------------------------------------------------------------
        | Textarea
        |--------------------------------------------------------------------------
        */

        elseif ($type === 'textarea'):

            ?>

            <textarea
                name="attributes[<?= $attributeId ?>]"
                class="form-control"
                rows="4"
                <?= $required ? 'required' : '' ?>></textarea>


        <?php

        /*
        |--------------------------------------------------------------------------
        | Number
        |--------------------------------------------------------------------------
        */

        elseif ($type === 'number'):

            ?>

            <input
                type="number"
                step="any"
                name="attributes[<?= $attributeId ?>]"
                class="form-control"
                <?= $required ? 'required' : '' ?>>


        <?php

        /*
        |--------------------------------------------------------------------------
        | Date
        |--------------------------------------------------------------------------
        */

        elseif ($type === 'date'):

            ?>

            <input
                type="date"
                name="attributes[<?= $attributeId ?>]"
                class="form-control"
                <?= $required ? 'required' : '' ?>>


        <?php

        /*
        |--------------------------------------------------------------------------
        | Boolean
        |--------------------------------------------------------------------------
        */

        elseif ($type === 'boolean'):

            ?>

            <select
                name="attributes[<?= $attributeId ?>]"
                class="form-select"
                <?= $required ? 'required' : '' ?>>

                <option value="">
                    Select
                </option>

                <option value="1">
                    Yes
                </option>

                <option value="0">
                    No
                </option>

            </select>


        <?php

        /*
        |--------------------------------------------------------------------------
        | Color
        |--------------------------------------------------------------------------
        */

        elseif ($type === 'color'):

            ?>

            <input
                type="color"
                name="attributes[<?= $attributeId ?>]"
                class="form-control form-control-color"
                value="#000000"
                title="<?= esc($name) ?>">


        <?php

        /*
        |--------------------------------------------------------------------------
        | Default Text
        |--------------------------------------------------------------------------
        */

        else:

            ?>

            <input
                type="text"
                name="attributes[<?= $attributeId ?>]"
                class="form-control"
                <?= $required ? 'required' : '' ?>>

        <?php endif; ?>

    </div>

<?php endforeach; ?>