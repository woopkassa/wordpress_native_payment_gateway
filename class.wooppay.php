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

	public function __construct()
	{
		$this->id = 'wooppay_wallet';
		$this->icon = apply_filters('woocommerce_wooppay_icon',
			plugin_dir_url(__FILE__) . '/assets/images/wooppay.png');
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
					include_once('ApiDTO.php');
					$client = new WooppayClient($this->get_option('test_mode') === 'yes');
					global $wpdb;
					$apiDTO = (new ApiDTO($this->get_option('api_username'), $this->get_option('api_password')));
					$auth = $client->auth($apiDTO);
					$operation = $wpdb->get_results("SELECT operation_id FROM {$wpdb->prefix}wooppay WHERE order_id = " . $_REQUEST['id_order']);
					$operation = end($operation);
					$operationData = $client->getOperationData($operation->operation_id, $auth);
					if ($operationData[0]->status === 14 || $operationData[0]->status === 19) {
						$order->update_status('completed', __('Payment completed.', 'woocommerce'));
						die('{"data":1}');
					}
				} catch (Exception $e) {
					$this->returnError($e->getMessage());
					wc_add_notice(__('Wooppay error:', 'woocommerce') . $e->getMessage() . print_r($order, true),
						'error');
				}
			} else {
				$this->returnError('Error order key: ' . print_r($_REQUEST, true));
			}
		} else {
			$this->returnError('Error call back: ' . print_r($_REQUEST, true));
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
					'false' => __('Без перехода, оставаясь на вашем сайте', 'woocommerce'),
					'true' => __('С переходом на страницу оплаты woopkassa', 'woocommerce')
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
			'test_mode' => array(
				'title' => __('Тестовый режим', 'wooppay_wallet'),
				'type' => 'checkbox',
				'label' => __('Включить тестовый режим', 'wooppay_wallet'),
				'default' => 'yes'
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
                    <input id="isAgree" type="checkbox" name="isAgree" style="margin-right: 10px"/> <span>Я прочитал(а) и ознакомлен(а) с <a
                                target="_blank"
                                href="<?php echo $this->get_option('terms') ?>">офертой</a> и принимаю условия.</span>
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
		if ($this->get_option('terms') && $_POST['isAgree'] !== "on") {
			wc_add_notice(__('Для старта процесса оплаты необходимо согласие с правилами и условиями сайта.',
				'woocommerce'), 'error');
		}
		return parent::validate_fields(); // TODO: Change the autogenerated stub
	}

	function process_payment($orderId)
	{
		try {
			if (!isset($_POST['step'])) {
				include_once('WooppayClient.php');
				include_once('ApiDTO.php');
				include_once('InvoiceDTO.php');
				global $woocommerce;
				$order = new WC_Order($orderId);
				$apiDTO = (new ApiDTO($this->get_option('api_username'), $this->get_option('api_password')));
				$invoiceDTO = new InvoiceDTO($this->get_option('order_prefix') . '_' . $orderId . '_' . time(),
					$order->get_total(),
					WC()->api_request_url('WC_Gateway_Wooppay_Wallet') . '?id_order=' . $orderId . '&key=' . $order->get_order_key(),
					$this->get_return_url($order), $this->get_option('linkCard') === 'yes',
					$order->get_billing_phone(),
					$this->get_option('service_name'));
				$client = new WooppayClient($this->get_option('test_mode') === 'yes');
				$auth = $client->auth($apiDTO);
				$invoice = $client->createInvoice($invoiceDTO, $auth);
				$this->addNewRecord($orderId, $invoice->operationId);
				if ($this->get_option('redirect') === 'false') {
					include_once('InvoiceContinueDTO.php');
					$invoiceContinueDto = new InvoiceContinueDTO($invoice->invoiceId, $invoice->invoiceKey,
						$invoice->partnerName,
						$invoiceDTO->linkCard, $invoiceDTO->userPhone, $this->get_option('test_mode') === 'yes', $auth->token, urlencode($invoiceDTO->backUrl),
						$orderId,
						$invoice->operationId);
					wp_send_json(['step' => 'pseudoAuth', 'invoiceDTO' => $invoiceContinueDto]);
				} else {
					$woocommerce->cart->empty_cart();
					$order->update_status('pending', __('Payment Pending.', 'woocommerce'));
					$order->payment_complete($invoice->operationId);
					return [
						'result' => 'success',
						'redirect' => $invoice->operationUrl
					];
				}
			} else {
				include_once('WooppayClient.php');
				$client = new WooppayClient($this->get_option('test_mode') === 'yes');
				switch ($_POST['step']) {
					case 'pseudoAuth':
					    if (json_decode($_POST['invoiceDTO']) === null){
						    $invoiceDTO = json_decode(stripslashes($_POST['invoiceDTO']));
                        } else {
						    $invoiceDTO = json_decode($_POST['invoiceDTO']);
                        }
						include_once('InvoiceContinueDTO.php');
						$invoiceContinueDto = new InvoiceContinueDTO($invoiceDTO->invoiceId, $invoiceDTO->invoiceKey,
							$invoiceDTO->partnerName, $invoiceDTO->linkCard, $invoiceDTO->userPhone,
							$invoiceDTO->testMode, $invoiceDTO->token, urlencode($invoiceDTO->finishUrl), $invoiceDTO->orderId,
							$invoiceDTO->operationId);
						$pseudoAuth = $client->pseudoAuth($invoiceContinueDto);
						if ($pseudoAuth->walletType === 'G') {
							$cards = $client->getCards($pseudoAuth);
							if ($cards !== null) {
								$_GET['cards'] = $cards;
								ob_start();
								require __DIR__ . '/linkedList.php';
								$cards = ob_get_clean();
								wp_send_json([
									'step' => 'cards',
									'cards' => $cards,
									'pseudoAuth' => $pseudoAuth,
									'invoiceDTO' => $invoiceContinueDto
								]);
							} else {
								$cardFrame = $client->payFromCard($invoiceContinueDto, $pseudoAuth,
									isset($_POST['card_id']) ? $_POST['card_id'] : null);
								wp_send_json([
									'step' => 'cardFrame',
									'cardFrame' => $cardFrame,
									'pseudoAuth' => $pseudoAuth,
									'invoiceDTO' => $invoiceContinueDto
								]);
							}
						}
						wp_send_json([
							'step' => 'payFromCard',
							'pseudoAuth' => $pseudoAuth,
							'invoiceDTO' => $invoiceContinueDto
						]);
						break;
					case 'payFromCard':
						if (json_decode($_POST['invoiceDTO']) === null){
							$invoiceDTO = json_decode(stripslashes($_POST['invoiceDTO']));
						} else {
							$invoiceDTO = json_decode($_POST['invoiceDTO']);
						}
						include_once('InvoiceContinueDTO.php');
						$invoiceContinueDto = new InvoiceContinueDTO($invoiceDTO->invoiceId, $invoiceDTO->invoiceKey,
							$invoiceDTO->partnerName, $invoiceDTO->linkCard, $invoiceDTO->userPhone,
							$invoiceDTO->testMode, $invoiceDTO->token, urlencode($invoiceDTO->finishUrl), $invoiceDTO->orderId,
							$invoiceDTO->operationId);
						if (json_decode($_POST['pseudoAuth']) === null){
							$pseudoAuth = json_decode(stripslashes($_POST['pseudoAuth']));
						} else {
							$pseudoAuth = json_decode($_POST['pseudoAuth']);
						}
						include_once('PseudoAuth.php');
						$pseudoAuth = new PseudoAuth($pseudoAuth->token, $pseudoAuth->walletType);
						$cardFrame = $client->payFromCard($invoiceContinueDto, $pseudoAuth,
							isset($_POST['card_id']) ? $_POST['card_id'] : null);
						wp_send_json([
							'step' => 'cardFrame',
							'cardFrame' => $cardFrame,
							'pseudoAuth' => $pseudoAuth,
							'invoiceDTO' => $invoiceContinueDto
						]);
					case 'failPayment':
						ob_start();
						require __DIR__ . '/failPayment.php';
						$failView = ob_get_clean();
						wp_send_json([
							'view' => $failView,
						]);
					case 'successPayment':
						ob_start();
						require __DIR__ . '/successPayment.php';
						$successView = ob_get_clean();
						wp_send_json([
							'view' => $successView,
						]);
					case 'finish':
						if (json_decode($_POST['pseudoAuth']) === null){
							$pseudoAuth = json_decode(stripslashes($_POST['pseudoAuth']));
						} else {
							$pseudoAuth = json_decode($_POST['pseudoAuth']);
						}
						include_once('PseudoAuth.php');
						$pseudoAuth = new PseudoAuth($pseudoAuth->token, $pseudoAuth->walletType);
						$receipt = $client->getReceipt($_POST['operationId'], $pseudoAuth);
						global $woocommerce;
						$order = new WC_Order($orderId);
						$woocommerce->cart->empty_cart();
						$order->update_status('pending', __('Payment Pending.', 'woocommerce'));
						$order->payment_complete($_POST['operationId']);
						if (!empty($receipt)) {
							wp_send_json([
								'receipt' => $receipt,
								'returnUrl' => $this->get_return_url($order)
							]);
						} else {
							wp_send_json([
								'returnUrl' => $this->get_return_url($order)
							]);
						}
				}
			}
		} catch (Exception $e) {
			$this->returnError($e->getMessage());
			wc_add_notice(__('Wooppay error: ', 'woocommerce') . 'Не удалось совершить платеж', 'error');
		}

	}

	function returnError($message)
	{
		if ($this->debug === 'yes') {
			if (empty($this->log)) {
				$this->log = new WC_Logger();
			}
			$this->log->add('Wooppay', $message);
		}
		ob_start();
		require __DIR__ . '/fail.php';
		$failView = ob_get_clean();
		wp_send_json(['errorMessage' => $message, 'view' => $failView]);
	}

	function addNewRecord($orderId, $operationId)
	{
		global $wpdb;
		$wpdb->insert("{$wpdb->prefix}wooppay",
			array("order_id" => $orderId, "operation_id" => $operationId),
			array("%s", "%s"));
	}

}
