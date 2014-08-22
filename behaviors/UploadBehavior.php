<?php
/**
 * @link http://www.astwell.com/
 * @copyright Copyright (c) 2014 Astwell Soft
 * @license http://www.astwell.com/license/
 */

namespace lembadm\fileapi\behaviors;

use yii\base\Behavior;
use yii\base\InvalidParamException;
use yii\db\ActiveRecord;
use yii\helpers\FileHelper;

/**
 * Class UploadBehavior
 * Поведение для загрузки файлов.
 *
 * Пример использования:
 * ```php
 * use lembadm\fileapi\behaviors\UploadBehavior;
 *
 * public function behaviors()
 * {
 *     return [
 *          'uploadBehavior' => [
 *               'class' => UploadBehavior::className(),
 *               'attributes' => ['image', 'thumb'],
 *               'deleteScenarios' => [
 *                   'thumb' => 'delete-thumb',
 *               ],
 *               'scenarios' => ['signup', 'update'],
 *               'path' => [
 *                   'image' => Yii::getAlias('@webroot/path_to_images'),
 *                   'thumb' => Yii::getAlias('@webroot/path_to_thumbs'),
 *               ],
 *               'tempPath' => Yii::getAlias('@webroot/uploads'),
 *          ]
 *     ];
 * }
 * ```
 */
class UploadBehavior extends Behavior
{
    /**
     * @event Событие которое вызывается после успешной загрузки файла
     */
    const EVENT_AFTER_UPLOAD = 'afterUpload';

    /**
     * @var string|array Аттрибуты которые будут использоватся для сохранения пути к изображению
     */
    public $attributes;

    /**
     * @var array Массив сценариев в которых поведение должно срабатывать
     */
    public $scenarios = [];

    /**
     * @var array Массив сценариев в которых нужно удалить указанные атрибуты и их файлы.
     */
    public $deleteScenarios = [];

    /**
     * @var string|array Путь к папке в которой будут загружены файлы.
     *
     * Если поведение затрагивает несколько аттрибутов, можно указать пути сохранения для каждого отдельно
     *
     * Пример использования:
     * ~~~
     * [
     *     'image' => Yii::getAlias('@webroot/path_to_images'),
     *     'thumb' => Yii::getAlias('@webroot/path_to_thumbs'),
     * ]
     * ~~~
     */
    public $path;

    /**
     * @var string|array Путь к временой папке в которой загружены файлы.
     *
     * Если поведение затрагивает несколько аттрибутов, можно указать пути сохранения для каждого отдельно
     *
     * Пример использования:
     * ~~~
     * [
     *     'image' => Yii::getAlias('@webroot/path_to_temp_images'),
     *     'thumb' => Yii::getAlias('@webroot/path_to_temp_thumbs'),
     * ]
     * ~~~
     */
    public $tempPath;

    /**
     * @var boolean В случае true текущий файл из атрибута модели будет удалён.
     */
    public $deleteOnSave = true;

    /**
     * @var \yii\db\ActiveRecord
     */
    public $owner;

    /**
     * @inheritdoc
     */
    public function events()
    {
        return [
            ActiveRecord::EVENT_BEFORE_UPDATE => 'beforeUpdate',
            ActiveRecord::EVENT_BEFORE_INSERT => 'beforeInsert',
            ActiveRecord::EVENT_BEFORE_DELETE => 'beforeDelete',
        ];
    }

    /**
     * @inheritdoc
     */
    public function attach($owner)
    {
        parent::attach($owner);

        if ( ! is_array($this->attributes) or empty($this->attributes) ) {
            throw new InvalidParamException("Invalid or empty \"{$this->attributes}\" array");
        }

        if ( ! $this->path ) {
            throw new InvalidParamException("Empty \"{$this->path}\".");
        }

        if ( ! $this->tempPath ) {
            throw new InvalidParamException("Empty \"{$this->tempPath}\".");
        }

        if( ! is_array($this->attributes) ) {
            $this->attributes = [ $this->attributes ];
        }

        $this->path = ( is_array($this->path) )
            ? array_map(function($path){ return FileHelper::normalizePath($path) . DIRECTORY_SEPARATOR; }, $this->path)
            : array_fill_keys($this->attributes, FileHelper::normalizePath($this->path) . DIRECTORY_SEPARATOR);

        $this->tempPath = ( is_array($this->tempPath) )
            ? array_map(function($path){ return FileHelper::normalizePath($path) . DIRECTORY_SEPARATOR; }, $this->tempPath)
            : array_fill_keys($this->attributes, FileHelper::normalizePath($this->tempPath) . DIRECTORY_SEPARATOR);
    }

