<?php
/**
 *
 * @Project: aceprint
 * @Filename: Tokenization.php
 * @Author: longnc <nguyenchilong90@gmail.com>
 * @Created Date: 12/7/20 11:13 AM
 *
 * @Description: Text description here
 */

namespace App\Libraries\GrabPay;


class Tokenization extends GrabPay
{
	/**
	 * Initiate the binding process with customer credits.
	 *
	 * @param string $txId order ID
	 *
	 * @return object
	 */
	public function bind(string $txId): object
	{
		$data = [
			'partnerTxID' => $txId,
			'countryCode' => $this->countryCode,
		];
		$date = $this->generateHeaderDate();
		$requestUri = $this->getPartnerUrlPath('v2', '/bind');
		$hmacSignature = $this->generateHmacSignature('POST', 'application/json', $date, $requestUri, json_encode($data));
		return $this->callGrabApi('POST', $requestUri, [
			'Date' => $date,
			'Authorization' => $this->partnerId . ':' . $hmacSignature,
		], $data);
	}
	/**
	 * Charge a customer who has completed the bind process with GrabPay.
	 *
	 * @param string $accessToken OAuth access token
	 * @param string $txId order ID
	 * @param string $groupTxId partner transaction ID
	 * @param int $amount ​Transaction amount as integer
	 * @param string $description description of the charge (optional)
	 *
	 * @return object
	 */
	public function charge(string $accessToken, string $txId, string $groupTxId, int $amount, string $description = ''): object
	{
		$data = [
			'partnerTxID' => $txId,
			'partnerGroupTxID' => $groupTxId,
			'amount' => $amount,
			'currency' => $this->currency,
			'merchantID' => $this->merchantId,
			'description' => $description,
		];
		$date = $this->generateHeaderDate();
		return $this->callGrabApi('POST', $this->getPartnerUrlPath('v2', '/charge'), [
			'Date' => $date,
			'Authorization' => 'Bearer ' . $accessToken,
			'X-GID-AUX-POP' => $this->generatePopSignature($accessToken, $date),
		], $data);
	}
	/**
	 * View the wallet balance of the bound user.
	 *
	 * @param string $accessToken OAuth access token
	 *
	 * @return object
	 */
	public function getWalletInfo(string $accessToken): object
	{
		$date = $this->generateHeaderDate();
		return $this->callGrabApi('GET', $this->getPartnerUrlPath('v2', '/wallet/info'), [
			'Date' => $date,
			'Authorization' => 'Bearer ' . $accessToken,
			'X-GID-AUX-POP' => $this->generatePopSignature($accessToken, $date),
		]);
	}
	/**
	 * Deactivate the token generated during the binding process.
	 *
	 * @param string $accessToken OAuth access token
	 * @param string $txId order ID
	 *
	 * @return object
	 */
	public function unbind(string $accessToken, string $txId): object
	{
		$data = [
			'partnerTxID' => $txId,
		];
		$date = $this->generateHeaderDate();
		return $this->callGrabApi('DELETE', $this->getPartnerUrlPath('v2', '/bind'), [
			'Date' => $date,
			'Authorization' => 'Bearer ' . $accessToken,
			'X-GID-AUX-POP' => $this->generatePopSignature($accessToken, $date),
		], $data);
	}
}
