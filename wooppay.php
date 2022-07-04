<?php
/**
 * The MIT License (MIT)
 *
 * Copyright (c) 2012-2021 Wooppay
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * @copyright   Copyright (c) 2012-2021 Wooppay
 * @author      Vlad Shishkin <vshishkin@wooppay.com>
 * @version     3.0.0
 * @wordpress-plugin
 * Plugin Name:       WooCommerce Wooppay Wallet Payment Gateways
 * Plugin URI:
 * Description:       Add Wooppay Wallet Payment Gateways for WooCommerce.
 * Version:           3.0.0
 * Author:            Vlad Shishkin <vshishkin@wooppay.com>
 * License:           The MIT License (MIT)
 *
 */

session_start();
function woocommerce_cpg_fallback_notice_wallet()
{
	echo '<div class="error"><p>' . sprintf(__('WooCommerce Wooppay Gateways depends on the last version of %s to work!',
			'wooppay_wallet'),
			'<a href="http://wordpress.org/extend/plugins/woocommerce/">WooCommerce</a>') . '</p></div>';
}

function custom_payment_gateway_load_wallet()
{
	if (!class_exists('WC_Payment_Gateway')) {
		add_action('admin_notices', 'woocommerce_cpg_fallback_notice_wallet');
		return;
	}

	function wc_Custom_add_gateway_wallet($methods)
	{
		$methods[] = 'WC_Gateway_Wooppay_Wallet';
		return $methods;
	}

	add_filter('woocommerce_payment_gateways', 'wc_Custom_add_gateway_wallet');


	require_once plugin_dir_path(__FILE__) . 'class.wooppay.php';
}

add_action('plugins_loaded', 'custom_payment_gateway_load_wallet', 0);

function wcCpg_action_links_wallet($links)
{
	$settings = array(
		'settings' => sprintf(
			'<a href="%s">%s</a>',
			admin_url('admin.php?page=wc-settings&tab=checkout&section=wc_gateway_wooppay_wallet'),
			__('Payment Gateways', 'wooppay_wallet')
		)
	);

	return array_merge($settings, $links);
}

add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'wcCpg_action_links_wallet');


function init_assets()
{
	wp_register_script('prefix_bootstrap_js', plugins_url('/assets/bootstrap/js/bootstrap.min.js', __FILE__),
		array('jquery'));
	wp_enqueue_script('prefix_bootstrap_js');

	wp_register_style('prefix_bootstrap_style', plugins_url('/assets/bootstrap/css/bootstrap.min.css', __FILE__));
	wp_enqueue_style('prefix_bootstrap_style');

	wp_register_style('prefix_modal_style', plugins_url('/assets/css/modal.css', __FILE__));
	wp_enqueue_style('prefix_modal_style');

	wp_register_style('prefix_style', plugins_url('/assets/css/style.css', __FILE__));
	wp_enqueue_style('prefix_style');
}


add_action('woocommerce_before_checkout_form', 'init_assets');


add_action('woocommerce_before_checkout_form', 'add_bootstrap_modal');


add_action('woocommerce_before_checkout_form', 'add_frame_modal');


add_action('woocommerce_before_checkout_form', 'register_my_session');

add_action('woocommerce_before_checkout_form', 'modal_action_javascript', 99);

function add_bootstrap_modal()
{
	?>
    <div class="modal fade" id="pleaseWaitDialog" tabindex="-1" role="dialog" aria-labelledby="myModalLabel"
         aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-body">
                    <img class="modal__preloader"
                         src="<?php echo plugins_url('/assets/images/preloader.gif', __FILE__) ?>"
                         alt="Preloader" width="146" height="146">
                </div>
            </div>
        </div>
    </div>
	<?php
}

function add_frame_modal()
{
	?>
    <div id="wooppay_frame_modal" class="modal fade">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                </div>
            </div>
        </div>
    </div>
	<?php
}

function init_table()
{
	global $wpdb;
	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	$table_name = $wpdb->prefix . 'wooppay';
	$query = $wpdb->prepare('SHOW TABLES LIKE %s', $wpdb->esc_like($table_name));
	if (!$wpdb->get_var($query) == $table_name) {
		$charset_collate = "DEFAULT CHARACTER SET {$wpdb->charset} COLLATE {$wpdb->collate}";
		$sql = "CREATE TABLE {$table_name} (
	id  bigint(20) unsigned NOT NULL auto_increment,
	order_id varchar(255) NOT NULL default '',
	operation_id varchar(255) NOT NULL default '',
	PRIMARY KEY  (id)
	)
	{$charset_collate};";
		dbDelta($sql);
	}
}

register_activation_hook(__FILE__, 'init_table');

function register_my_session()
{
	if (!session_id()) {
		session_start();
	}
}

function modal_action_javascript()
{
	wp_register_script( 'script', plugins_url( '/script.js', __FILE__ ) );
	wp_enqueue_script( 'script' );
}

?>
