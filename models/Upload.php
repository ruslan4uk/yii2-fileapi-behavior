<?php
/**
 * @link http://www.astwell.com/
 * @copyright Copyright (c) 2014 Astwell Soft
 * @license http://www.astwell.com/license/
 */

namespace lembadm\fileapi\models;

use yii\base\Model;

/**
 * Vодель загрузки файлов.
 */
class Upload extends Model
{
    /**
     * Переменная используются для сбора пользовательской информации, но не сохраняются в базу.
     * @var \yii\web\UploadedFile переданный файл/ы
     */
    public $file;
}