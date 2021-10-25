<?php


class Invoice
{
	public $reference_id;
	public $amount;
	public $back_url;
	public $request_url;
	public $merchant_name;

	public $option;

	public $linkCard;

	public $email = '';
	public $user_phone = '';
	public $service_name = '';
	public $card_forbidden = 0;

	public $transport;

	public function create()
	{
		$data = [
			'reference_id' => $this->reference_id,
			'amount' => $this->amount,
			'merchant_name' => $this->merchant_name,
			'request_url' => ['url' => $this->request_url],
			'back_url' => $this->back_url,
			'option' => $this->option,
			'card_forbidden' => $this->card_forbidden,
		];
		if (!empty($this->service_name)) {
			$data = array_merge($data, ['service_name' => $this->service_name]);
		}
		if ($this->linkCard == true) {
			$data = array_merge($data, ['user_phone' => $this->user_phone]);
		}
		return $this->transport->sendRequest('/invoice/create', $data);
	}

}
