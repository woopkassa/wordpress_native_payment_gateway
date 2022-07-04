<?php


class ApiDTO
{
	public $apiUsername;
	public $apiPassword;

	public function __construct($apiUsername = '', $apiPassword = '')
	{
		foreach (get_defined_vars() as $value => $key) {
			if ($key === '' || $key === null) {
				isset($_POST['step']) ? $step = 'На шаге: ' . $_POST['step'] : $step = '';
				throw new Exception('Класс: ' . get_class($this) . ' Поле: ' . $value . ' пустое! ' . $step);
			}
		}
		$this->apiUsername = $apiUsername;
		$this->apiPassword = $apiPassword;
	}

}