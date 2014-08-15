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

class UploadBehavior extends Behavior
{

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
    public $paths;

    /**
     * @var string Путь к временой папке в которой загружены файлы.
     */
    public $tempPath;

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

        if ( ! $this->attributes) {
            throw new InvalidParamException("Invalid or empty \"{$this->attributes}\" array");
        }

        if ( ! $this->paths ) {
            throw new InvalidParamException("Empty \"{$this->paths}\".");
        }

        if( ! is_array($this->attributes) ) {
            $this->attributes = [ $this->attributes ];
        }

        if( ! is_array($this->paths) ) {
            $path = FileHelper::normalizePath($this->paths) . DIRECTORY_SEPARATOR;

            array_walk($this->attributes, function($attribute) use($path) {
                $this->paths[ $attribute ] = $path;
            });
        }
        else {
            array_walk($this->paths, function($path, $attribute) use($this) {
                $this->paths[ $attribute ] = FileHelper::normalizePath($path) . DIRECTORY_SEPARATOR;;
            });
        }
    }

} 