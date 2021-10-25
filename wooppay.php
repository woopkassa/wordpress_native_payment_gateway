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
        <div id="file_url" style="display: none"><?php echo plugins_url('noRedirectScript.php', __FILE__) ?></div>
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
	$no_redirect_url = plugins_url('noRedirectScript.php', __FILE__);
	?>
    <script>


        function getQueryVariable(variable, query) {
            var vars = query.split('&');
            for (var i = 0; i < vars.length; i++) {
                var pair = vars[i].split('=');
                if (decodeURIComponent(pair[0]) == variable) {
                    return decodeURIComponent(pair[1]);
                }
            }
        }

        (function ($) {
            jQuery(document).bind('ajaxStart', function (e, request, options) {
                $('#pleaseWaitDialog').modal({
                    backdrop: 'static',
                    keyboard: false
                });
                jQuery('#pleaseWaitDialog').css("display", 'flex');
            });
            jQuery(document).bind('ajaxComplete', function (e, request, options) {
                jQuery('#pleaseWaitDialog').css("display", 'none');
                jQuery('#pleaseWaitDialog').modal('hide');
            });

            function sendRequest() {
                var url = "<?php echo $no_redirect_url ?>"
                $.ajax({
                    url: url,
                    method: 'get',
                    dataType: 'html',
                    data: '',
                    success: function (data) {
                        $('#wooppay_frame_modal .modal-body').html(data);
                        $('#wooppay_frame_modal').modal({
                            backdrop: 'static',
                            keyboard: false
                        });
                    },
                });
            }

            function abortWoocommerceAjax() {
                $('#place_order').attr('type', 'button');
                $('#place_order').attr('id', 'wooppay_checkout');
                $('#wooppay_checkout').on('click', function () {
                    sendRequest();
                })
            }

            jQuery(document).ajaxSuccess(function (e, request, options) {
                if (options.url == '/?wc-ajax=checkout' && getQueryVariable('payment_method', options.data) == 'wooppay_wallet') {
                    if (request.responseJSON.messages.length > 0) {
                        var message = $(request.responseJSON.messages).html();
                        if (message.replace(/\s/g, '') == 'Wooppay:Оплатапродолжается') {
                            abortWoocommerceAjax();
                            sendRequest();
                        }
                    }
                }
            });

            function base64ToBlob(base64, mimetype) {
                if (!window.atob || !window.Uint8Array) {
                    console.log('The current browser doesnot have the atob function. Cannot continue');
                    return null;
                }
                slicesize = 512;
                var bytechars = atob(base64);
                var bytearrays = [];
                for (var offset = 0; offset < bytechars.length; offset += slicesize) {
                    var slice = bytechars.slice(offset, offset + slicesize);
                    var bytenums = new Array(slice.length);
                    for (var i = 0; i < slice.length; i++) {
                        bytenums[i] = slice.charCodeAt(i);
                    }
                    var bytearray = new Uint8Array(bytenums);
                    bytearrays[bytearrays.length] = bytearray;
                }
                return new Blob(bytearrays, {type: mimetype});
            }

            var functionName = "rechargeReceiver";

            if (typeof functionName != "function") {
                functionName = function (event) {
                    if (event.data) {
                        var message = JSON.parse(event.data);
                        if (message.status !== 4) {
                            var err_info = "";
                            if (message.data && typeof message.data.errorCode != "undefined") {
                                var errors_text = getAcquiringErrors();
                                var err_key = "e_" + message.data.errorCode;
                                if (err_key in errors_text) {
                                    err_info = errors_text[err_key];
                                }
                            }
                            if (message.status == 3) {
                                if (err_info == '') {
                                    err_info = 'Произошла ошибка. Скорее всего вы ввели некорректные данные карты';
                                }
                            } else if (message.status == 2) {
                                if (err_info == '') {
                                    err_info = 'Произошла ошибка. Возможно вы ввели некорректные данные карты';
                                }
                            }
                            var url = "<?php echo $no_redirect_url ?>"
                            $.ajax({
                                url: url,
                                type: "POST",
                                data: 'woop_frame_status=' + message.status + '&woop_frame_error=' + err_info + '&form=213',
                                beforeSend: function () {
                                    if ($('#wooppay_frame_modal iframe').length > 0) {
                                        $('#wooppay_frame_modal').css('display', 'none')
                                    }
                                },
                                success: function (result) {
                                    if (!$.parseJSON(result)[1]) {
                                        console.log('зашел')
                                        $('#wooppay_frame_modal').css('display', 'flex')
                                        $('#wooppay_frame_modal .modal-body').html($.parseJSON(result)[0]);
                                        $("#wooppay_frame_modal").on("hide.bs.modal", function () {
                                            window.location = 'checkout';
                                        });
                                    }
                                    if ($.parseJSON(result)[1]) {
                                        if ($.parseJSON(result)[1] !== 'none') {
                                            var mime = 'application/pdf';
                                            var a = document.createElement('a');
                                            var urlCreator = window.URL || window.webkitURL || window.mozURL || window.msURL;
                                            if (urlCreator && window.Blob && ('download' in a) && window.atob) {
                                                var blob = base64ToBlob($.parseJSON(result)[1], mime);
                                                var url = window.URL.createObjectURL(blob);
                                                a.setAttribute('href', url);
                                                a.setAttribute("download", 'receipt.pdf');
                                                var event = document.createEvent('MouseEvents');
                                                event.initMouseEvent('click', true, true, window, 1, 0, 0, 0, 0, false, false, false, false, 0, null);
                                                a.dispatchEvent(event);
                                            }
                                        }
                                        window.location = $.parseJSON(result)[2];
                                    }
                                },
                                complete: function () {
                                },
                                error: function (error) {
                                    console.log(error);
                                }
                            });
                        }
                    }
                };
                window.addEventListener("message", functionName, false);
            }

            function getAcquiringErrors() {
                return {
                    'e_04': 'Карта заблокирована. Для снятия ограничений, позвоните в Колл-центр вашего банка.',
                    'e_05': 'Транзакция отклонена. Позвоните в Колл-центр вашего банка.',
                    'e_07': 'Карта заблокирована. Для снятия ограничений, позвоните в Колл-центр вашего банка.',
                    'e_12': 'Недействительная транзакция, перепроверьте введенные данные. В случае повторения ошибки попробуйте позже...',
                    'e_14': 'Недействительный номер карты.',
                    'e_19': 'Ошибка авторизации.',
                    'e_30': 'Переданы неверные данные для оплаты пополнения. Обратитесь в службу поддержки.',
                    'e_36': 'Карта заблокирована. Для снятия ограничений, позвоните в Колл-центр вашего банка.',
                    'e_37': 'По карте выставлены ограничения. Для снятия ограничений, позвоните в Колл-центр вашего банка.',
                    'e_41': 'Карта, числится в базе утерянных. Позвоните в Колл-центр вашего банка.',
                    'e_45': 'Карта, числится в базе украденых. Позвоните в Колл-центр вашего банка, либо обратиться в ближайшее отделение полиции.',
                    'e_51': 'Недостаточно средств на карте.',
                    'e_54': 'Истёк срок действия карты.',
                    'e_57': 'Карта закрыта для интернет-транзакций. Обратитесь в ваш банк.',
                    'e_58': 'Операции с картами временно приостановлены. Попробуйте позже.',
                    'e_61': 'Сумма превышает допустимый суточный лимит. Можете обратиться в службу поддержки, либо завершить операцию завтра.',
                    'e_62': 'Карта заблокирована банком. Позвоните в Колл-центр вашего банка.',
                    'e_91': 'Ваш банк временно не доступен. Попробуйте оплатить позже.',
                    'e_96': 'Не установлен 3DSecure(SecureCode) либо сбой связи. Позвоните в Колл-центр вашего банка.',
                };
            }

        })(jQuery);
    </script>
	<?php
}
?>
