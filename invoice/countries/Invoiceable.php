<?php


interface Invoiceable
{

	public function getOption($linkCard);

	public function getPartnerName();

	public function pseudoAuth();

}
