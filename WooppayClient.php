<?php

class WooppayClient
{
	public $url;
	public $merchant_name;
	public $password;

	public $reference_id;
	public $amount;
	public $back_url;
	public $request_url;
	public $email = '';
	public $user_phone = '';
	public $service_name = '';
	public $linkCard;

	public $invoice;
	public $transport;
	public $auth;

	const KZ_COUNTRY_CODE = 1;
	const UZ_COUNTRY_CODE = 860;
	const TJ_COUNTRY_CODE = 762;

	public function __construct($url = '', $mechant_name = '', $password = '')
	{
		$this->url = $url;
		$this->merchant_name = $mechant_name;
		$this->password = $password;
		try {
			if ($this->checkRequiredProperties($scenario = 'auth')) {
				$this->initTransport();
				$this->auth();
			}
		} catch (Exception $e) {
			throw $e;
		}
	}

	private function auth()
	{
		try {
			$this->auth = $this->transport->sendRequest('/auth',
				['login' => $this->merchant_name, 'password' => $this->password]);
			$this->transport->authorization = $this->auth->token;
		} catch (Exception $e) {
			if (!empty($e->getMessage())) {
				throw $e;
			} else {
				throw new Exception('Не удалось совершить вход в API, скорее всего неверный логин или пароль');
			}
		}
	}

	private function getInvoiceByCountry($country)
	{
		switch ($country) {
			case self::KZ_COUNTRY_CODE:
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

	public function createInvoice()
	{
		try {
			if ($this->checkRequiredProperties($scenario = 'operation')) {
				$this->invoice = $this->getInvoiceByCountry($this->auth->country);
				$this->invoice->transport = $this->transport;
				$this->invoice->transport->partner_name = $this->invoice->getPartnerName();
				$this->setInvoiceData();
				return $this->invoice->create();
			}
		} catch (Exception $e) {
			throw $e;
		}
	}

	private function initTransport()
	{
		require_once('Transport.php');
		$this->transport = new Transport();
		$this->transport->url = $this->url;
	}

	private function setInvoiceData()
	{
		$this->invoice->reference_id = $this->reference_id;
		$this->invoice->amount = $this->amount;
		$this->invoice->merchant_name = $this->merchant_name;
		$this->invoice->back_url = $this->back_url;
		$this->invoice->request_url = $this->request_url;
		$this->invoice->user_phone = $this->user_phone;
		$this->invoice->option = $this->invoice->getOption($this->linkCard);
		$this->invoice->email = $this->email;
		$this->invoice->service_name = $this->service_name;
	}

	private function checkRequiredProperties($scenario)
	{
		switch ($scenario) {
			case 'auth':
				$properties = $this->getRequiredAuthPropertiesList();
				break;
			case 'operation':
				$properties = $this->getRequiredOperationPropertiesList();
				break;
		}
		foreach ($properties as $property) {
			if (empty($this->$property)) {
				throw new Exception($this->getPropertyErrorMessage($property));
			}
		}
		return true;
	}

	private function getRequiredAuthPropertiesList()
	{
		return [
			'url',
			'merchant_name',
			'password',
		];
	}

	private function getRequiredOperationPropertiesList()
	{
		return [
			'reference_id',
			'amount',
			'back_url',
			'request_url'
		];
	}

	private function getPropertyErrorMessage($property)
	{
		$messages = [
			'amount' => 'Не указан amount в заказе',
			'reference_id' => 'Не указан reference_id',
			'back_url' => 'Не указан back_url',
			'request_url' => 'Не указан request_url',
			'url' => 'Не указан API URL в настройках модуля',
			'merchant_name' => 'Не указан API Username в настройках модуля',
			'password' => 'Не указан API Password в настройках модуля',
		];
		return $messages[$property];
	}

	public function getOperationData($operation_id)
	{
		 return $this->transport->sendRequest('/history/transaction/get-operations-data', ['operation_ids' => [$operation_id]]);
	}

}