    /**
     * Метод срабатывает в момент создания новой записи моедли.
     */
    public function beforeInsert()
    {
        if (in_array($this->owner->scenario, $this->scenarios)) {
            foreach ($this->attributes as $attribute) {
                if ($this->owner->$attribute) {
                    $fileTempPath = $this->getTempPath($attribute);

                    if ( is_file($fileTempPath) ) {
                        rename($fileTempPath, $this->getPath($attribute));

                        $this->triggerEventAfterUpload();
                    }
                    else {
                        unset($this->owner->$attribute);
                    }
                }
            }
        }
    }

    /**
     * Метод срабатывает в момент обновления существующей записи моедли.
     */
    public function beforeUpdate()
    {
        if (in_array($this->owner->scenario, $this->scenarios)) {
            foreach ($this->attributes as $attribute) {
                if ($this->owner->isAttributeChanged($attribute)) {
                    $fileTempPath = $this->getTempPath($attribute);

                    if ( is_file($fileTempPath) ) {
                        rename($fileTempPath, $this->getPath($attribute));

                        if ($this->deleteOnSave === true and $this->owner->getOldAttribute($attribute)) {
                            $this->delete($attribute, true);
                        }

                        $this->triggerEventAfterUpload();
                    }
                    else {
                        $this->owner->setAttribute($attribute, $this->owner->getOldAttribute($attribute));
                    }
                }
            }
        }

        // Удаляем указаные атрибуты и их файлы если это нужно
        if (!empty($this->deleteScenarios) and in_array($this->owner->scenario, $this->deleteScenarios)) {
            foreach ($this->deleteScenarios as $attribute => $scenario) {
                if ($this->owner->scenario === $scenario) {
                    $file = $this->getPath($attribute);

                    if (is_file($file) and unlink($file)) {
                        $this->owner->$attribute = null;
                    }
                }
            }
        }
    }

    /**
     * Метод срабатывает в момент удаления существующей записи моедли.
     */
    public function beforeDelete()
    {
        foreach ($this->attributes as $attribute) {
            if ($this->owner->$attribute) {
                $this->delete($attribute);
            }
        }
    }

    /**
     * Удаляем старый файл.
     *
     * @param string $attribute Атрибут для которого нужно вернуть путь загрузки.
     * @param bool   $old       Получить путь для уже сохраненного файла
     */
    protected function delete($attribute, $old = false)
    {
        $file = $this->getPath($attribute, $old);

        if (is_file($file)) {
            unlink($file);
        }
    }

    /**
     * Определяем событие [[EVENT_AFTER_UPLOAD]] для текущей модели.
     */
    protected function triggerEventAfterUpload()
    {
        $this->owner->trigger(self::EVENT_AFTER_UPLOAD);
    }

    /**
     * Получить путь к файлу
     *
     * @param string $attribute Атрибут для которого нужно вернуть путь загрузки.
     * @param bool   $old       Получить путь для уже сохраненного файла
     *
     * @return string Путь загрузки файла.
     */
    public function getPath($attribute, $old = false)
    {
        $fileName = ($old === true)
            ? $this->owner->getOldAttribute($attribute)
            : $this->owner->$attribute;

        return (FileHelper::createDirectory( $this->path[$attribute]) )
            ? $this->path[$attribute] . $fileName
            : null;
    }

    /**
     * Получить путь к временному файлу
     *
     * @param string $attribute Атрибут для которого нужно вернуть путь загрузки.
     *
     * @return string Временный путь загрузки файла.
     */
    public function getTempPath($attribute)
    {
        return $this->tempPath[$attribute] . $this->owner->$attribute;
    }
} 