<?php
/**
 * @link http://www.astwell.com/
 * @copyright Copyright (c) 2014 Astwell Soft
 * @license http://www.astwell.com/license/
 */

namespace lembadm\fileapi\assets;

use yii\web\AssetBundle;

/**
 * Пакет продвинутой загрузки файла с предварительной нарезкой.
 */
class AdvancedCropAsset extends AssetBundle
{
    public $sourcePath = '@fileapi/assets/src';
    public $css = [
        'css/advanced.css',
        'vendor/jquery.fileapi/the-modal/the-modal.css',
    ];
    public $js = [
        'vendor/jquery.fileapi/the-modal/jquery.modal.js'
    ];
    public $depends = [
        'yii\web\YiiAsset',
        'yii\bootstrap\BootstrapAsset',
        'lembadm\fileapi\assets\AdvancedAsset',
        'lembadm\fileapi\assets\JcropAsset'
    ];
}