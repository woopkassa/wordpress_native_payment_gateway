<?php

require_once(__DIR__ . '/../Invoice.php');
require_once('Invoiceable.php');

class KzInvoice extends Invoice implements Invoiceable
{
	const OPTION_STANDARD = 0;
	const OPTION_LINKED_CARD = 4;
	const PARTNER_NAME = 'wooppay_kz';

	public function getOption($linkCard)
	{
		if (empty($this->user_phone)) {
			return self::OPTION_STANDARD;
		} elseif (!empty($this->user_phone) && $linkCard) {
			$this->linkCard = true;
			return self::OPTION_LINKED_CARD;
		} else {
			return self::OPTION_STANDARD;
		}
	}

	public  function getPartnerName()
	{
		return self::PARTNER_NAME;
	}

	public function pseudoAuth()
	{
		if ($this->option !== self::OPTION_LINKED_CARD) {
			$this->transport->authorization = '';
		}
		parent::pseudoAuth();
	}
}
