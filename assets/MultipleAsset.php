<?php
/**
 * @link http://astwell.com/
 * @copyright Copyright (c) 2014 Astwell Soft
 * @license http://astwell.com/license/
 */

namespace lembadm\fileapi\assets;

use yii\web\AssetBundle;

/**
 * Asset bundle мульти-загрузки файлов
 */
class MultipleAsset extends AssetBundle
{
    public $sourcePath = '@fileapi/assets/src';
    public $css = [
        'css/multiple.css'
    ];
    public $depends = [
        'lembadm\fileapi\assets\FileAPIAsset'
    ];
}