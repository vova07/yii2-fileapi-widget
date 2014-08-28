<?php

namespace vova07\fileapi\actions;

use yii\base\Action;
use yii\base\InvalidConfigException;
use yii\helpers\FileHelper;
use Yii;

/**
 * DeleteAction for images and files.
 *
 * Usage:
 * ```php
 * public function actions()
 * {
 *     return [
 *         'delete-file' => [
 *             'class' => 'vova07\fileapi\actions\DeleteAction',
 *             'path' => '@path/to/files'
 *         ]
 *     ];
 * }
 * ```
 */
class DeleteAction extends Action
{
    /**
     * @var string Path to directory where files has been uploaded
     */
    public $path;

    /**
     * @var string Variable's name that FileAPI sent upon image/file upload.
     */
    public $uploadParam = 'file';

    /**
     * @inheritdoc
     */
    public function init()
    {
        if ($this->path === null) {
            throw new InvalidConfigException("Empty \"{$this->path}\".");
        } else {
            $this->path = FileHelper::normalizePath($this->path) . DIRECTORY_SEPARATOR;
        }
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