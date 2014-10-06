<?php

namespace vova07\fileapi;

use yii\web\AssetBundle;

/**
 * Crop asset bundle.
 */
class CropAsset extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public $sourcePath = '@vendor/rubaxa/fileapi';

    /**
     * @inheritdoc
     */
	public $css = [
	    'jcrop/jquery.Jcrop.min.css'
	];

    /**
     * @inheritdoc
     */
	public $js = [
	    'jcrop/jquery.Jcrop.min.js'
	];

    /**
     * @inheritdoc
     */
	public $depends = [
		'vova07\fileapi\Asset',
        'yii\bootstrap\BootstrapAsset',
        'yii\bootstrap\BootstrapPluginAsset',
	];
}
