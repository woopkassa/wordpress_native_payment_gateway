<?php


class PseudoAuth
{
	public $token;
	public $walletType;

	public function __construct($token = '', $walletType = '')
	{
		foreach (get_defined_vars() as $value => $key) {
			if ($key === '' || $key === null) {
				isset($_POST['step']) ? $step = 'На шаге: ' . $_POST['step'] : $step = '';
				throw new Exception('Класс: ' . get_class($this) . ' Поле: ' . $value . ' пустое! ' . $step);
			}
		}
		$this->token = $token;
		$this->walletType = $walletType;
	}

}