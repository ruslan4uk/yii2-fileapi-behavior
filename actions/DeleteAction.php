<?php
/**
 * @link http://www.astwell.com/
 * @copyright Copyright (c) 2014 Astwell Soft
 * @license http://www.astwell.com/license/
 */

namespace fileapi\actions;

use Yii;
use yii\base\Action;
use yii\base\InvalidConfigException;
use yii\helpers\FileHelper;

/**
 * DeleteAction действие для удаления загруженных файлов.
 *
 * Пример использования:
 * ```php
 * use fileapi\actions\DeleteAction;
 *
 * public function actions()
 * {
 *     return [
 *         'deleteTempImage' => [
 *             'class' => UploadAction::className(),
 *             'path'  => Yii::getAlias('@webroot/path_to_images'),
 *         ],
 *     ];
 * }
 * ```
 */
class DeleteAction extends Action
{
    /**
     * @var string Путь к папке где хранятся файлы.
     */
    public $path;

    /**
     * @var string Название переменной в которой хранится имя файла.
     */
    public $fileVar = 'file';

    /**
     * @inheritdoc
     */
    public function init()
    {
        if ($this->path === null) {
            throw new InvalidConfigException("Empty \"{$this->path}\".");
        }

        $this->path = FileHelper::normalizePath($this->path) . DIRECTORY_SEPARATOR;
    }

    /**
     * @inheritdoc
     */
    public function run()
    {
        if (($file = Yii::$app->request->getBodyParam($this->fileVar))) {
            if (is_file($this->path . $file)) {
                unlink($this->path . $file);
            }
        }
    }
} 