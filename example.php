<?php

namespace Payment;

use OneTimeCharge;

class PaymentController
{

  // the method to get redirect URL for make payment
	public function initCharge($request): array
	{
		try {
			$otCharge = $this->initGrabPay();
			$txId = $this->getPartnerId($request->orderId);
			$groupTxId = $this->getGroupId($request->orderId);
			$initCharge = $otCharge->initCharge($txId, $groupTxId, $totalAmount, 'description for payment');
			if(!empty($initCharge->request)) {
				$codeVerifier = $this->generateRandomString(64);
				$redirectURI = $this->getRedirectURL();
				$redirectURL = $otCharge->getOauthAuthorizeUrl(
					$codeVerifier,
					$initCharge->request,
					$redirectURI,
					'openid payment.one_time_charge'
				);
				return [
					'orderId'  => $request->orderId,
					'verifier' => $codeVerifier,
					'payURL'   => $redirectURL
				];
			} else {
				return [];
			}
		} catch (\Exception $exception) {
			return ['error' => $exception->getMessage()];
		}
	}

  // make complete payment from "code" response of GrabPay
	public function completePayment($request): bool
	{
		try {
			$otCharge = $this->initGrabPay();
			$redirectURI = $this->getRedirectURL();
			$accessToken = $otCharge->getAccessToken($request->responseCode, $redirectURI, $request->verifier);
			if (!empty($accessToken->access_token)) {
				$partnerId = $this->getPartnerId($request->orderId);
				$completePay = $otCharge->completeCharge($accessToken->access_token, $partnerId);
				$statusPay = !empty($completePay->status) ? mb_strtolower($completePay->status, 'UTF-8') : '';
        return ($statusPay === 'success') ? true : false;
			}
      return false;
		} catch (\Exception $exception) {
      return false;
		}
	}

	/**
	 * Init GrabPay for One Time Charge
	 * @return OneTimeCharge
	 */
	private function initGrabPay(): object
	{
		$env = env('APP_ENV');
		return new OneTimeCharge(array(
			'partnerId'     => env('GRABPAY_PARTNER_ID'),
			'partnerSecret' => env('GRABPAY_PARTNER_SECRET'),
			'clientId'      => env('GRABPAY_CLIENT_ID'),
			'clientSecret'  => env('GRABPAY_CLIENT_SECRET'),
			'merchantId'    => env('GRABPAY_MERCHANT_ID'),
			'isProduction'  => ($env === 'production' ? true : false)
		));
	}

	/**
	 * format partnerTxId string for init charge
	 * @param int $orderId
	 * @return string
	 */
	private function getPartnerId(int $orderId): string
	{
		return 'ORD'.$orderId;
	}

	/**
	 * format groupTxId for init charge
	 * @param int $orderId
	 * @return string
	 */
	private function getGroupId(int $orderId): string
	{
		return 'Group'.$orderId;
	}

	/**
	 * get full redirect url for get response code
	 * @param string $redirectURI
	 * @return string
	 */
	private function getRedirectURL(string $redirectURI = ''): string
	{
		$slash = '/';
		$redirectURI = empty($redirectURI) ? env('GRABPAY_REDIRECT_URI') : $redirectURI;
		return trim(env('GRABPAY_BASE_DOMAIN_RESPONSE'), $slash).$slash.trim($redirectURI, $slash);
	}
  
  /**
	 * random string with length
	 * @param int $length
	 * @return string
	 */
  private function generateRandomString($length = 16): string {
      $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
      $charactersLength = strlen($characters);
      $randomString = '';
      for ($i = 0; $i < $length; $i++) {
          $randomString .= $characters[rand(0, $charactersLength - 1)];
      }
      return $randomString;
  }
}
