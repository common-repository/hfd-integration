<?php
/**
 * Created by PhpStorm.
 * Date: 6/7/18
 * Time: 8:45 AM
 */
namespace Hfd\Woocommerce;

use Hfd\Woocommerce\Container;
use Hfd\Woocommerce\Template;

class Admin
{
    const PLUGIN_SLUG = 'betanet-epost';

    public function init()
    {
        $this->registerHook();
    }

    /**
     * Register admin hooks
     */
    public function registerHook()
    {
//        add_filter('woocommerce_admin_order_actions', array($this, 'registerHfdButton'), 10, 2);

        add_filter('manage_edit-shop_order_columns', array($this, 'registerHfdColumn'));
        add_action( 'manage_shop_order_posts_custom_column', array($this, 'renderHfdActions'), 10, 2);
        add_action('admin_menu', array($this, 'registerSettingMenu'));
        add_action('admin_post_save_epost_setting', array($this, 'saveSetting'));
        add_action('admin_enqueue_scripts', array($this, 'registerStyle'));
        add_filter('bulk_actions-edit-shop_order', array($this, 'registerBulkAction'));
        add_filter('handle_bulk_actions-edit-shop_order', array($this, 'handleBulkAction'), 10, 3);
        add_action('admin_notices', array($this, 'showBulkActionNotices'));
        add_action('wp_ajax_sync_order', array($this, 'syncOrderToHfd'));
		
		//add epost location in admin side orders
		add_action( 'woocommerce_admin_order_items_after_fees', array( $this, 'addEpostLocation' ) );
		
		//add js into admin footer
		add_action( 'admin_footer', array( $this, 'addJsIntoAdminFooter' ) );
		
		//add city and location in order meta
		add_action( 'woocommerce_saved_order_items', array( $this, 'addLocationMetaInOrder' ), 10, 2 );
		
		//validate spotlist and city if betanet shipping selected
		add_action( 'woocommerce_before_save_order_items', array( $this, 'validateCityAndSpotNotEmpty' ), 10, 2 );
		
		//add meta box for hfd shipping status
		add_action( 'add_meta_boxes', array( $this, 'registerMetaBoxes' ) );
		
		//cancel shipment ajax
		add_action( 'wp_ajax_hfd_epost_cancel_shipment', array( $this, 'epostCancelEpostShipment' ) );
    }
	
	//cancel epost shipping
	public function epostCancelEpostShipment(){
		$out = array( "success" => 0, "msg" => __( "Something Went wrong", HFD_WC_EPOST ) );
		if( !is_admin() ){
			echo json_encode( $out );
			exit;
		}
		
		if( isset( $_POST['orderRef'] ) && !empty( $_POST['orderRef'] ) ){
			$orN = sanitize_text_field( $_POST['orderRef'] );
			$orderID = sanitize_text_field( $_POST['orderID'] );
			$helper = \Hfd\Woocommerce\Container::get('Hfd\Woocommerce\Setting');
			$authToken = $helper->get( 'betanet_epost_hfd_auth_token' );
			if( !empty( $authToken ) ){
				$cancel_shipment_url = $helper->get( 'betanet_epost_hfd_cancel_shipment_url' );
				$cancel_shipment_url = str_replace( "{shipping_number}", $orN, $cancel_shipment_url );
				$args = array(
					'headers' => array(
						'Authorization' => 'Bearer '.$authToken
					)
				);
				$response_run = wp_remote_get( $cancel_shipment_url, $args );
				$api_response_run = wp_remote_retrieve_body( $response_run );
				$api_response_run = simplexml_load_string( $api_response_run );
				if( $api_response_run === false ){
					$out = array( "success" => 0, "msg" => __( "Shipment not tracked", HFD_WC_EPOST ) );
				}else{
					$api_response_run  = json_encode( $api_response_run );
					$api_response_run = json_decode( $api_response_run, true );
					update_post_meta( $orderID, 'hfd_ship_cancel_response', maybe_serialize( $api_response_run ) );
					if( isset( $api_response_run['Status'] ) && $api_response_run['Status'] == "OK" ){
						$corder = wc_get_order( $orderID );
						if( $corder ){
							$corder->add_order_note( __( "HFD shipment cancelled", HFD_WC_EPOST ) );
						}
						$out = array( "success" => 0, "msg" => __( "HFD shipment cancelled", HFD_WC_EPOST ) );
					}else if( isset( $api_response_run['Status'] ) && $api_response_run['Status'] == "ERROR" ){
						$corder = wc_get_order( $orderID );
						if( $corder ){
							$corder->add_order_note( sprintf( __( "HFD shipment cancelled error : %s", HFD_WC_EPOST ), $api_response_run['Status_desc'] ) );
						}
						$out = array( "success" => 0, "msg" => $api_response_run['Status_desc'] );
					}
				}
			}else{
				$out = array( "success" => 0, "msg" => __( "Authorization token is required", HFD_WC_EPOST ) );
			}
		}
		echo json_encode( $out );
		exit;
	}
	
