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
class FileAPIAsset extends AssetBundle
{
    public $sourcePath = '@vendor/bower/jquery.fileapi';
    public $js = [
        'FileAPI/FileAPI.min.js',
        'jquery.fileapi.min.js'
    ];
    public $depends = [
        'yii\web\JqueryAsset',
    ];
}