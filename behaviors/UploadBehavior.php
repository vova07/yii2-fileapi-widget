<?php

namespace vova07\fileapi\behaviors;

use yii\base\Behavior;
use yii\base\InvalidParamException;
use yii\db\ActiveRecord;
use yii\helpers\FileHelper;
use yii\validators\Validator;
use Yii;

/**
 * Class UploadBehavior
 * @package vova07\fileapi\behaviors
 * Uploading file behavior.
 *
 * Usage:
 * ```
 * ...
 * 'uploadBehavior' => [
 *     'class' => UploadBehavior::className(),
 *     'attributes' => [
 *         'preview_url' => [
 *             'path' => '@app/web/path',
 *             'tempPath' => '@app/tmp/path',
 *             'url' => '/path/to/file'
 *         ],
 *         'image_url' => [
 *             'path' => '@app/web/path',
 *             'tempPath' => '@app/tmp/path',
 *             'url' => '/path/to/file'
 *         ]
 *     ]
 * ]
 * ...
 * ```
 */
class UploadBehavior extends Behavior
{
    /**
     * @event Event that will be call after successful file upload
     */
    const EVENT_AFTER_UPLOAD = 'afterUpload';

    /**
     * Are available 3 indexes:
     * - `path` Path where the file will be moved.
     * - `tempPath` Temporary path from where file will be moved.
     * - `url` Path URL where file will be saved.
     *
     * @var array Attributes array
     */
    public $attributes = [];

    /**
     * @var boolean If `true` current attribute file will be deleted
     */
    public $unlinkOnSave = true;

    /**
     * @var boolean If `true` current attribute file will be deleted after model deletion
     */
    public $unlinkOnDelete = true;

    /**
     * @var array Publish path cache array
     */
    protected static $_cachePublishPath = [];

