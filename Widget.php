<?php

namespace vova07\fileapi;

use yii\helpers\ArrayHelper;
use yii\helpers\Url;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\web\JsExpression;
use yii\widgets\InputWidget;
use Yii;

/**
 * FileAPI Class
 * Wrapper widget for {@link https://github.com/RubaXa/jquery.fileapi/ FileAPI} plugin.
 * @todo Multiple files upload
 *
 * Usage:
 * ```
 * ...
 * echo \vova07\fileapi\Widget::widget([
 *     'name' => 'fileapi',
 *     'settings' => [
 *         'autoUpload' => true
 *     ]
 * ]);
 * ...
 * ```
 * or
 * ```
 * ...
 * echo $form->field($model, 'file')->widget('vova07\fileapi\Widget', [
 *     'settings' => [
 *         'autoUpload' => true
 *     ]
 * ]);
 * ...
 * ```
 */
class Widget extends InputWidget
{
    /**
     * @var string FileAPI selector
     */
    public $selector;

    /**
     * @var string The parameter name for the file form data (the request argument name)
     */
    public $paramName = 'file';

    /**
     * Widget settings.
     *
     * @var array {@link https://github.com/RubaXa/jquery.fileapi/ FileAPI options}
     */
    public $settings = [];

    /**
     * @var string Widget template view
     *
     * @see \yii\base\Widget::render
     */
    public $template;

    /**
     * @var array FileAPI events array
     */
    public $callbacks = [];

    /**
     * @var boolean Enable/disable files preview
     */
    public $preview = true;

    /**
     * @var boolean Enable/disable crop
     */
    public $crop = false;

    /**
     * @var array JCrop settings
     */
    public $jcropSettings = [
        'aspectRatio' => 1,
        'bgColor' => '#ffffff',
        'maxSize' => [568, 800],
        'minSize' => [100, 100],
        'keySupport' => false, // Important param to hide jCrop radio button.
        'selection' => '100%'
    ];

    /**
     * @var integer|null Crop resize width
     */
    public $cropResizeWidth;

    /**
     * @var integer|null Crop resize height
     */
    public $cropResizeHeight;

    /**
     * @var array Default settings array
     */
    private $_defaultSettings;

    /**
     * @var array Default settings array for single upload
     */
    private $_defaultSingleSettings = [
        'autoUpload' => true,
        'elements' => [
            'progress' => '[data-fileapi="progress"]',
            'active' => [
                'show' => '[data-fileapi="active.show"]',
                'hide' => '[data-fileapi="active.hide"]'
            ],
            'name' => '[data-fileapi="name"]',
            'preview' => [
                'el' => '[data-fileapi="preview"]',
                'width' => 100,
                'height' => 100
            ]
        ]
    ];

    /**
     * @var array Default settings array for multiple upload
     */
    private $_defaultMultipleSettings = [
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
     * Widget translation function.
     *
     * @param string $category the message category.
     * @param string $message the message to be translated.
     * @param array $params the parameters that will be used to replace the corresponding placeholders in the message.
     * @param string $language the language code (e.g. `en-US`, `en`). If this is null, the current
     * [[\yii\base\Application::language|application language]] will be used.
     *
     * @return string the translated message.
     */
    public static function t($category, $message, $params = [], $language = null)
    {
        return Yii::t('vova07/' . $category, $message, $params, $language);
    }

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        $request = Yii::$app->getRequest();

        $this->registerTranslations();

        if ($request->enableCsrfValidation === true) {
            $this->settings['data'][$request->csrfParam] = $request->getCsrfToken();
        }
        if (!isset($this->settings['url'])) {
            $this->settings['url'] = $request->getUrl();
        } else {
            $this->settings['url'] = Url::to($this->settings['url']);
        }
        if ($this->crop === true) {
            $this->settings['autoUpload'] = false;
        }
        if (isset($this->settings['multiple']) && $this->settings['multiple'] === true) {
            if ($this->template === null) {
                $this->template = 'multiple';
            }
            if ($this->preview === false) {
                unset($this->_defaultMultipleSettings['elements']['file']['preview']);
            }
            $this->_defaultSettings = $this->_defaultMultipleSettings;
        } else {
            if ($this->template === null) {
                $this->template = 'single';
            }
            if ($this->preview === false) {
                unset($this->_defaultSingleSettings['elements']['preview']);
            }
            $this->_defaultSettings = $this->_defaultSingleSettings;
        }
        $this->settings = ArrayHelper::merge($this->_defaultSettings, $this->settings);
    }

    /**
     * Register widget translations.
     */
    public function registerTranslations()
    {
        if (!isset(Yii::$app->i18n->translations['vova07/fileapi']) && !isset(Yii::$app->i18n->translations['vova07/*'])) {
            Yii::$app->i18n->translations['vova07/fileapi'] = [
                'class' => 'yii\i18n\PhpMessageSource',
                'basePath' => '@vova07/fileapi/messages',
                'fileMap' => [
                    'vova07/fileapi' => 'fileapi.php'
                ],
                'forceTranslation' => true
        ];
        }
    }

