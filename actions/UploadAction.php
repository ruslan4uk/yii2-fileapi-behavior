<?php
/**
 * @link http://www.astwell.com/
 * @copyright Copyright (c) 2014 Astwell Soft
 * @license http://www.astwell.com/license/
 */

namespace lembadm\fileapi\actions;

use lembadm\fileapi\models\Upload;
use Yii;
use yii\base\Action;
use yii\base\InvalidConfigException;
use yii\helpers\FileHelper;
use yii\helpers\Json;
use yii\validators\Validator;
use yii\web\BadRequestHttpException;
use yii\web\UploadedFile;

/**
 * UploadAction действие для загрузки файлов.
 * Использует в качестве валидаторов [[yii\validators\FileValidator]] или [[yii\validators\ImageValidator]],
 * таким образом доступны все параметры для настройки данных классов.
 *
 * Пример использования:
 * ```php
 * use lembadm\fileapi\actions\UploadAction;
 *
 * public function actions()
 * {
 *     return [
 *         'uploadTempImage' => [
 *             'class' => UploadAction::className(),
 *             'path' => $this->module->imageTempPath(),
 *             'types' => $this->module->imageAllowedExtensions,
 *             'minHeight' => $this->module->imageHeight,
 *             'minWidth' => $this->module->imageWidth
 *         ],
 *     ];
 * }
 * ```
 */
class UploadAction extends Action
{
    /**
     * @var string По умолчанию используется [[yii\validators\ImageValidator]] в качестве валидатора загружаемых файлов.
     * В случае false будет использоватся [[yii\validators\FileValidator]]
     */
    public $imageValidator = true;

    /**
     * @var string Путь к папке в которой будут загружены файлы
     */
    public $path;

    /**
     * @var string Название переменной в которой хранится загружаемый файл
     */
    public $fileVar = 'file';

    /**
     * @var boolean В случае true для загружаемых файлов будут сгенерированы уникальные имена на основе {@link http://md1.php.net/uniqid uniqid()}
     */
    public $unique = true;

    /**
     * @var array|string Список разрешенных расширений файла
     * Может указываться как массив или как строка в которой каждое расширение разделено запятой с пробелом
     * Пример:
     * ~~~
     * [ 'gif', 'gif' ]
     * ~~~
     * или
     * ~~~
     * "gif, gif"
     * ~~~
     * Расширение файла чувствительно к регистру символов.
     * По умолчанию null - любое расширение
     */
    public $types;

    /**
     * @var integer Минимальный размер в байтах для загружаемого файла
     * По умолчанию null - любой размер
     */
    public $minSize;

    /**
     * @var integer Максимальный размер в байтах для загружаемого файла
     * По умолчанию null - любой размер
     *
     * Примечание, максимальный размер также зависит от 'upload_max_filesize' параметра INI
     * и скрытого параметра 'MAX_FILE_SIZE' HTML тега input
     */
    public $maxSize;

    /**
     * @var integer Максимальное кол-во файлов которое может сохранить аттрибут
     * По умолчанию 1 - загруза одного файла. Указав большее значение, активируется мультизагрузка.
     */
    public $maxFiles = 1;

    /**
     * @var string Сообщение об ошибке - произошла ошибка загрузки файла.
     */
    public $message;

    /**
     * @var string Сообщение об ошибке - файл не загружен
     */
    public $uploadRequired;

    /**
     * @var string Сообщение об ошибке - загруженный файл слишком велик.
     * Вы можете использовать следующие маркеры в сообщении:
     *
     * - {attribute}: имя атрибута
     * - {file}: имя загруженного файла
     * - {limit}: максимально допустимый размер (see [[getSizeLimit()]])
     */
    public $tooBig;

    /**
     * @var string Сообщение об ошибке - загруженный файл слишком мал.
     * Вы можете использовать следующие маркеры в сообщении:
     *
     * - {attribute}: имя атрибута
     * - {file}: имя загруженного файла
     * - {limit}: значение [[minSize]]
     */
    public $tooSmall;

    /**
     * @var string Сообщение об ошибке - загруженный файл имеет расширение, не перечисленное в [[types]]
     * Вы можете использовать следующие маркеры в сообщении:
     *
     * - {attribute}: имя атрибута
     * - {file}: имя загруженного файла
     * - {extensions}: список разрешенных расширений
     */
    public $wrongType;

    /**
     * @var string Сообщение об ошибке - кол-во загружаемых файлов превышает лимит [[maxFiles]]
     * Вы можете использовать следующие маркеры в сообщении:
     *
     * - {attribute}: имя атрибута
     * - {limit}: значение [[maxFiles]]
     */
    public $tooMany;

    /**
     * @var string Сообщение об ошибке - загруженный файл не является изображением
     * Вы можете использовать следующие маркеры в сообщении:
     *
     * - {attribute}: имя атрибута
     * - {file}: имя загруженного файла
     */
    public $notImage;

    /**
     * @var integer Минимальная ширина (в пикселях)
     * По умолчанию null, т.е. нет лимита.
     */
    public $minWidth;

    /**
     * @var integer Максимальная ширина (в пикселях)
     * По умолчанию null, т.е. нет лимита.
     */
    public $maxWidth;

    /**
     * @var integer Минимальная высота (в пикселях)
     * По умолчанию null, т.е. нет лимита.
     */
    public $minHeight;

