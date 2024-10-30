<?php
/**
 * Created by PhpStorm.
 * Date: 6/6/18
 * Time: 3:10 PM
 */

$setting = \Hfd\Woocommerce\Container::get('Hfd\Woocommerce\Setting');
$layout = $setting->get('betanet_epost_layout');
?>
<?php if ($layout == 'map') : ?>
    <div id="israelpost-modal" style="display: none">
        <div id="israelpost-autocompelete">
            <input id="pac-input" class="controls" type="text" placeholder="<?php echo esc_attr(__('Please enter an address', HFD_WC_EPOST)) ?>" />
        </div>
        <div id="legend" style="height: 45px;" class="pac-inner">
            <div style="float: left;">
                <span><?php echo __('Lockers', HFD_WC_EPOST) ?></span>
                <img src="<?php echo $this->getSkinUrl('images/red-dot.png')?>" alt="" width="20" height="32" />
            </div>
            <div style="float: left;">
                <span><?php echo __('Store', HFD_WC_EPOST) ?></span>
                <img src="<?php echo $this->getSkinUrl('images/grn-dot.png') ?>" alt="" width="20" height="32" />
            </div>
        </div>
        <div id="israelpost-map" style="width: 100%; max-width: 750px; height: 450px;"></div>
    </div>
	<?php
	wp_enqueue_script( 'betanet-gscript', '//maps.googleapis.com/maps/api/js?v=3.37&libraries=places&language=he&key='.$setting->getGoogleApiKey() );
	wp_enqueue_script( 'betanet-gmaps', $this->getSkinUrl( 'js/infobox.js' ) );
	wp_enqueue_script( 'betanet-common-js', $this->getSkinUrl( 'js/common.js' ) );
	wp_enqueue_script( 'betanet-gmap-js', $this->getSkinUrl( 'js/map.js' ) );
	wp_enqueue_script( 'betanet-pickup-post', $this->getSkinUrl( 'js/pickup-post.js' ) );
	wp_enqueue_script( 'betanet-checkout-js', $this->getSkinUrl( 'js/checkout.js' ) );
	wp_enqueue_script( 'betanet-translator-js', $this->getSkinUrl( 'js/translator.js' ) );
	?>
    <script type="text/javascript">
        var $j
        document.addEventListener("DOMContentLoaded", function() {
            $j = jQuery;
            Translator.add('Select','<?php echo __('Select', HFD_WC_EPOST) ?>');
            Translator.add('Change pickup branch','<?php echo __('Change pickup branch', HFD_WC_EPOST) ?>');
            Translator.add('Please wait','<?php echo __('Please wait', HFD_WC_EPOST) ?>');
            Translator.add('Branch name','<?php echo __('Branch name', HFD_WC_EPOST) ?>');
            Translator.add('Branch address','<?php echo __('Branch address', HFD_WC_EPOST) ?>');
            Translator.add('Operating hours','<?php echo __('Operating hours', HFD_WC_EPOST) ?>');
            Translator.add('Please choose pickup branch','<?php echo __('Please choose pickup branch', HFD_WC_EPOST) ?>');
            Translator.add('Select a collection point','<?php echo __('Select a collection point', HFD_WC_EPOST) ?>');
            IsraelPostCommon.init({
                saveSpotInfoUrl: '<?php echo admin_url('admin-ajax.php'); ?>',
                getSpotsUrl: '<?php echo admin_url('admin-ajax.php?action=get_spots'); ?>',
                redDotPath: '<?php echo $this->getSkinUrl('images/red-dot.png') ?>',
                grnDotPath: '<?php echo $this->getSkinUrl('/images/grn-dot.png') ?>'
            });
        });
    </script>
<?php else:

	$helper = \Hfd\Woocommerce\Container::get('Hfd\Woocommerce\Helper\Spot');
	wp_enqueue_script( 'betanet-translator', $this->getSkinUrl( 'js/translator.js' ) );
	wp_enqueue_script( 'betanet-epost-list', $this->getSkinUrl( 'js/epost-list.js' ), array(), time() );
    ?>
    <script type="text/javascript">
        document.addEventListener("DOMContentLoaded", function() {
            Translator.add('Select a collection point','<?php echo __('Select a collection point', HFD_WC_EPOST) ?>');
            Translator.add('Select pickup point','<?php echo __('Select pickup point', HFD_WC_EPOST) ?>');
            Translator.add('There is no pickup point','<?php echo __('There is no pickup point', HFD_WC_EPOST) ?>');
            EpostList.init({
                saveSpotInfoUrl: '<?php echo admin_url('admin-ajax.php'); ?>',
                getSpotsUrl: '<?php echo admin_url('admin-ajax.php?action=get_spots'); ?>',
                cities: <?php echo json_encode($helper->getCities())?>
            });
        });
    </script>
<?php endif; ?>
