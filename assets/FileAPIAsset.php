<?php
/**
 * @link http://astwell.com/
 * @copyright Copyright (c) 2014 Astwell Soft
 * @license http://astwell.com/license/
 */

namespace lembadm\fileapi\assets;

use yii\web\AssetBundle;

/**
 * Asset bundle загрузки одиночного файла
 */
class FileAPIAsset extends AssetBundle
{
    public $sourcePath = '@vendor/rubaxa/fileapi';
    public $css = [
        'css/single.css'
    ];
    public $js = [
        'FileAPI/FileAPI.min.js',
        'jquery.fileapi.min.js'
    ];
    public $depends = [
        'yii\web\JqueryAsset',
    ];
}