<?php
/**
 * @link http://www.astwell.com/
 * @copyright Copyright (c) 2014 Astwell Soft
 * @license http://www.astwell.com/license/
 */

namespace fileapi\assets;

use yii\web\AssetBundle;

/**
 * Пакет продвинутой загрузки файла с предварительной нарезкой.
 */
class AdvancedCropAsset extends AssetBundle
{
    public $sourcePath = '@fileapi/assets/src';
    public $css = [
        'css/the-modal.css',
    ];
    public $js = [
        'js/jquery.modal.js'
    ];
    public $depends = [
        'yii\web\YiiAsset',
        'yii\bootstrap\BootstrapAsset',
        'fileapi\assets\AdvancedAsset',
        'fileapi\assets\JcropAsset'
    ];
}