	/**
	 * Register meta box(es).
	 */
	public function registerMetaBoxes(){
		add_meta_box( 'hfd-epost-metabox', __( 'HFD Shipment Status', HFD_WC_EPOST ), array( $this, 'hfdRegisterMetaboxCallback' ), 'shop_order', 'side' );
	}
	
	/**
	 * Meta box(es) callback.
	 */
	public function hfdRegisterMetaboxCallback( $post ){
		$hfd_sync_flag = (int)get_post_meta( $post->ID, 'hfd_sync_flag', true );
		$hfd_rand_number = get_post_meta( $post->ID, 'hfd_rand_number', true );
		if( $hfd_sync_flag ){
			$helper = \Hfd\Woocommerce\Container::get('Hfd\Woocommerce\Setting');
			$shipment_track_url = $helper->get( 'betanet_epost_hfd_track_shipment_url' );
			$shipment_track_url = str_replace( "{RAND}", $hfd_rand_number, $shipment_track_url );
		?>
			<a href="<?php echo $shipment_track_url; ?>" target="_blank" style="<?php echo ( $hfd_rand_number == "" ) ? "pointer-events: none;" : ""; ?>"><button type="button" class="button epost-check-shipment-status" <?php echo ( $hfd_rand_number == "" ) ? "disabled" : ""; ?>><?php _e( 'Check Status', HFD_WC_EPOST ); ?></button></a>
			<button type="button" class="button epost-cancel-shipment" data-id="<?php echo $post->ID; ?>" data-text="<?php echo $hfd_rand_number; ?>" <?php echo ( $hfd_rand_number == "" ) ? "disabled" : ""; ?>><?php _e( 'Cancel Shipment', HFD_WC_EPOST ); ?></button>
		<?php
		}
	}
	/**
     * validate city and spot
     */
	public function validateCityAndSpotNotEmpty( $order_id, $items ){
		if( isset( $items['shipping_method'] ) && !empty( $items['shipping_method'] ) ){
			$pickup_info = wc_get_order_item_meta( $items['shipping_method_id'][0], 'epost_pickup_info', true );
			if( empty( $pickup_info ) ){
				foreach( $items['shipping_method'] as $shipping ){
					if( $shipping == "betanet_epost" ){
						if( ( isset( $items['city-list'] ) && empty( $items['city-list'] ) ) || ( isset( $items['spot-list'] ) && empty( $items['spot-list'] ) ) ){
							$error_arr = array( "error" => __( 'Please choose pickup branch', HFD_WC_EPOST ) );
							wp_send_json_error( $error_arr );
							exit;
						}
					}
				}
			}
		}
	}
	
