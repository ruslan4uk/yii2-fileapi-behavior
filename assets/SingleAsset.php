<?php
/**
 * @link http://astwell.com/
 * @copyright Copyright (c) 2014 Astwell Soft
 * @license http://astwell.com/license/
 */

namespace fileapi\assets;

use yii\web\AssetBundle;

/**
 * Asset bundle загрузки одиночного файла
 */
class SingleAsset extends AssetBundle
{
    public $sourcePath = '@fileapi/assets/src';
    public $css = [
        'css/single.css'
    ];
    public $depends = [
        'fileapi\assets\FileAPIAsset'
    ];
}