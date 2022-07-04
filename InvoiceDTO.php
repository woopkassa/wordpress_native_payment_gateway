<?php


class InvoiceDTO
{
	public $referenceId;
	public $amount;
	public $requestUrl;
	public $backUrl;
	public $linkCard;
	public $userPhone;
	public $serviceName;

	public function __construct(
		$referenceId = '',
		$amount = '',
		$requestUrl = '',
		$backUrl = '',
		$linkCard = '',
		$userPhone = '',
		$serviceName = ''
	) {
		foreach (get_defined_vars() as $value => $key) {
			if ($key === '' || $key === null) {
				isset($_POST['step']) ? $step = 'На шаге: ' . $_POST['step'] : $step = '';
				throw new Exception('Класс: ' . get_class($this) . ' Поле: ' . $value . ' пустое! ' . $step);
			}
		}
		$this->referenceId = $referenceId;
		$this->amount = $amount;
		$this->requestUrl = $requestUrl;
		$this->backUrl = $backUrl;
		$this->linkCard = $linkCard;
		$this->userPhone = $userPhone;
		$this->serviceName = $serviceName;
	}
}