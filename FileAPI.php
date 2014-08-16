<?php
/**
 * @link http://astwell.com/
 * @copyright Copyright (c) 2014 Astwell Soft
 * @license http://astwell.com/license/
 */

namespace lembadm\fileapi;

use lembadm\fileapi\assets\MultipleAsset;
use lembadm\fileapi\assets\SingleAsset;
use Yii;
use yii\helpers\FileHelper;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\web\JsExpression;
use yii\web\YiiAsset;
use yii\widgets\InputWidget;

class FileAPI extends InputWidget
{
    /**
     * @var string Идентификатор шаблона с разметкой для плагина.
     * Параметр позволяет инициализировать плагин виджета с собсвенной разметкой.
     */
    public $selector;

    /**
     * @var string Название файлового поля.
     * Соответсвенно так же будет называтся $_FILES переменная с переданными файлами.
     */
    public $fileVar = 'file';

    /**
     * Настройки виджета
     * @var array {@link https://github.com/RubaXa/jquery.fileapi/ FileAPI options}.
     */
    public $settings = [];

    /**
     * @var array Настройки по умолчанию для виджета с одиночной загрузкой.
     */
    protected $_defaultSettingsSingle = [
        'autoUpload' => true,
        'elements' => [
            'progress' => '.uploader-progress-bar',
            'active' => [
                'show' => '.uploader-progress',
                'hide' => '.uploader-browse'
            ]
        ]
    ];

    /**
     * @var array Настройки по умолчанию для мульти-загрузочного виджета.
     */
    protected $_defaultSettingsMultiple = [
        'autoUpload' => true,
        'elements' => [
            'list' => '.uploader-files',
            'file' => [
                'tpl' => '.uploader-file-tpl',
                'progress' => '.uploader-file-progress-bar',
                'preview' => [
                    'el' => '.uploader-file-preview',
                    'width' => 100,
                    'height' => 100
                ],
                'upload' => [
                    'show' => '.uploader-file-progress'
                ],
                'complete' => [
                    'hide' => '.uploader-file-progress'
                ]
            ],
            'dnd' => [
                'el' => '.uploader-dnd',
                'hover' => 'uploader-dnd-hover',
                'fallback' => '.uploader-dnd-not-supported'
            ]
        ]
    ];

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        Yii::setAlias('@fileapi', FileHelper::normalizePath( __DIR__ ));

        $request = Yii::$app->getRequest();

        // Регистрируем переводы виджета.
        $this->registerTranslations();

        // Если CSRF защита включена, добавляем токен в запросы виджета.
        if ($request->enableCsrfValidation) {
            $this->settings['data'][$request->csrfParam] = $request->getCsrfToken();
        }

        // Определяем URL загрузки файлов по умолчанию
        if (!isset($this->settings['url'])) {
            $this->settings['url'] = Yii::$app->getRequest()->getUrl();
        }

        $settings = $this->isMultiple()
            ? $this->_defaultSettingsMultiple
            : $this->_defaultSettingsSingle;

        $settings['onFileComplete'] = $this->setCallback();

        // Объединяем настройки виджета
        $this->settings = array_merge($settings, $this->settings);
    }

    /**
     * @inheritdoc
     */
    public function run()
    {
        $this->registerClientScript();

        if ($this->selector === null) {
            if ($this->isMultiple()) {
                echo $this->render('multiple', [
                    'selector' => $this->getId(),
                    'fileVar'  => $this->fileVar
                ]);
            }
            else {
                echo $this->render('single', [
                    'selector' => $this->getId(),
                    'fileVar'  => $this->fileVar,
                    'input'    => $this->hasModel()
                        ? Html::activeHiddenInput($this->model, $this->attribute, $this->options)
                        : Html::hiddenInput($this->name, $this->value, $this->options),
                ]);
            }
        }
    }

    /**
     * Регистрируем AssetBundle-ы виджета.
     */
    public function registerClientScript()
    {
        $view = $this->getView();

        $selector = ($this->selector !== null)
            ? '#' . $this->selector
            : '#' . $this->getId();

        // Инициализируем плагин виджета
        $view->registerJs('jQuery("' . $selector . '").fileapi(' . Json::encode($this->settings) . '");');

        // В случае мульти-загрузки добавляем индекс переменную.
        if ($this->isMultiple()) {
            // Регистрируем мульти-загрузочный бандл виджета
            MultipleAsset::register($view);
            $view->registerJs("var indexKey = 0;");
        }
        else {
            // Регистрируем стандартный бандл виджета
            SingleAsset::register($view);
        }
    }

    /**
     * Регистрируем переводы виджета.
     */
    public function registerTranslations()
    {
        Yii::$app->i18n->translations['lembadm/fileapi/*'] = [
            'class'          => 'yii\i18n\PhpMessageSource',
            'basePath'       => '@fileapi/messages',
            'sourceLanguage' => 'ru',
            'fileMap' => [
                'lembadm/fileapi/fileapi' => 'fileapi.php',
            ],
        ];
    }

    /**
     * Определяем обработчики событий виджета.
     */
    public function setCallback()
    {
        // Определяем мульти-загрузку
        if ($this->isMultiple()) {
            $this->options['id']    = $this->options['id'] . '-{%key}';
            $this->options['value'] = '{%value}';

            $input = $this->hasModel()
                ? Html::activeHiddenInput($this->model, '[{%key}]' . $this->attribute, $this->options)
                : Html::hiddenInput('[{%key}]' . $this->name, $this->value, $this->options);

            return new JsExpression("function (evt, uiEvt) {
				if (uiEvt.result.error) {
					alert(uiEvt.result.error);
				} else {
					var uinput = '$input',
					    uid = FileAPI.uid(uiEvt.file),
					    ureplace = {
					    	'{%key}' : indexKey,
					    	'{%value}' : uiEvt.result.name
					    };
					uinput = uinput.replace(/{%key}|{%value}/gi, function (index) {
						return ureplace[index];
					});
			        ufile = jQuery(this).find('div[data-fileapi-id=\"' + uid + '\"] .uploader-file-fields').html(uinput);
				}
			}");
        }

        return new JsExpression('function (evt, uiEvt) {
            if (uiEvt.result.error) {
                alert(uiEvt.result.error);
            } else {
                jQuery(this).find("#' . $this->options['id'] . '").val(uiEvt.result.name);
            }
        }');
    }

    /**
     * Локальная функция перевода виджета.
     *
     * @param string      $category Категория перевода
     * @param string      $message  Сообщение которое нужно перевести
     * @param array       $params   Массив параметров которые будут заменены на их шаблоны в сообщении
     * @param string|null $language Язык перевода. В случае null, будет использован текущий
     *                              [[\yii\base\Application::language|язык приложения]].
     *
     * @return string
     */
    public static function t($category, $message, $params = [], $language = null)
    {
        return Yii::t('lembadm/fileapi/' . $category, $message, $params, $language);
    }

    /**
     * Проверка на возможность мульти-загрузки
     *
     * @return bool
     */
    private function isMultiple()
    {
        return (isset($this->settings['multiple']) and $this->settings['multiple'] === true);
    }
} 