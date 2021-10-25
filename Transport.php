<?php


class Transport
{
	public $url;
	public $authorization;
	public $partner_name;

	public function sendRequest($methodUrl, $params, $curlMethod = CURLOPT_POST, $query = '')
	{
		if ($curl = curl_init()) {
			if ($curlMethod == CURLOPT_HTTPGET && !empty($params)) {
				$url = empty($query) ? $this->url . strval($methodUrl) . '?' . http_build_query($params) : $this->url . strval($methodUrl) . $query;
				curl_setopt($curl, CURLOPT_URL, $url);
			} else {
				curl_setopt($curl, CURLOPT_URL, $this->url . strval($methodUrl));
			}
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			$headers = array('Content-type: application/json', 'language: ru', 'Time-Zone: Asia/Almaty');
			if (isset($this->authorization)) {
				$headers = array_merge($headers, array("Authorization: $this->authorization"));
			} elseif (isset($_SESSION['wooppay']['pseudoAuth'])) {
				$headers = array_merge($headers, array("Authorization:" . $_SESSION['wooppay']['pseudoAuth'] . ""));
			}
			if (isset($this->partner_name)) {
				$headers = array_merge($headers, array("partner-name: $this->partner_name"));
			}
			if (!empty($params) && $curlMethod == CURLOPT_POST) {
				$headers = array_merge($headers, []);
				curl_setopt($curl, $curlMethod, true);
				curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($params));
			}
			curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
			$result = curl_exec($curl);
			if (json_decode($result)) {
				$result = json_decode($result);
			}
			if (curl_getinfo($curl)['http_code'] > 201) {
				if (is_array($result) && isset($result[0]->message)) {
					throw new Exception($result[0]->message);
				} else {
					throw new Exception($result->message);
				}
			}
			curl_close($curl);
			return $result;
		}
	}
}
