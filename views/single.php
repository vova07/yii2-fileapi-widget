<?php

/**
 * Single upload view.
 *
 * @var \yii\web\View $this View
 * @var \yii\helpers\Html $input Hidden input
 * @var string $selector Widget ID selector
 * @var string $paramName The parameter name for the file form data
 * @var string $value Current file name
 * @var boolean $preview Enable/disable preview
 * @var boolean $crop Enable/disable crop
 */

use vova07\fileapi\Widget;

?>
    <div id="<?= $selector; ?>" class="uploader">
        <div class="btn btn-default js-fileapi-wrapper col-sm-12">
            <div class="uploader-browse" data-fileapi="active.hide">
                <span class="glyphicon glyphicon-picture"></span>
            <span data-fileapi="browse-text" class="<?= $value ? 'hidden' : 'browse-text' ?>">
                <?= Widget::t('fileapi', 'BROWSE_BTN') ?>
            </span>
                <span data-fileapi="name"></span>
                <input type="file" name="<?= $paramName ?>">
            </div>
            <div class="uploader-progress" data-fileapi="active.show">
                <div class="progress progress-striped">
                    <div class="uploader-progress-bar progress-bar progress-bar-info" data-fileapi="progress"
                         role="progressbar" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
            </div>
        </div>
        <?php if ($preview === true) : ?>
            <a href="#" class="uploader-preview">
                <span data-fileapi="delete" class="uploader-preview-delete"><span
                        class="glyphicon glyphicon-trash"></span></span>
                <span data-fileapi="preview"></span>
            </a>
        <?php endif; ?>
        <?= $input ?>
    </div>

<?php if ($crop === true) : ?>
    <div id="modal-crop" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title" id="myModalLabel"><?= Widget::t('fileapi', 'MODAL_TITLE') ?></h4>
                </div>
                <div class="modal-body">
                    <div id="modal-preview"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal"><?= Widget::t('fileapi', 'MODAL_CANCEL') ?></button>
                    <button type="button" class="btn btn-primary crop"><?= Widget::t('fileapi', 'MODAL_SAVE') ?></button>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>