    /**
     * @inheritdoc
     */
    public function attach($owner)
    {
        parent::attach($owner);

        if (!is_array($this->attributes) || empty($this->attributes)) {
            throw new InvalidParamException('Invalid or empty attributes array.');
        } else {
            foreach ($this->attributes as $attribute => $config) {
                if (!isset($config['path']) || empty($config['path'])) {
                    throw new InvalidParamException('Path must be set for all attributes.');
                }
                if (!isset($config['tempPath']) || empty($config['tempPath'])) {
                    throw new InvalidParamException('Temporary path must be set for all attributes.');
                }
                if (!isset($config['url']) || empty($config['url'])) {
                    $config['url'] = $this->publish($config['path']);
                }
                $this->attributes[$attribute]['path'] = FileHelper::normalizePath(Yii::getAlias($config['path'])) . DIRECTORY_SEPARATOR;
                $this->attributes[$attribute]['tempPath'] = FileHelper::normalizePath(Yii::getAlias($config['tempPath'])) . DIRECTORY_SEPARATOR;
                $this->attributes[$attribute]['url'] = rtrim($config['url'], '/') . '/';

                $validator = Validator::createValidator('string', $this->owner, $attribute);
                $this->owner->validators[] = $validator;
                unset($validator);
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function events()
    {
        return [
            ActiveRecord::EVENT_BEFORE_INSERT => 'beforeInsert',
            ActiveRecord::EVENT_BEFORE_UPDATE => 'beforeUpdate',
            ActiveRecord::EVENT_BEFORE_DELETE => 'beforeDelete'
        ];
    }

    /**
     * Function will be called before inserting the new record.
     */
    public function beforeInsert()
    {
        foreach ($this->attributes as $attribute => $config) {
            if ($this->owner->$attribute) {
                $this->saveFile($attribute);
            }
        }
    }

    /**
     * Save model attribute file.
     *
     * @param string $attribute Attribute name
     * @param bool $insert `true` on insert record
     */
    protected function saveFile($attribute, $insert = true)
    {
        if (empty($this->owner->$attribute)) {
            if ($insert !== true) {
                $this->deleteFile($this->oldFile($attribute));
            }
        } else {
            $tempFile = $this->tempFile($attribute);
            $file = $this->file($attribute);

            if (is_file($tempFile) && FileHelper::createDirectory($this->path($attribute))) {
                if (rename($tempFile, $file)) {
                    if ($insert === false && $this->unlinkOnSave === true && $this->owner->getOldAttribute(
                            $attribute
                        )
                    ) {
                        $this->deleteFile($this->oldFile($attribute));
                    }
                    $this->triggerEventAfterUpload();
                } else {
                    unset($this->owner->$attribute);
                }
            } elseif ($insert === true) {
                unset($this->owner->$attribute);
            } else {
                $this->owner->setAttribute($attribute, $this->owner->getOldAttribute($attribute));
            }
        }
    }

    /**
     * Delete specified file.
     *
     * @param string $file File path
     *
     * @return bool `true` if file was successfully deleted
     */
    protected function deleteFile($file)
    {
        if (is_file($file)) {
            return unlink($file);
        }

        return false;
    }

    /**
     * @param string $attribute Attribute name
     *
     * @return string Old file path
     */
    public function oldFile($attribute)
    {
        return $this->path($attribute) . $this->owner->getOldAttribute($attribute);
    }

    /**
     * @param string $attribute Attribute name
     *
     * @return string Path to file
     */
    public function path($attribute)
    {
        return $this->attributes[$attribute]['path'];
    }

    /**
     * @param string $attribute Attribute name
     *
     * @return string Temporary file path
     */
    public function tempFile($attribute)
    {
        return $this->tempPath($attribute) . $this->owner->$attribute;
    }

    /**
     * @param string $attribute Attribute name
     *
     * @return string Path to temporary file
     */
    public function tempPath($attribute)
    {
        return $this->attributes[$attribute]['tempPath'];
    }

    /**
     * @param string $attribute Attribute name
     *
     * @return string File path
     */
    public function file($attribute)
    {
        return $this->path($attribute) . $this->owner->$attribute;
    }

    /**
     * Publish given path.
     *
     * @param string $path Path
     *
     * @return string Published url (/assets/images/image1.png)
     */
    public function publish($path)
    {
        if (!isset(static::$_cachePublishPath[$path])) {
            static::$_cachePublishPath[$path] = Yii::$app->assetManager->publish($path)[1];
        }
        return static::$_cachePublishPath[$path];
    }

    /**
     * Trigger [[EVENT_AFTER_UPLOAD]] event.
     */
    protected function triggerEventAfterUpload()
    {
        $this->owner->trigger(self::EVENT_AFTER_UPLOAD);
    }

    /**
     * Function will be called before updating the record.
     */
    public function beforeUpdate()
    {
        foreach ($this->attributes as $attribute => $config) {
            if ($this->owner->isAttributeChanged($attribute)) {
                $this->saveFile($attribute, false);
            }
        }
    }

    /**
     * Function will be called before deleting the record.
     */
    public function beforeDelete()
    {
        if ($this->unlinkOnDelete) {
            foreach ($this->attributes as $attribute => $config) {
                if ($this->owner->$attribute) {
                    $this->deleteFile($this->file($attribute));
                }
            }
        }
    }

    /**
     * Remove attribute and its file.
     *
     * @param string $attribute Attribute name
     *
     * @return bool Whenever the attribute and its file was removed
     */
    public function removeAttribute($attribute)
    {
        if (isset($this->attributes[$attribute])) {
            if ($this->deleteFile($this->file($attribute))) {
                return $this->owner->updateAttributes([$attribute => null]);
            }
        }

        return false;
    }

    /**
     * @param string $attribute Attribute name
     *
     * @return null|string Full attribute URL
     */
    public function urlAttribute($attribute)
    {
        if (isset($this->attributes[$attribute]) && $this->owner->$attribute) {
            return $this->attributes[$attribute]['url'] . $this->owner->$attribute;
        }

        return null;
    }

    /**
     * @param string $attribute Attribute name
     *
     * @return string Attribute mime-type
     */
    public function getMimeType($attribute)
    {
        return FileHelper::getMimeType($this->file($attribute));
    }

    /**
     * @param string $attribute Attribute name
     *
     * @return boolean Whether file exist or not
     */
    public function fileExists($attribute)
    {
        return file_exists($this->file($attribute));
    }
}
