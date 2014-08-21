<?php
/**
 * @link http://www.astwell.com/
 * @copyright Copyright (c) 2014 Astwell Soft
 * @license http://www.astwell.com/license/
 */

namespace lembadm\fileapi\assets;

use yii\web\AssetBundle;

/**
 * Пакет продвинутой загрузки.
 */
class AdvancedAsset extends AssetBundle
{
    public $sourcePath = '@fileapi/assets/src';
    public $css = [
        'css/advanced.css',
    ];
    public $depends = [
        'yii\web\YiiAsset',
        'yii\bootstrap\BootstrapAsset',
        'lembadm\fileapi\assets\FileAPIAsset',
    ];
}