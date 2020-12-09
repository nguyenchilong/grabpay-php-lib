<?php
/**
 *
 * @Project: payment
 * @Filename: OneTimeCharge.php
 * @Author: longnc <nguyenchilong90@gmail.com>
 * @Created Date: 12/7/19 11:12 AM
 *
 * @Description: The class for one-time charge
 */

namespace GrabPay;


class OneTimeCharge extends GrabPay
{

	public function __construct(array $config)
	{
		parent::__construct(
			$config['partnerId'],
			$config['partnerSecret'],
			$config['clientId'],
			$config['clientSecret'],
			$config['merchantId']
		);
		$this->isProduction = $config['isProduction'];
	}
	/**
	 * Set up the details required to initiate a one-time payment.
	 *
	 * @param string $txId order ID
	 * @param string $groupTxId partner transaction ID
	 * @param int $amount Transaction amount as integer
	 * @param string $description description of the charge (optional)
	 *
	 * @return object
	 */
	public function initCharge(string $txId, string $groupTxId, int $amount, string $description = ''): object
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
		$requestUri = $this->getPartnerUrlPath('v2', '/charge/init');
		$hmacSignature = $this->generateHmacSignature('POST', 'application/json', $date, $requestUri, json_encode($data));
		return $this->callGrabApi('POST', $requestUri, [
			'Date' => $date,
			'Authorization' => $this->partnerId . ':' . $hmacSignature,
		], $data);
	}
	/**
	 * Complete the payment authorised by the user.
	 *
	 * @param string $accessToken OAuth access token
	 * @param string $partnerTxId partner transaction ID
	 *
	 * @return object
	 */
	public function completeCharge(string $accessToken, string $partnerTxId): object
	{
		$data = [
			'partnerTxID' => $partnerTxId,
		];
		$date = $this->generateHeaderDate();
		return $this->callGrabApi('POST', $this->getPartnerUrlPath('v2', '/charge/complete'), [
			'Date' => $date,
			'Authorization' => 'Bearer ' . $accessToken,
			'X-GID-AUX-POP' => $this->generatePopSignature($accessToken, $date),
		], $data);
	}
}