	/**
     * save epost info into order item meta
     */
	public function addLocationMetaInOrder( $order_id, $items ){
		if( isset( $items['city-list'] ) && !empty( $items['city-list'] ) && isset( $items['spot-list'] ) && !empty( $items['spot-list'] ) ){
			$city = $items['city-list'];
			$spot = $items['spot-list'];
			
			$response = wp_remote_get( admin_url( "admin-ajax.php?action=get_spots&city=".$city."" ) );
			if( !is_wp_error( $response ) ) {
				$body = $response['body'];
				$spots = (array)json_decode( $body );
				if( isset( $spots[$spot] ) && !empty( $spots[$spot] ) ){
					wc_update_order_item_meta( $items['shipping_method_id'][0], 'epost_pickup_info', serialize( (array)$spots[$spot] ) );
				}
			}
		}
		if( isset( $items['batanet_govina'] ) && !empty( $items['batanet_govina'] ) ){
			update_post_meta( $order_id, 'betanet_pmethod', $items['batanet_govina'] );
		}
	}
	
	/**
     * add js into admin footer
     */
	public function addJsIntoAdminFooter(){
		$helper = \Hfd\Woocommerce\Container::get('Hfd\Woocommerce\Helper\Spot');
		?>
		<script type="text/javascript">
			document.addEventListener("DOMContentLoaded", function() {
				Translator.add('Select a collection point','<?php echo __('Select a collection point', HFD_WC_EPOST) ?>');
				Translator.add('Select pickup point','<?php echo __('Select pickup point', HFD_WC_EPOST) ?>');
				Translator.add('There is no pickup point','<?php echo __('There is no pickup point', HFD_WC_EPOST) ?>');
				EpostList.init({
					saveSpotInfoUrl: '<?php echo admin_url("admin-ajax.php"); ?>',
					getSpotsUrl: '<?php echo admin_url("admin-ajax.php?action=get_spots"); ?>',
					cities: <?php echo json_encode($helper->getCities())?>
				});
			});
		</script>
		<?php
	}
	
	/**
     * add epost option after shipping method
     */
	public function addEpostLocation( $order_id ){
		$order = wc_get_order( $order_id );
		$city = $n_code = $shipping_method = '';
		if( $order ){
			foreach( $order->get_items( 'shipping' ) as $item_id => $shipping_item_obj ){
				$shipping_method = $shipping_item_obj->get_method_id();
				$pickup_info = wc_get_order_item_meta( $item_id, 'epost_pickup_info', true );
				if( !empty( $pickup_info ) ){
					$pickup_info = unserialize( $pickup_info );
					$city = $pickup_info['city'];
					$n_code = $pickup_info['n_code'];
				}
			}
		}
		
		$betanet_pmethod = get_post_meta( $order_id, 'betanet_pmethod', true );
		if( empty( $betanet_pmethod ) ){
			$betanet_pmethod = 'govina_cash';
		}
		?>
		<tr class="israelpost-wrapper">
			<td></td>
			<td colspan="5">
				<div id="israelpost-additional" style="display:none;">
					<div class="spot-list-container">
						<div class="field">
							<select id="city-list" name="city-list" <?php if( !empty( $city ) ): ?>data-selected="<?php echo esc_attr($city ); ?>" <?php endif; ?>>
								<option value=""><?php echo __('Select city', HFD_WC_EPOST) ?></option>
							</select>
						</div>
						<div class="field">
							<select id="spot-list" name="spot-list" <?php if( !empty( $n_code ) ): ?>data-selected="<?php echo esc_attr($n_code ); ?>" <?php endif; ?>>
								<option value=""><?php echo __('Select pickup point', HFD_WC_EPOST) ?></option>
							</select>
						</div>
					</div>
				</div>
			</td>
		</tr>
		<tr class="betanetgovina-wrapper">
			<td></td>
			<td colspan="5" id="betanetgovina-wrapper" style="<?php echo ( $shipping_method != "betanet_govina" ) ? 'display:none;' : '';  ?>">
				<div>
					<span>
						<input type="radio" name="batanet_govina" value="govina_cash" <?php checked( $betanet_pmethod, 'govina_cash' ); ?> /><?php echo __( 'Cash', HFD_WC_EPOST ); ?>
					</span>
					<span>
						<input type="radio" name="batanet_govina" value="govina_cheque" <?php checked( $betanet_pmethod, 'govina_cheque' ); ?> /><?php echo __( 'Cheque', HFD_WC_EPOST ); ?>
					</span>
				</div>
			</td>
		</tr>
		<?php
	}
	