    /**
     * @var integer Максимальная высота (в пикселях)
     * По умолчанию null, т.е. нет лимита.
     */
    public $maxHeight;

    /**
     * @var array|string Список mime-типов, которые разрешено загружать.
     * Может указываться как массив или как строка в которой каждый mime-тип разделен запятой с пробелом
     * Пример:
     * ~~~
     * [ 'image/jpeg', 'image/png' ]
     * ~~~
     * или
     * ~~~
     * "image/jpeg, image/png"
     * ~~~
     * mime-тип чувствительн к регистру символов.
     * По умолчанию null - любой mime-тип
     */
    public $mimeTypes;

    /**
     * @var string Сообщение об ошибке - изображение меньше [[minWidth]].
     * Вы можете использовать следующие маркеры в сообщении:
     *
     * - {attribute}: имя атрибута
     * - {file}: имя загруженного файла
     * - {limit}: значение [[minWidth]]
     */
    public $underWidth;

    /**
     * @var string Сообщение об ошибке - изображение больше [[maxWidth]].
     * Вы можете использовать следующие маркеры в сообщении:
     *
     * - {attribute}: имя атрибута
     * - {file}: имя загруженного файла
     * - {limit}: значение [[maxWidth]]
     */
    public $overWidth;

    /**
     * @var string Сообщение об ошибке - изображение меньше [[minHeight]].
     * Вы можете использовать следующие маркеры в сообщении:
     *
     * - {attribute}: имя атрибута
     * - {file}: имя загруженного файла
     * - {limit}: значение [[minHeight]]
     */
    public $underHeight;

    /**
     * @var string Сообщение об ошибке - изображение больше [[maxHeight]].
     * Вы можете использовать следующие маркеры в сообщении:
     *
     * - {attribute}: имя атрибута
     * - {file}: имя загруженного файла
     * - {limit}: значение [[maxHeight]]
     */
    public $overHeight;

    /**
     * @var string Сообщение об ошибке - загруженный файл имеет mime-тип, не перечисленный в [[mimeTypes]]
     * Вы можете использовать следующие маркеры в сообщении:
     *
     * - {attribute}: имя атрибута
     * - {file}: имя загруженного файла
     * - {mimeTypes}: значение [[mimeTypes]]
     */
    public $wrongMimeType;

    /**
     * @var string Имя валидатора
     */
    protected $_validator;

    /**
     * @var array Настройки валидатора
     */
    protected $_validatorOptions;

    /**
     * @inheritdoc
     */
    public function init()
    {
        if ($this->path === null) {
            throw new InvalidConfigException("Empty \"{$this->path}\".");
        }

        $this->path = FileHelper::normalizePath($this->path) . DIRECTORY_SEPARATOR;

        $this->_validatorOptions = [
            'types'          => $this->types,
            'minSize'        => $this->minSize,
            'maxSize'        => $this->maxSize,
            'maxFiles'       => $this->maxFiles,
            'message'        => $this->message,
            'uploadRequired' => $this->uploadRequired,
            'tooBig'         => $this->tooBig,
            'tooSmall'       => $this->tooSmall,
            'wrongType'      => $this->wrongType,
            'tooMany'        => $this->tooMany
        ];

        if ($this->imageValidator === true) {
            $this->_validator = 'image';
            $this->_validatorOptions['notImage']      = $this->notImage;
            $this->_validatorOptions['minWidth']      = $this->minWidth;
            $this->_validatorOptions['maxWidth']      = $this->maxWidth;
            $this->_validatorOptions['minHeight']     = $this->minHeight;
            $this->_validatorOptions['maxHeight']     = $this->maxHeight;
            $this->_validatorOptions['mimeTypes']     = $this->mimeTypes;
            $this->_validatorOptions['underWidth']    = $this->underWidth;
            $this->_validatorOptions['overWidth']     = $this->overWidth;
            $this->_validatorOptions['underHeight']   = $this->underHeight;
            $this->_validatorOptions['overHeight']    = $this->overHeight;
            $this->_validatorOptions['wrongMimeType'] = $this->wrongMimeType;
        }
        else {
            $this->_validator = 'file';
        }
    }

    /**
     * @inheritdoc
     */
    public function run()
    {
        if (Yii::$app->request->isPost) {
            $model = new Upload;

            $model->validators[] = Validator::createValidator(
                $this->_validator,
                $model,
                $model->attributes(),
                $this->_validatorOptions
            );

            $model->file = UploadedFile::getInstanceByName($this->fileVar);

            if ($model->validate()) {
                if ($this->unique === true && $model->file->extension) {
                    $model->file->name = uniqid() . '.' . $model->file->extension;
                }

                $model->file->saveAs( $this->getSavePath($model->file->name) );

                return Json::encode([ 'name' => $model->file->name ]);
            }

            return Json::encode([ 'error' => $model->getFirstError('file') ]);
        }

        throw new BadRequestHttpException("Allowed only POST method");
    }

    /**
     * @param string $fileName Имя загружаемого файла.
     *
     * @return string Полный путь до папки куда нужно сохранить файл.
     */
    protected function getSavePath($fileName)
    {
        return (FileHelper::createDirectory($this->path))
            ? $this->path . $fileName
            : null;
    }
} 