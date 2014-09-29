<?php
/**
 * @link http://www.astwell.com/
 * @copyright Copyright (c) 2014 Astwell Soft
 * @license http://www.astwell.com/license/
 */

namespace fileapi\behaviors;

use Yii;
use yii\base\Behavior;
use yii\base\InvalidParamException;
use yii\db\ActiveRecord;
use yii\helpers\FileHelper;

/**
 * Class UploadBehavior
 * @package fileapi\behaviors
 *
 * Поведение для загрузки файлов.
 *
 * Пример использования:
 * ```php
 * use fileapi\behaviors\UploadBehavior;
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
 *                   'image' => '@webroot/path_to_images',
 *                   'thumb' => '@webroot/path_to_thumbs',
 *               ],
 *               'tempPath' => '@webroot/uploads',
 *          ]
 *     ];
 * }
 * ```
 *
 * @property array|string $tempPath
 * @property array|string  $path
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
     *     'image' => '@webroot/path_to_images',
     *     'thumb' => '@webroot/path_to_thumbs',
     * ]
     * ~~~
     */
    private $_path;

    public function getPath()
    {
        return $this->normalizePath($this->_path);
    }

    public function setPath($data)
    {
        $this->_path = $data;
    }

    /**
     * @var string|array Путь к временой папке в которой загружены файлы.
     *
     * Если поведение затрагивает несколько аттрибутов, можно указать пути сохранения для каждого отдельно
     *
     * Пример использования:
     * ~~~
     * [
     *     'image' => '@webroot/path_to_temp_images',
     *     'thumb' => '@webroot/path_to_temp_thumbs',
     * ]
     * ~~~
     */
    private $_tempPath;

    public function getTempPath()
    {
        return $this->normalizePath($this->_tempPath);
    }

    public function setTempPath($data)
    {
        $this->_tempPath = $data;
    }

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

        if (!is_array($this->attributes) or empty($this->attributes)) {
            throw new InvalidParamException("Invalid or empty attributes array");
        }

        if (!$this->path) {
            throw new InvalidParamException("Empty path");
        }

        if (!$this->tempPath) {
            throw new InvalidParamException("Empty tempPath");
        }

        if (!is_array($this->attributes)) {
            $this->attributes = [$this->attributes];
        }
    }

    /**
     * @param array|string $path
     * @return array
     */
    protected function normalizePath($path)
    {

        if (is_array($path)) {
            return array_map(function ($data) {
                $data = Yii::getAlias($data);
                $data = FileHelper::normalizePath($data);
                return $data;
            }, $path);
        } else {
            $path = Yii::getAlias($path);
            $path = FileHelper::normalizePath($path);
            return array_fill_keys($this->attributes, $path);
        }
    }

    /**
     * Метод срабатывает в момент создания новой записи моедли.
     */
    public function beforeInsert()
    {
        if (empty($this->scenarios) || in_array($this->owner->scenario, $this->scenarios)) {
            foreach ($this->attributes as $attribute) {
                if ($this->owner->$attribute) {
                    $fileTempPath = $this->getTempUploadPath($attribute);

                    if (is_file($fileTempPath)) {
                        rename($fileTempPath, $this->getUploadPath($attribute));

                        $this->triggerEventAfterUpload();
                    } else {
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
        if (empty($this->scenarios) || in_array($this->owner->scenario, $this->scenarios)) {
            foreach ($this->attributes as $attribute) {
                if ($this->owner->isAttributeChanged($attribute)) {
                    $fileTempPath = $this->getTempUploadPath($attribute);

                    if (is_file($fileTempPath)) {
                        rename($fileTempPath, $this->getUploadPath($attribute));

                        if ($this->deleteOnSave === true) {
                            $this->delete($attribute, true);
                        }

                        $this->triggerEventAfterUpload();
                    } else {
                        $this->owner->setAttribute($attribute, $this->owner->getOldAttribute($attribute));
                    }
                }
            }
        }

        // Удаляем указаные атрибуты и их файлы если это нужно
        if (!empty($this->deleteScenarios) and in_array($this->owner->scenario, $this->deleteScenarios)) {
            foreach ($this->deleteScenarios as $attribute => $scenario) {
                if ($this->owner->scenario === $scenario) {
                    $file = $this->getUploadPath($attribute);

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
     * @param bool $old Получить путь для уже сохраненного файла
     */
    protected function delete($attribute, $old = false)
    {
        $file = $this->getUploadPath($attribute, $old);

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
     * @param bool $old Получить путь для уже сохраненного файла
     *
     * @return string Путь загрузки файла.
     */
    protected function getUploadPath($attribute, $old = false)
    {
        $fileName = ($old === true)
            ? $this->owner->getOldAttribute($attribute)
            : $this->owner->$attribute;

        return (FileHelper::createDirectory($this->path[$attribute]))
            ? $this->path[$attribute] . DIRECTORY_SEPARATOR . $fileName
            : null;
    }

    /**
     * Получить путь к временному файлу
     *
     * @param string $attribute Атрибут для которого нужно вернуть путь загрузки.
     *
     * @return string Временный путь загрузки файла.
     */
    protected function getTempUploadPath($attribute)
    {
        return $this->tempPath[$attribute] . DIRECTORY_SEPARATOR . $this->owner->$attribute;
    }
} 