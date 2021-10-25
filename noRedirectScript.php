<?php

session_start();

class noRedirectScript
{
	public $transport;
	private $wallet_type;

	public function __construct()
	{
		$this->initTransport();
	}

	private function initTransport()
	{
		require_once('Transport.php');
		$this->transport = new Transport();
		$this->transport->partner_name = $_SESSION['wooppay']['partner_name'];
		$this->transport->url = $_SESSION['wooppay']['api_url'];
	}

	public function pseudoAuth()
	{
		$data = [
			'login' => $_SESSION['wooppay']['user_phone']
		];
		if ($_SESSION['wooppay']['link_card'] == 'true') {
			$this->transport->authorization = $_SESSION['wooppay']['authorization'];
			$data = array_merge($data, ['subject_type' => 5019]);
		}
		$auth = $this->transport->sendRequest('/auth/pseudo', $data);
		$this->transport->authorization = $auth->token;
		$this->wallet_type = substr($auth->login, 0, 1);
		$_SESSION['wooppay']['wallet_type'] = $this->wallet_type;
		$_SESSION['wooppay']['pseudoAuth'] = $auth->token;
	}

	public function getCards()
	{
		if ($_SESSION['wooppay']['wallet_type'] == 'G') {
			if (!isset($_SESSION['wooppay']['cards_saved'])) {
				$cards = $this->transport->sendRequest('/card', '', CURLOPT_HTTPGET);
				if (!empty($cards)) {
					$_SESSION['wooppay']['cards_saved'] = serialize($cards);
					return $cards;
				}
			} else {
				return unserialize($_SESSION['wooppay']['cards_saved']);
			}


			return false;
		}
		return false;
	}

	public function payFromCard()
	{
		if (!isset($_SESSION['wooppay']['frame_saved'])) {
			if (isset($_SESSION['wooppay']['cards'])) {
				$_SESSION['wooppay']['cards'] = false;
			}
			$data = [
				'invoice_id' => $_SESSION['wooppay']['invoice_id'],
				'key' => $_SESSION['wooppay']['invoice_key'],
			];
			if ((isset($_POST['card_id']) & !empty($_POST['card_id'])) || isset($_SESSION['wooppay']['card_id'])) {
				if (isset($_POST['card_id'])) {
					$_SESSION['wooppay']['card_id'] = $_POST['card_id'];
				}
				$data = array_merge($data, ['card_id' => $_SESSION['wooppay']['card_id']]);
			}
			$this->transport->authorization = $_SESSION['wooppay']['pseudoAuth'];
			$payFromCard = $this->transport->sendRequest('/invoice/pay-from-card', $data);
			$frame = "<iframe src='$payFromCard->frame_url' width='600px' height='550px' style='border: none; width: 600px; height: 550px' frameborder='no' align='middle'> </iframe>";
			$_SESSION['wooppay']['frame_saved'] = $frame;
			$_SESSION['wooppay']['payment_operation'] = $payFromCard->payment_operation;
		}
		echo $_SESSION['wooppay']['frame_saved'];
	}
}

if (isset($_SESSION['wooppay'])) {
	$class = new noRedirectScript();
	if (!isset($_POST['woop_frame_status'])) {
		if (!isset($_SESSION['wooppay']['pseudoAuth'])) {
			$class->pseudoAuth();
		}
		if ((!isset($_SESSION['wooppay']['cards']) || $_SESSION['wooppay']['cards'] == true) && (!isset($_POST['card_id']) && !isset($_SESSION['wooppay']['card_id']))) {
			$cards = $class->getCards();
			if (!empty($cards) && $cards !== "[]") {
				$_GET['cards'] = $cards;
				$_SESSION['wooppay']['cards'] = true;
				return require('linkedList.php');
			}
		}
		$class->payFromCard();
	} else {
		switch ($_POST['woop_frame_status']) {
			case 1:
				$data = [
					$_SESSION['wooppay']['payment_operation'],
				];
				$class->transport->authorization = $_SESSION['wooppay']['pseudoAuth'];
				$count = 0;
				do {
					$success = true;
					try {
						if ($count == 10){
							$fileString = 'none';
						} else {
							$fileString = $class->transport->sendRequest('/history/receipt/pdf', $data, CURLOPT_HTTPGET,
								'/' . $_SESSION['wooppay']['payment_operation']);
							$fileString = chunk_split(base64_encode($fileString));
						}
						$_GET['wooppay_frame_status'] = $_POST['woop_frame_status'];
						$_GET['wooppay_frame_message'] = 'Оплата успешно проведена!';
						ob_start();
						require('result.php');
						$result = ob_get_contents();
						ob_clean();
						$answer = json_encode([
							$result,
							$fileString,
							$_SESSION['wooppay']['finish_url']
						]);
						echo $answer;
					} catch (Exception $e) {
						sleep(1);
						$count++;
						$success = false;
						continue;
					}
				} while (!$success);
				break;
			default:
				$_GET['wooppay_frame_status'] = $_POST['woop_frame_status'];
				$_GET['wooppay_frame_message'] = $_POST['woop_frame_error'];
				ob_start();
				require('result.php');
				$result = ob_get_contents();
				ob_clean();
				$answer = json_encode([$result]);
				echo $answer;
		}
		unset($_SESSION['wooppay']);
	}
}


