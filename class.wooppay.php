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
 * @copyright   Copyright (c) 2012-2015 Wooppay
 * @author      Vlad Shishkin <vshishkin@wooppay.com>
 * @version     3.0.0
 */

class WC_Gateway_Wooppay_Wallet extends WC_Payment_Gateway
{

	public $debug = 'yes';
	public $api;

	public function __construct()
	{
		$this->id = 'wooppay_wallet';
		$this->icon = apply_filters('woocommerce_wooppay_icon', plugin_dir_url(__FILE__) . '/assets/images/wooppay.png');
		$this->method_title = __('WOOPPAY', 'Wooppay');
		$this->init_form_fields();
		$this->init_settings();
		$this->title = $this->settings['title'];
		$this->description = $this->settings['description'];
		$this->instructions = $this->get_option('instructions');
		$this->enable_for_methods = $this->get_option('enable_for_methods', array());

		add_action('woocommerce_update_options_payment_gateways_' . $this->id, array(&$this, 'process_admin_options'));
		add_action('woocommerce_api_wc_gateway_wooppay_wallet', array($this, 'check_response'));
	}

	public function check_response()
	{
		if (isset($_REQUEST['id_order']) && isset($_REQUEST['key'])) {
			$order = wc_get_order((int)$_REQUEST['id_order']);
			if ($order && $order->key_is_valid($_REQUEST['key'])) {
				try {
					include_once('WooppayClient.php');
					$this->api = new WooppayClient($this->get_option('api_url'), $this->get_option('api_username'),
						$this->get_option('api_password'));
					global $wpdb;
					$operation = $wpdb->get_results("SELECT operation_id FROM {$wpdb->prefix}wooppay WHERE order_id = " . $_REQUEST['id_order']);
					$operation_data = $this->api->getOperationData($operation[0]->operation_id);
					if ($operation_data[0]->status == 14) {
						$order->update_status('completed', __('Payment completed.', 'woocommerce'));
						die('{"data":1}');
					}
				} catch (Exception $e) {
					$this->add_log($e->getMessage());
					wc_add_notice(__('Wooppay error:', 'woocommerce') . $e->getMessage() . print_r($order, true),
						'error');
				}
			} else {
				$this->add_log('Error order key: ' . print_r($_REQUEST, true));
			}
		} else {
			$this->add_log('Error call back: ' . print_r($_REQUEST, true));
		}
		die('{"data":1}');
	}

	/* Admin Panel Options.*/
	public function admin_options()
	{
		?>
        <h3><?php _e('Wooppay', 'wooppay_wallet'); ?></h3>
        <table class="form-table">
			<?php $this->generate_settings_html(); ?>
        </table> <?php
	}

