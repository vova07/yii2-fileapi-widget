<?php

namespace vova07\fileapi;

use yii\web\AssetBundle;

/**
 * Single upload asset bundle.
 */
class SingleAsset extends AssetBundle
{
    /**
     * @inheritdoc
     */
	public $sourcePath = '@vova07/fileapi/assets';

    /**
     * @inheritdoc
     */
	public $css = [
	    'css/single.css'
	];

    /**
     * @inheritdoc
     */
	public $depends = [
		'vova07\fileapi\Asset'
	];
}