    public function testEmail()
    {
        if (isset($_GET['trigger_mail'])) {
            foreach (WC()->mailer()->get_emails() as $email) {
                if ($email->id == 'customer_on_hold_order') {
                    $email->trigger( sanitize_text_field( $_GET['trigger_mail'] ) );
                    var_dump($email);
                }
            }
            exit;
        }
    }

    /**
     * Register admin style
     */
    public function registerStyle()
    {
        wp_enqueue_style('epost-admin-style', HFD_EPOST_PLUGIN_URL.'/css/admin.css' );
        wp_enqueue_script( 'epost-admin-script', HFD_EPOST_PLUGIN_URL.'/js/epost-list.js' );
        wp_enqueue_script( 'epost-translator-script', HFD_EPOST_PLUGIN_URL.'/js/translator.js' );
        wp_enqueue_script( 'epost-admin-scr', HFD_EPOST_PLUGIN_URL.'/js/epost-admin.js' );
    }

    /**
     * Register menu
     */
    public function registerSettingMenu()
    {
        add_submenu_page(
            'woocommerce',
            __('HFD Sync Settings', self::PLUGIN_SLUG),
            __('HFD Sync Settings', self::PLUGIN_SLUG),
            'manage_options',
            'betanet_epost_setting',
            array($this, 'settingPage')
        );
    }

    public function settingPage()
    {
        $setting = Container::get('Hfd\Woocommerce\Setting');
        $template = Container::get('Hfd\Woocommerce\Template');

        echo $template->fetchView('admin/setting.php', array(
            'setting' => $setting
        ));
    }

    public function saveSetting()
    {
        if (!current_user_can('manage_options')) {
            wp_die('Not allowed');
        }

        check_admin_referer('epost_setting');

        if (!empty($_POST)) {
            $settingKeys = array(
                'betanet_epost_layout',
                'betanet_epost_active_direct_jquery',
                'betanet_epost_service_url',
                'betanet_epost_google_api_key',
                'betanet_epost_hfd_active',
                'betanet_epost_hfd_service_url',
                'betanet_epost_hfd_auth_token',
                'betanet_epost_hfd_shipping_method',
                'betanet_epost_hfd_sender_name',
                'betanet_epost_hfd_customer_number',
                'betanet_epost_hfd_debug',
				'hfd_order_auto_sync',
                'hfd_auto_sync_status',
                'hfd_auto_sync_time'
            );

            foreach( $settingKeys as $settingKey ){
                if( isset( $_POST[$settingKey] ) ){
					if( is_array( $_POST[$settingKey] ) ){
						update_option( $settingKey, array_map( 'sanitize_text_field', $_POST[$settingKey] ) );
					}else{
						update_option( $settingKey, sanitize_text_field( $_POST[$settingKey] ) );
					}
                }
            }
        }

        // Redirect the page to the configuration form that was
        // processed
        wp_redirect(add_query_arg('page', 'betanet_epost_setting', admin_url('admin.php')));
        exit;
    }

    /**
     * @param array $actions
     * @param \WC_Order $order
     * @return array
     */
    public function registerHfdButton($actions, $order)
    {
        $setting = Container::get('Hfd\Woocommerce\Setting');
        if (!$setting->get('betanet_epost_hfd_active')
            || $order->get_meta('hfd_sync_flag') == \Hfd\Woocommerce\Helper\Hfd::STATUS_SEND_SUCCESS) {
            return $actions;
        }

        $actions[] = array(
            'action'    => 'sync-to-hfd',
            'url'       => admin_url('admin-ajax.php?action=sync_order&order_id='. $order->get_id()),
            'name'      => __('Sync To HFD', HFD_WC_EPOST)
        );
        return $actions;
    }

    public function registerHfdColumn($columns)
    {
        if (is_array($columns)) {
            $columns['hfd_actions'] = __('HFD Actions', HFD_WC_EPOST);
        }

        return $columns;
    }

