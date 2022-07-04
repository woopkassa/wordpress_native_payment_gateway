<?php

require_once('ApiDTO.php');

class WooppayClient
{

	public $hostUrl;
	public $invoice;
	public $receiptCounter = 0;

	const KZ_COUNTRY_CODE = 1;
	const UZ_COUNTRY_CODE = 860;
	const TJ_COUNTRY_CODE = 762;
	const KZ_COUNTRY_CODE_ALTERNATIVE = 398;

	const TEST_CORE_URL = 'https://api.yii2-stage.test.wooppay.com/v1';
	const CORE_URL = 'https://api-core.wooppay.com/v1';

	public function __construct(bool $testMode)
	{
		$testMode == true ? $this->hostUrl = self::TEST_CORE_URL : $this->hostUrl = self::CORE_URL;
	}


	public function auth(ApiDTO $apiDTO)
	{
		try {
			$array = array(
				'login' => $apiDTO->apiUsername,
				'password' => $apiDTO->apiPassword
			);
			$ch = curl_init($this->hostUrl . '/auth');
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($array, '', '&'));
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_HEADER, false);
			$auth = curl_exec($ch);
			if (json_decode($auth)) {
				$auth = json_decode($auth);
				if (isset($auth->message)) {
					throw new Exception($auth->message);
				}
				if (!isset($auth->token)) {
					throw new Exception('Не удалось совершить вход в API, скорее всего неверный логин или пароль');
				}
			} else {
				throw new Exception('Не удалось совершить вход в API, скорее всего неверный логин или пароль');
			}
			curl_close($ch);
			require_once('Auth.php');
			return new Auth($auth->token, $auth->country, $auth->login);
		} catch (Exception $e) {
			if (!empty($e->getMessage())) {
				throw new Exception("Ошибка при авторизации: " . $e->getMessage());
			} else {
				throw new Exception('Не удалось совершить вход в API, скорее всего неверный логин или пароль');
			}
		}
	}

	private function getInvoiceByCountry(int $country)
	{
		switch ($country) {
			case self::KZ_COUNTRY_CODE || self::KZ_COUNTRY_CODE_ALTERNATIVE:
				require_once(__DIR__ . '/invoice/countries/KzInvoice.php');
				return new KzInvoice();
			case self::UZ_COUNTRY_CODE:
				require_once(__DIR__ . '/invoice/countries/UzInvoice.php');
				return new UzInvoice();
			case self::TJ_COUNTRY_CODE:
				require_once(__DIR__ . '/invoice/countries/TjInvoice.php');
				return new TjInvoice();
		}
		throw new Exception("Invoice for $country not found!");
	}

	public function createInvoice(InvoiceDTO $invoiceDTO, Auth $auth)
	{
		try {
			$this->invoice = $this->getInvoiceByCountry($auth->country);
			$array = array(
				'reference_id' => $invoiceDTO->referenceId,
				'amount' => $invoiceDTO->amount,
				'merchant_name' => $auth->login,
				'back_url' => $invoiceDTO->backUrl,
				'request_url' => ['url' => urlencode($invoiceDTO->requestUrl), 'type' => 'POST'],
				'user_phone' => $invoiceDTO->userPhone,
				'option' => $this->invoice->getOption($invoiceDTO->linkCard),
				'service_name' => $invoiceDTO->serviceName,
			);
			$ch = curl_init($this->hostUrl . '/invoice/create');
			$headers = array('Content-type: application/json', 'language: ru', 'Time-Zone: Asia/Almaty');
			$headers = array_merge($headers, array("Authorization: $auth->token"));
			$headers = array_merge($headers, array("partner-name: " . $this->invoice->getPartnerName()));
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($array));
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			$invoice = curl_exec($ch);
			if (json_decode($invoice)) {
				$invoice = json_decode($invoice);
				if (isset($invoice->message)) {
					throw new Exception('Ошибка при создании инвойса: ' . $invoice->message);
				}
				if (!isset($invoice->operation_url)) {
					throw new Exception('Не удалось создать инвойс!');
				}
			} else {
				throw new Exception('Не удалось создать инвойс!');
			}
			curl_close($ch);
			require_once('Invoice.php');
			return new Invoice($invoice->operation_url, $invoice->response->operation_id,
				$invoice->response->invoice_id, $invoice->response->key, $this->invoice->getPartnerName());
		} catch (Exception $e) {
			if (!empty($e->getMessage())) {
				throw new Exception($e->getMessage());
			} else {
				throw new Exception('Не удалось создать инвойс!');
			}
		}
	}

	public function pseudoAuth(InvoiceContinueDTO $invoiceContinueDTO)
	{
		try {
			$array = [
				'login' => $invoiceContinueDTO->userPhone
			];
			$ch = curl_init($this->hostUrl . '/auth/pseudo');
			$headers = array('Content-type: application/json', 'language: ru', 'Time-Zone: Asia/Almaty');
			if ($invoiceContinueDTO->linkCard === true) {
				$headers = array_merge($headers, array("Authorization: $invoiceContinueDTO->token"));
				$array = array_merge($array, ['subject_type' => 5019]);
			}
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($array));
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			$pseudoAuth = curl_exec($ch);
			if (json_decode($pseudoAuth)) {
				$pseudoAuth = json_decode($pseudoAuth);
				if (isset($pseudoAuth->message)) {
					throw new Exception('Ошибка при попытке псевдоавторизации: ' . $pseudoAuth->message);
				}
				if (!isset($pseudoAuth->token)) {
					throw new Exception('Ошибка при попытке псевдоавторизации!');
				}
			} else {
				throw new Exception('Не удалось пройти псевдоавторизацию!');
			}
			curl_close($ch);
			require_once('PseudoAuth.php');
			return new PseudoAuth($pseudoAuth->token, substr($pseudoAuth->login, 0, 1));
		} catch (Exception $e) {
			if (!empty($e->getMessage())) {
				throw new Exception($e->getMessage());
			} else {
				throw new Exception('Не удалось пройти псевдоавторизацию!');
			}
		}
	}

	public function getCards(PseudoAuth $auth)
	{
		try {
			$curl = curl_init();
			curl_setopt($curl, CURLOPT_URL, $this->hostUrl . '/card');
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			$headers = array('Content-type: application/json', 'language: ru', 'Time-Zone: Asia/Almaty');
			$headers = array_merge($headers, array("Authorization: $auth->token"));
			curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
			$cards = curl_exec($curl);
			curl_close($curl);
			if ($cards !== "[]") {
				if (json_decode($cards)) {
					return json_decode($cards);
					if (isset($cards->message)) {
						throw new Exception('Ошибка при попытке получения привязанных карт: ' . $cards->message);
					}
				} else {
					throw new Exception('Не удалось получить привязанные карты!');
				}
			}
		} catch (Exception $e) {
			if (!empty($e->getMessage())) {
				throw new Exception($e->getMessage());
			} else {
				throw new Exception('Не удалось получить привязанные карты!');
			}
		}
	}

	public function payFromCard(InvoiceContinueDTO $continueDTO, PseudoAuth $pseudoAuth, $cardId = null)
	{
		try {
			$array = [
				'invoice_id' => $continueDTO->invoiceId,
				'key' => $continueDTO->invoiceKey,
			];
			isset($cardId) ? $array = array_merge($array, ['card_id' => $cardId]) : '';
			$ch = curl_init($this->hostUrl . '/invoice/pay-from-card');
			$headers = array('Content-type: application/json', 'language: ru', 'Time-Zone: Asia/Almaty');
			$headers = array_merge($headers, array("Authorization: $pseudoAuth->token"));
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($array));
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			$cardFrame = curl_exec($ch);
			if (json_decode($cardFrame)) {
				$cardFrame = json_decode($cardFrame);
				if (isset($cardFrame->message)) {
					throw new Exception('Ошибка при генерации фрейма оплаты с карты: ' . $cardFrame->message);
				}
				if (!isset($cardFrame->frame_url)) {
					throw new Exception('Не удалось создать инвойс!');
				}
			} else {
				throw new Exception('Не удалось получить фрейм ввода карты!');
			}
			curl_close($ch);
			require_once('CardFrame.php');
			return new CardFrame($cardFrame->frame_url, $cardFrame->operation_id, $cardFrame->payment_operation);
		} catch (Exception $e) {
			if (!empty($e->getMessage())) {
				throw new Exception($e->getMessage());
			} else {
				throw new Exception('Не удалось получить фрейм ввода карты!');
			}
		}
	}

	public function getReceipt(int $operationId, PseudoAuth $auth)
	{
		try {
			$curl = curl_init();
			curl_setopt($curl, CURLOPT_URL, $this->hostUrl . '/history/receipt/pdf/' . $operationId);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			$headers = array('Content-type: application/json', 'language: ru', 'Time-Zone: Asia/Almaty');
			$headers = array_merge($headers, array("Authorization: $auth->token"));
			curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
			$receipt = curl_exec($curl);
			if (json_decode($receipt)) {
				$receipt = json_decode($receipt);
				if (isset($receipt[0]->message) && $receipt[0]->message == 'Чек для данной операции не формировался') {
					while ($this->receiptCounter < 10) {
						$this->receiptCounter++;
						sleep(2);
						return self::getReceipt($operationId, $auth);
					}
					return '';
				} elseif (isset($receipt->message)) {
					throw new Exception('Ошибка при генерации чека: ' . $receipt->message);
				}
			} else {
				return chunk_split(base64_encode($receipt));
			}
		} catch (Exception $e) {
			if (!empty($e->getMessage())) {
				throw new Exception($e->getMessage());
			} else {
				throw new Exception('Формирование чека завершилось ошибкой');
			}
		}
	}

	public function getOperationData($operationId, Auth $auth)
	{


		try {
			$array = [
				'operation_ids' => [$operationId],
			];
			$ch = curl_init($this->hostUrl . '/history/transaction/get-operations-data');
			$headers = array('Content-type: application/json', 'language: ru', 'Time-Zone: Asia/Almaty');
			$headers = array_merge($headers, array("Authorization: $auth->token"));
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($array));
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			$operationData = curl_exec($ch);
			if (json_decode($operationData)) {
				$operationData = json_decode($operationData);
				if (isset($operationData->message)) {
					throw new Exception('Ошибка при попытке получить статус операции: ' . $operationData->message);
				}
			} else {
				throw new Exception('Не удалось получить статус операции!');
			}
			curl_close($ch);
			return $operationData;
		} catch (Exception $e) {
			if (!empty($e->getMessage())) {
				throw new Exception($e->getMessage());
			} else {
				throw new Exception('Не удалось получить статус операции!');
			}
		}
	}

}