    /**
     * @inheritdoc
     */
    public function run()
    {
        $this->registerFiles();
        $this->register();

        if ($this->selector === null) {
            if (isset($this->settings['multiple']) && $this->settings['multiple'] === true) {

                return $this->render(
                    $this->template,
                    [
                        'selector' => $this->options['id'],
                        'paramName' => $this->paramName
                    ]
                );
            } else {
                $input = $this->hasModel() ? Html::activeHiddenInput(
                    $this->model,
                    $this->attribute,
                    $this->options
                ) : Html::hiddenInput($this->name, $this->value, $this->options);

                return $this->render(
                    $this->template,
                    [
                        'selector' => $this->getSelector(),
                        'input' => $input,
                        'paramName' => $this->paramName,
                        'value' => $this->model->{$this->attribute},
                        'preview' => $this->preview,
                        'crop' => $this->crop
                    ]
                );
            }
        }
    }

    /**
     * Registering already uploaded files.
     */
    public function registerFiles()
    {
        if (!isset($this->settings['multiple']) || $this->settings['multiple'] === false) {
            if ($this->hasModel() && $this->model->{$this->attribute} && $this->model->fileExists($this->attribute)) {
                $this->settings['files'][] = [
                    'src' => $this->model->urlAttribute($this->attribute),
                    'name' => $this->model->{$this->attribute},
                    'type' => $this->model->getMimeType($this->attribute)
                ];
            }
        }
    }

    /**
     * Register all widget scripts and callbacks
     */
    public function register()
    {
        $this->registerMainClientScript();
        $this->registerClientScript();
        $this->registerDefaultCallbacks();
        $this->registerCallbacks();
    }

    /**
     * Register widget main asset.
     */
    protected function registerMainClientScript()
    {
        $selector = $this->getSelector();
        $options = Json::encode($this->settings);
        $view = $this->getView();

        Asset::register($view);
        $view->registerJs("jQuery('#$selector').fileapi($options);");
    }

    /**
     * @return string Widget selector
     */
    public function getSelector()
    {
        return $this->selector !== null ? $this->selector : 'uploader-' . $this->options['id'];
    }

    /**
     * Register widget asset.
     */
    public function registerClientScript()
    {
        $view = $this->getView();
        $selector = $this->getSelector();

        if (isset($this->settings['multiple']) && $this->settings['multiple'] === true) {
            // MultipleAsset::register($view);
        } else {
            SingleAsset::register($view);
            if ($this->preview === true) {
                $view->registerJs(
                    "jQuery(document).on('click', '#$selector [data-fileapi=\"delete\"]', function(evt) {" .
                        "evt.preventDefault();" .
                        "var uploader = jQuery(this).closest('#$selector');" .
                        "uploader.fileapi('clear');" .
                        "uploader.find('[data-fileapi=\"browse-text\"]').removeClass('hidden');" .
                        "uploader.find('input[type=\"hidden\"]').val('');" .
                    "})"
                );
            }
        }
        if ($this->crop === true) {
            CropAsset::register($view);
        }
    }

    /**
     * Register default widget callbacks
     */
    protected function registerDefaultCallbacks()
    {
        // File complete handler
        $this->callbacks['filecomplete'][] = new JsExpression('function (evt, uiEvt) {' .
            'if (uiEvt.result.error) {' .
                'alert(uiEvt.result.error);' .
            '} else {' .
                'jQuery(this).find("input[type=\"hidden\"]").val(uiEvt.result.name);' .
                'jQuery(this).find("[data-fileapi=\"browse-text\"]").addClass("hidden");' .
                'jQuery(this).find("[data-fileapi=\"delete\"]").attr("data-fileapi-uid", FileAPI.uid(uiEvt.file));' .
            '}' .
        '}');

        if ($this->crop === true) {
            $view = $this->getView();
            $selector = $this->getSelector();
            $jcropSettings = Json::encode($this->jcropSettings);

            if ($this->cropResizeWidth !== null && $this->cropResizeHeight !== null) {
                $cropResizeJs = "el.fileapi('resize', ufile, $this->cropResizeWidth, $this->cropResizeHeight);";
            } else {
                $cropResizeJs = '';
            }
            // Add event handler for crop button
            $view->registerJs('jQuery(document).on("click", "#modal-crop .crop", function() {' .
                '$("#' . $selector . '").fileapi("upload");' .
                'jQuery("#modal-crop").modal("hide");' .
            '});');
            // Crop event handler
            $this->callbacks['select'] = new JsExpression('function (evt, ui) {' .
                'var ufile = ui.files[0],' .
                'jcropSettings = ' . $jcropSettings . ',' .
                'el = jQuery(this);' .
                'if (ufile) {' .
                    'jcropSettings.file = ufile;' .
                    'jcropSettings.onSelect = function (coordinates) {' .
                        '$("#' . $selector . '").fileapi("crop", ufile, coordinates);' .
                        $cropResizeJs .
                    '};' .
                    'jQuery("#modal-crop").modal("show");' .
                    'setTimeout(function () {' .
                        '$("#modal-preview").cropper(jcropSettings);' .
                    '}, 700);' .
                '}' .
            '}');
        }
    }

    /**
     * Register widget callbacks.
     */
    protected function registerCallbacks()
    {
        if (!empty($this->callbacks)) {
            $selector = $this->getSelector();
            $view = $this->getView();
            foreach ($this->callbacks as $event => $callback) {
                if (is_array($callback)) {
                    foreach ($callback as $function) {
                        if (!$function instanceof JsExpression) {
                            $function = new JsExpression($function);
                        }
                        $view->registerJs("jQuery('#$selector').on('$event', $function);");
                    }
                } else {
                    if (!$callback instanceof JsExpression) {
                        $callback = new JsExpression($callback);
                    }
                    $view->registerJs("jQuery('#$selector').on('$event', $callback);");
                }
            }
        }
    }
}