    public function renderHfdActions($column, $orderId)
    {
        if ($column !== 'hfd_actions') {
            return;
        }

        $order = wc_get_order($orderId);
        $html = '<p class="wc_actions column-wc_actions">';

        $syncFlag = $order->get_meta('hfd_sync_flag');
        $shipmentNumber = $order->get_meta('hfd_ship_number');
		$printPdfUrl = site_url( '/printLabel/'.$shipmentNumber );

        if ($shipmentNumber && $printPdfUrl
            && $syncFlag == \Hfd\Woocommerce\Helper\Hfd::STATUS_SEND_SUCCESS
        ) {
            $label = __('Print Label', HFD_WC_EPOST);
            $classes = ['button', 'wc-action-button', 'hfd-print-label'];
            $html .= sprintf(
                '<a href="%s" class="%s" aria-label="%s" title="%s" target="_blank">%s</a>',
                $printPdfUrl,
                implode(' ', $classes),
                $label,
                $label,
                $label
            );
        }

        if (!$syncFlag || $syncFlag == \Hfd\Woocommerce\Helper\Hfd::STATUS_SEND_ERROR) {
            $label = __('Sync To HFD', HFD_WC_EPOST);
            $classes = ['button', 'wc-action-button', 'sync-to-hfd'];
            $syncUrl = admin_url('admin-ajax.php?action=sync_order&order_id='. $order->get_id());
            $html .= sprintf(
                '<a href="%s" class="%s" aria-label="%s" title="%s">%s</a>',
                $syncUrl,
                implode(' ', $classes),
                $label,
                $label,
                $label
            );
        }

        $html .= '</p>';
        echo $html;
    }

    /**
     * @param array $actions
     * @return array
     */
    public function registerBulkAction($actions)
    {
        $setting = Container::get('Hfd\Woocommerce\Setting');
        if ($setting->get('betanet_epost_hfd_active')) {
            $actions['sync_order_to_hfd'] = __('Sync Order to HFD', HFD_WC_EPOST);
        }

        return $actions;
    }

    /**
     * @param string $redirectTo
     * @param string $doAction
     * @param array $postIds
     * @return string
     */
    public function handleBulkAction($redirectTo, $doAction, $postIds)
    {
        if ($doAction != 'sync_order_to_hfd') {
            return $redirectTo;
        }

        /* @var \Hfd\Woocommerce\Helper\Hfd $hfdHelper */
        $hfdHelper = Container::create('Hfd\Woocommerce\Helper\Hfd');
        $result = $hfdHelper->sendOrders($postIds);

        $filesystem = Container::get('Hfd\Woocommerce\Filesystem');
        $filesystem->writeSession(serialize($result), 'sync_to_hfd');

        $redirectTo = add_query_arg('sync_to_hfd', 1, $redirectTo);

        return $redirectTo;
    }

    /**
     * Show notice after sync order into HFD
     */
    public function showBulkActionNotices()
    {
        if (isset($_GET['sync_to_hfd'])) {
            $filesystem = Container::get('Hfd\Woocommerce\Filesystem');
            $data = $filesystem->readSession('sync_to_hfd');
            $filesystem->clearSession('sync_to_hfd');

            if ($data) {
                $data = unserialize($data);
            }

            $template = Container::get('Hfd\Woocommerce\Template');
            echo $template->fetchView('admin/notice.php', array('syncData' => $data));
        }
    }

    /**
     * Sync order into HFD system
     */
    public function syncOrderToHfd()
    {
        $orderId = isset( $_GET['order_id'] ) ? sanitize_text_field( $_GET['order_id'] ) : null;
        $redirectTo = admin_url('edit.php?post_type=shop_order');

        if( !$orderId ){
            wp_redirect( $redirectTo );
            exit;
        }

        /* @var \Hfd\Woocommerce\Helper\Hfd $hfdHelper */
        $hfdHelper = Container::create('Hfd\Woocommerce\Helper\Hfd');
        $result = $hfdHelper->sendOrders(array($orderId));
        $filesystem = Container::get('Hfd\Woocommerce\Filesystem');
        $filesystem->writeSession(serialize($result), 'sync_to_hfd');

        $redirectTo = add_query_arg('sync_to_hfd', 1, $redirectTo);
        wp_redirect($redirectTo);
        exit;
    }
}