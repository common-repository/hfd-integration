<?php
/**
 * Created by PhpStorm.
 * Date: 6/5/18
 * Time: 4:18 PM
 */
?>
<div id="israelpost-additional">
    <?php if ($layout == 'map') : ?>
        <div class="spot-detail">
            <?php if ($spotInfo) : ?>
                <input type="hidden" id="israelpost-spot-id" value="<?php echo esc_attr($spotInfo['n_code']) ?>" />
                <strong><?php echo __('Branch name', HFD_WC_EPOST) ?>:</strong> <?php echo ($spotInfo['name']) ?> <br />
                <strong><?php echo __('Branch address', HFD_WC_EPOST) ?>:</strong> <?php echo ($spotInfo['street']) ?> <?php echo ($spotInfo['house']) ?>, <?php echo ($spotInfo['city']) ?> <br />
                <strong><?php echo __('Operating hours', HFD_WC_EPOST) ?>:</strong> <?php echo ($spotInfo['remarks']) ?> <br />
            <?php endif ?>
        </div>
        <p>
            <a href="javascript:void(0);" class="spot-picker">
                <?php echo !$spotInfo ? __('Choose pickup branch', HFD_WC_EPOST) : __('Change pickup branch', HFD_WC_EPOST) ?>
            </a>
        </p>
    <?php else: ?>
        <div class="spot-list-container">
            <div class="field">
                <select id="city-list" <?php if ($spotInfo) : ?>data-selected="<?php echo esc_attr($spotInfo['city']) ?>" <?php endif; ?>>
                    <option value=""><?php echo __('Select city', HFD_WC_EPOST) ?></option>
                </select>
            </div>
            <div class="field">
                <select id="spot-list" <?php if ($spotInfo) : ?>data-selected="<?php echo $spotInfo['n_code'] ?>" <?php endif; ?>>
                    <option value=""><?php echo __('Select pickup point', HFD_WC_EPOST) ?></option>
                </select>
            </div>
            <div class="spot-message"><?php echo __('Please choose pickup branch', HFD_WC_EPOST) ?></div>
        </div>
    <?php endif; ?>
</div>