	/* Initialise Gateway Settings Form Fields. */
	public function init_form_fields()
	{
		global $woocommerce;

		$shipping_methods = array();

		if (is_admin()) {
			foreach ($woocommerce->shipping->load_shipping_methods() as $method) {
				$shipping_methods[$method->id] = $method->get_title();
			}
		}

		$this->form_fields = array(
			'enabled' => array(
				'title' => __('Enable/Disable', 'wooppay_wallet'),
				'type' => 'checkbox',
				'label' => __('Enable Wooppay Gateway', 'wooppay_wallet'),
				'default' => 'no'
			),
			'title' => array(
				'title' => __('Title', 'wooppay_wallet'),
				'type' => 'text',
				'description' => __('This controls the title which the user sees during checkout.', 'wooppay_wallet'),
				'desc_tip' => true,
				'default' => __('Оплатить через Woopkassa', 'wooppay_wallet')
			),
			'description' => array(
				'title' => __('Description', 'wooppay_wallet'),
				'type' => 'textarea',
				'description' => __('This controls the description which the user sees during checkout.',
					'wooppay_wallet'),
				'default' => __('Оплата с помощью кредитной карты или кошелька Wooppay', 'wooppay_wallet')
			),
			'instructions' => array(
				'title' => __('Instructions', 'wooppay_wallet'),
				'type' => 'textarea',
				'description' => __('Instructions that will be added to the thank you page.', 'wooppay_wallet'),
				'default' => __('Введите все необходимые данные и вас перенаправит на портал Wooppay для оплаты',
					'wooppay_wallet')
			),
			'api_details' => array(
				'title' => __('API Credentials', 'wooppay_wallet'),
				'type' => 'title',
			),
			'redirect' => array(
				'title' => __('Место оплаты', 'wooppay_wallet'),
				'type' => 'select',
				'options' => [
					'true' => __('Без перехода, оставаясь на вашем сайте', 'woocommerce'),
					'false' => __('С переходом на страницу оплаты woopkassa', 'woocommerce')
				]
			),
			'linkCard' => array(
				'title' => __('Привязывать карты покупателей', 'wooppay_wallet'),
				'type' => 'checkbox',
				'default' => 'yes',
				'desc_tip' => true,
				'description' => __('При повторной оплате, покупателю достаточно ввести CVV/CVC и код подтверждения при его наличии. Для привязки обязательно передать номер телефона при создании инвойса',
					'wooppay_wallet'),
			),
			'api_url' => array(
				'title' => __('API URL', 'wooppay_wallet'),
				'type' => 'text',
				'description' => __('Get your API credentials from Wooppay.', 'wooppay_wallet'),
				'default' => '',
				'desc_tip' => true,
				'placeholder' => __('Optional', 'wooppay_wallet')
			),
			'api_username' => array(
				'title' => __('API Username', 'wooppay_wallet'),
				'type' => 'text',
				'description' => __('Get your API credentials from Wooppay.', 'wooppay_wallet'),
				'default' => '',
				'desc_tip' => true,
				'placeholder' => __('Optional', 'wooppay_wallet')
			),
			'api_password' => array(
				'title' => __('API Password', 'wooppay_wallet'),
				'type' => 'text',
				'description' => __('Get your API credentials from Wooppay.', 'wooppay_wallet'),
				'default' => '',
				'desc_tip' => true,
				'placeholder' => __('Optional', 'wooppay_wallet')
			),
			'order_prefix' => array(
				'title' => __('Order prefix', 'wooppay_wallet'),
				'type' => 'text',
				'description' => __('Order prefix', 'wooppay_wallet'),
				'default' => '',
				'desc_tip' => true,
				'placeholder' => __('Optional', 'wooppay_wallet')
			),
			'service_name' => array(
				'title' => __('Service name', 'wooppay_wallet'),
				'type' => 'text',
				'description' => __('Service name', 'wooppay_wallet'),
				'default' => '',
				'desc_tip' => true,
				'placeholder' => __('Optional', 'wooppay_wallet')
			),
			'terms' => array(
				'title' => __('Terms', 'wooppay_wallet'),
				'type' => 'text',
				'description' => __('Link for terms.', 'wooppay_wallet'),
				'default' => '',
				'desc_tip' => true,
				'placeholder' => __('Optional', 'wooppay_wallet')
			)
		);

	}

	public function payment_fields()
	{
		?>
		<?php if ($this->get_option('terms')): ?>
        <fieldset>
			<?php parent::payment_fields() ?>
            <p>
                <label style="display: flex;align-items: baseline">
                    <input id="isAgree" type="checkbox" name="isAgree" style="margin-right: 10px"/> <span>Я прочитал(а) и ознакомлен(а) с <a target="_blank"
                                href="<?php echo $this->get_option('terms')?>">офертой</a> и принимаю условия.</span>
                </label>
            </p>
            <div class="clear"></div>
        </fieldset>
	<?php else: ?>
		<?php parent::payment_fields() ?>
	<?php endif; ?>
		<?php
	}

