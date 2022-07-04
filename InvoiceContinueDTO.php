<?php


class InvoiceContinueDTO
{
	public $invoiceId;
	public $invoiceKey;
	public $partnerName;
	public $linkCard;
	public $userPhone;
	public $testMode;
	public $token;
	public $finishUrl;
	public $orderId;
	public $operationId;

	public function __construct(
		$invoiceId = '',
		$invoiceKey = '',
		$partnerName = '',
		$linkCard = '',
		$userPhone = '',
		$testMode = '',
		$token = '',
		$finishUrl = '',
		$orderId = '',
		$operationId = ''
	) {
		foreach (get_defined_vars() as $value => $key) {
			if ($key === '' || $key === null) {
				isset($_POST['step']) ? $step = 'На шаге: ' . $_POST['step'] : $step = '';
				throw new Exception('Класс: ' . get_class($this) . ' Поле: ' . $value . ' пустое! ' . $step);
			}
		}
		$this->invoiceId = $invoiceId;
		$this->invoiceKey = $invoiceKey;
		$this->partnerName = $partnerName;
		$this->linkCard = $linkCard;
		$this->userPhone = $userPhone;
		$this->testMode = $testMode;
		$this->token = $token;
		$this->finishUrl = $finishUrl;
		$this->orderId = $orderId;
		$this->operationId = $operationId;
	}
}