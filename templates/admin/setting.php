<?php
/**
 * Created by PhpStorm.
 * Date: 6/7/18
 * Time: 8:55 AM
 */
/* @var \Hfd\Woocommerce\Setting $setting */

$order_auto_sync = $setting->get( 'hfd_order_auto_sync' );
$hfd_auto_sync_status = $setting->get( 'hfd_auto_sync_status' );
$hfd_auto_sync_time = $setting->get( 'hfd_auto_sync_time' );
if( empty( $order_auto_sync ) ){
	$order_auto_sync = 'no';
}
?>
<div id="ch2pho-general" class="wrap">
    <h2><?php echo __('HFD Sync Settings', HFD_WC_EPOST) ?></h2>
    <form method="post" action="admin-post.php">

        <input type="hidden" name="action" value="save_epost_setting"/>

        <!-- Adding security through hidden referrer field -->
        <?php wp_nonce_field('epost_setting'); ?>
        <table class="form-table">
            <tbody>
                <tr valign="top">
                    <th scope="row"><?php _e('Layout', HFD_WC_EPOST); ?></th>
                    <td>
                        <select name="betanet_epost_layout" class="regular-text">
                            <option value="map"><?php _e('Map', HFD_WC_EPOST) ?></option>
                            <option value="list" <?php if ($setting->get('betanet_epost_layout') == 'list') : ?>selected<?php endif ?>><?php _e('List', HFD_WC_EPOST) ?></option>
                        </select>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php _e('Google map API key', HFD_WC_EPOST); ?></th>
                    <td>
                        <input type="text" name="betanet_epost_google_api_key" class="regular-text"
                               value="<?php echo $setting->get('betanet_epost_google_api_key'); ?>"/>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php _e('Use custom jQuery UI', HFD_WC_EPOST); ?></th>
                    <td>
                        <select name="betanet_epost_active_direct_jquery" class="regular-text">
                            <option value="0"><?php _e('No', HFD_WC_EPOST) ?></option>
                            <option value="1" <?php if ($setting->get('betanet_epost_active_direct_jquery')) : ?>selected<?php endif ?>><?php _e('Yes', HFD_WC_EPOST) ?></option>
                        </select>
                    </td>
                </tr>
            </tbody>
        </table>
        <h3><?php _e('HFD Configuration', HFD_WC_EPOST) ?></h3>
        <table class="form-table">
            <tbody>
                <tr valign="top">
                    <th scope="row"><?php _e('Enable HFD Integration', HFD_WC_EPOST); ?></th>
                    <td>
                        <select name="betanet_epost_hfd_active" class="regular-text">
                            <option value="0"><?php _e('No', HFD_WC_EPOST) ?></option>
                            <option value="1" <?php if ($setting->get('betanet_epost_hfd_active')) : ?>selected<?php endif ?>><?php _e('Yes', HFD_WC_EPOST) ?></option>
                        </select>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php _e('Authorization token', HFD_WC_EPOST); ?></th>
                    <td>
                        <input type="text" name="betanet_epost_hfd_auth_token" class="regular-text"
                           value="<?php echo $setting->get('betanet_epost_hfd_auth_token'); ?>" />
                    </td>
                </tr>
                <tr valign="top">
                    <?php
                        $allowShippingMethods = $setting->get('betanet_epost_hfd_shipping_method');
                        if (!$allowShippingMethods) {
                            $allowShippingMethods = array();
                        }
                    ?>
                    <th scope="row"><?php _e('Allow shipping methods', HFD_WC_EPOST); ?></th>
                    <td>
                        <select name="betanet_epost_hfd_shipping_method[]" class="regular-text" multiple>
                            <?php foreach (WC()->shipping->load_shipping_methods() as $method) : ?>
                                <option value="<?php echo esc_attr($method->id) ?>" <?php if (in_array($method->id, $allowShippingMethods)) : ?>selected<?php endif ?>>
                                    <?php echo esc_attr($method->get_method_title()) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php _e('Company name', HFD_WC_EPOST); ?></th>
                    <td>
                        <input type="text" name="betanet_epost_hfd_sender_name" class="regular-text"
                               maxlength="8"
                               value="<?php echo $setting->get('betanet_epost_hfd_sender_name'); ?>"/>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php _e('Customer number', HFD_WC_EPOST); ?></th>
                    <td>
                        <input type="text" name="betanet_epost_hfd_customer_number" class="regular-text"
                               value="<?php echo $setting->get('betanet_epost_hfd_customer_number'); ?>"/>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php _e('Api Debug', HFD_WC_EPOST); ?></th>
                    <td>
                        <select name="betanet_epost_hfd_debug" class="regular-text">
                            <option value="0"><?php _e('No', HFD_WC_EPOST) ?></option>
                            <option value="1" <?php if ($setting->get('betanet_epost_hfd_debug')) : ?>selected<?php endif ?>><?php _e('Yes', HFD_WC_EPOST) ?></option>
                        </select>
                    </td>
                </tr>
				<tr valign="top">
                    <th scope="row"><?php _e('Order auto sync', HFD_WC_EPOST); ?></th>
                    <td>
						<div>
							<input type="radio" name="hfd_order_auto_sync" value="yes" <?php checked( $order_auto_sync, 'yes' ); ?> /> <?php _e( 'Yes', HFD_WC_EPOST ); ?>
						</div>
						<div>
							<input type="radio" name="hfd_order_auto_sync" value="no" <?php checked( $order_auto_sync, 'no' ); ?> /> <?php _e( 'No', HFD_WC_EPOST ); ?>
						</div>
                    </td>
                </tr>
				<tr valign="top">
                    <th scope="row"><?php _e('Auto sync status', HFD_WC_EPOST); ?></th>
                    <td>
                        <select name="hfd_auto_sync_status" class="regular-text">
							<option value=""><?php _e( '--Select--', HFD_WC_EPOST ); ?></option>
                            <?php
							if( function_exists( 'wc_get_order_statuses' ) ){
								$statuses = wc_get_order_statuses();
								if( $statuses ){
									foreach( $statuses as $statusCode => $status ){
										echo '<option value="'.$statusCode.'" ';
										echo selected( $hfd_auto_sync_status, $statusCode );
										echo '>'.$status.'</option>';
									}
								}
							}
							?>
                        </select>
                    </td>
                </tr>
				<tr valign="top">
                    <th scope="row"><?php _e( 'Auto sync time', HFD_WC_EPOST ); ?></th>
                    <td>
                        <select name="hfd_auto_sync_time" class="regular-text">
							<option value=""><?php _e( '--Select--', HFD_WC_EPOST ); ?></option>
							<option value="15" <?php selected( $hfd_auto_sync_time, 15 ); ?>>15</option>
							<option value="30" <?php selected( $hfd_auto_sync_time, 30 ); ?>>30</option>
							<option value="60" <?php selected( $hfd_auto_sync_time, 60 ); ?>>60</option>
                        </select>
                    </td>
                </tr>
            </tbody>
        </table>
        <input type="submit" value="<?php _e('Save Changes', HFD_WC_EPOST); ?>" class="button-primary"/>
    </form>
</div>
