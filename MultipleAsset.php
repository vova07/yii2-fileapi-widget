<?php

namespace vova07\fileapi;

use yii\web\AssetBundle;

/**
 * Multiple upload asset bundle.
 */
class MultipleAsset extends AssetBundle
{
    /**
     * @inheritdoc
     */
	public $sourcePath = '@vova07/fileapi/assets';

    /**
     * @inheritdoc
     */
	public $css = [
	    'css/multiple.css'
	];

    /**
     * @inheritdoc
     */
	public $depends = [
		'vova07\fileapi\Asset'
	];
}
