<?php
/**
 * Created by PhpStorm.
 * Date: 6/6/18
 * Time: 6:12 PM
 */
?>
<?php if ($spotInfo) : ?>
<div class="spot-detail">
    <strong><?php echo __('Branch name', HFD_WC_EPOST) ?>:</strong> <?php echo ($spotInfo['name']) ?> <br />
    <strong><?php echo __('Branch address', HFD_WC_EPOST) ?>:</strong> <?php echo ($spotInfo['street']) ?> <?php echo ($spotInfo['house']) ?>, <?php echo ($spotInfo['city']) ?> <br />
    <strong><?php echo __('Operating hours', HFD_WC_EPOST) ?>:</strong> <?php echo ($spotInfo['remarks']) ?> <br />
</div>
<?php endif ?>