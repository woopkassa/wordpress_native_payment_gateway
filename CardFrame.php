<?php


class CardFrame
{
	public $frameUrl;
	public $operationId;
	public $paymentOperation;

	public function __construct($frameUrl = '', $operationId = '', $paymentOperation = '')
	{
		foreach (get_defined_vars() as $value => $key) {
			if ($key === '' || $key === null) {
				isset($_POST['step']) ? $step = 'На шаге: ' . $_POST['step'] : $step = '';
				throw new Exception('Класс: ' . get_class($this) . ' Поле: ' . $value . ' пустое! ' . $step);
			}
		}
		$this->frameUrl = $frameUrl;
		$this->operationId = $operationId;
		$this->paymentOperation = $paymentOperation;
	}
}