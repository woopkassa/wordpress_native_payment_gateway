<?php


class Invoice
{
	public $operationUrl;
	public $operationId;
	public $invoiceId;
	public $invoiceKey;
	public $partnerName;

	public function __construct(
		$operationUrl = '',
		$operationId = '',
		$invoiceId = '',
		$invoiceKey = '',
		$partnerName = ''
	) {
		foreach (get_defined_vars() as $value => $key) {
			if ($key === '' || $key === null) {
				isset($_POST['step']) ? $step = 'На шаге: ' . $_POST['step'] : $step = '';
				throw new Exception('Класс: ' . get_class($this) . ' Поле: ' . $value . ' пустое! ' . $step);
			}
		}
		$this->operationUrl = $operationUrl;
		$this->operationId = $operationId;
		$this->invoiceId = $invoiceId;
		$this->invoiceKey = $invoiceKey;
		$this->partnerName = $partnerName;
	}
}