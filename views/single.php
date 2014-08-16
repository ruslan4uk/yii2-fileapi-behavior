<?php
/**
 * @link http://astwell.com/
 * @copyright Copyright (c) 2014 Astwell Soft
 * @license http://astwell.com/license/
 */

use lembadm\fileapi\FileAPI;

/**
 * Представление одиночной загрузки.
 *
 * @var yii\base\View $this
 * @var string $selector
 * @var string $fileVar
 * @var string $input
 */
?>
<div id="<?= $selector; ?>" class="uploader">
    <div class="uploader-file-preview"></div>
    <div class="btn btn-default js-fileapi-wrapper">
        <div class="uploader-browse">
            <?= FileAPI::t('fileapi', 'Выбрать') ?>
            <input type="file" name="<?= $fileVar ?>">
        </div>
        <div class="uploader-progress">
            <div class="progress progress-striped">
                <div class="uploader-progress-bar progress-bar progress-bar-info" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
            </div>
        </div>
    </div>
    <?= $input ?>
</div>