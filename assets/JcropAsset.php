<?php
/**
 * @link http://www.astwell.com/
 * @copyright Copyright (c) 2014 Astwell Soft
 * @license http://www.astwell.com/license/
 */

namespace lembadm\fileapi\assets;

use yii\web\AssetBundle;

class JcropAsset extends AssetBundle
{
    public $sourcePath = '@vendor/rubaxa/fileapi';
    public $css = [
        'jcrop/jquery.Jcrop.min.css'
    ];
    public $js = [
        'jcrop/jquery.Jcrop.min.js',
    ];
    public $depends = [
        'yii\web\JqueryAsset',
    ];
}