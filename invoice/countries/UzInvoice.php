<?php

class UzInvoice
{
	const OPTION_STANDARD = 0;
	const OPTION_LINKED_CARD = 4;
	const PARTNER_NAME = 'wooppay_uz';

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
}