	public function validate_fields()
	{
		if ($this->get_option('terms') && $_POST['isAgree'] !== "on"){
			wc_add_notice(__('Для старта процесса оплаты необходимо согласие с правилами и условиями сайта.', 'woocommerce'), 'error');
		}
		return parent::validate_fields(); // TODO: Change the autogenerated stub
	}

	function addNewRecord($order_id)
	{
		global $wpdb;
		$wpdb->insert("{$wpdb->prefix}wooppay",
			array("order_id" => $order_id, "operation_id" => $_SESSION['wooppay']['invoice_operation_id']),
			array("%s", "%s"));
	}


	function process_payment($order_id)
	{
		global $woocommerce;
		$order = new WC_Order($order_id);
		global $wpdb;
		if ($wpdb->get_results("SELECT * FROM {$wpdb->prefix}wooppay WHERE order_id = " . $order_id)) {
			$reference_id = $this->get_option('order_prefix') . '_' . $order_id . '_' . time();
		} else {
			$reference_id = $this->get_option('order_prefix') . '_' . $order_id;
		}
		try {
			if (empty($order->get_billing_phone()) && $this->get_option('redirect') == 'false') {
				wc_add_notice(__('Wooppay error: ',
						'woocommerce') . 'Для продолжения оплаты необходимо заполнить номер телефона', 'error');
			}
			include_once('WooppayClient.php');
			$this->api = new WooppayClient($this->get_option('api_url'), $this->get_option('api_username'),
				$this->get_option('api_password'));
			$this->api->reference_id = $reference_id;
			$this->api->amount = $order->get_total();
			$this->api->back_url = $this->get_return_url($order);
			$this->api->request_url = WC()->api_request_url('WC_Gateway_Wooppay_Wallet') . '?id_order=' . $order_id . '&key=' . $order->get_order_key();
			$this->api->linkCard = $this->get_option('linkCard') == 'yes' ? 1 : 0;
			$this->api->user_phone = $order->get_billing_phone();
			$this->api->service_name = $this->get_option('service_name');
			$invoice = $this->api->createInvoice();
			$_SESSION['wooppay']['invoice_id'] = $invoice->response->invoice_id;
			$_SESSION['wooppay']['invoice_key'] = $invoice->response->key;
			$_SESSION['wooppay']['partner_name'] = $this->api->transport->partner_name;
			$_SESSION['wooppay']['link_card'] = $this->api->invoice->linkCard;
			$_SESSION['wooppay']['user_phone'] = $this->api->user_phone;
			$_SESSION['wooppay']['api_url'] = $this->api->transport->url;
			$_SESSION['wooppay']['authorization'] = $this->api->transport->authorization;
			$_SESSION['wooppay']['finish_url'] = $this->api->back_url;
			$_SESSION['wooppay']['order_id'] = $order_id;
			$_SESSION['wooppay']['invoice_operation_id'] = $invoice->response->operation_id;
			if ($this->get_option('redirect') == 'false') {
				wc_add_notice(__('Wooppay: ', 'woocommerce') . 'Оплата продолжается', 'success');
				$this->addNewRecord($order_id);
				return [
					'result' => 'false',
				];
			} else {
				$woocommerce->cart->empty_cart();
				$order->update_status('pending', __('Payment Pending.', 'woocommerce'));
				$order->payment_complete($invoice->response->operation_id);
				$this->addNewRecord($order_id);
				unset($_SESSION['wooppay']);
				return [
					'result' => 'success',
					'redirect' => $invoice->operation_url
				];
			}

		} catch (Exception $e) {
			$this->add_log($e->getMessage());
			wc_add_notice(__('Wooppay error: ', 'woocommerce') . 'Не удалось совершить платеж', 'error');
		}

	}

	function thankyou()
	{
		echo $this->instructions != '' ? wpautop($this->instructions) : '';
	}

	function add_log($message)
	{
		if ($this->debug == 'yes') {
			if (empty($this->log)) {
				$this->log = new WC_Logger();
			}
			$this->log->add('Wooppay', $message);
		}
	}
}
