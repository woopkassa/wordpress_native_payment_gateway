<?php

class TjInvoice
{
	const OPTION_STANDARD = 8;
	const OPTION_LINKED_CARD = 9;
	const PARTNER_NAME = 'wooppay_kz';

	public function getOption($linkCard)
	{
		if (empty($this->user_phone)) {
			return self::OPTION_STANDARD;
		} elseif (!empty($this->user_phone) && $linkCard) {
			return self::OPTION_LINKED_CARD;
		} else {
			return self::OPTION_STANDARD;
		}
	}

	public  function getPartnerName()
	{
		return self::PARTNER_NAME;
	}

}
