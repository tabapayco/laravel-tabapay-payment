<?php

namespace Tabapay\Payment;

class TabaPay
{
    private static $createUrl;
    private static $verifyUrl;
    private static $paymentUrl;
    private static $merchant;

    private static function setConfig()
    {
        self::$merchant = config('payment.merchant_code');
        self::$createUrl = 'https://api.tabapay.ir/v1/create';
        self::$verifyUrl = 'https://api.tabapay.ir/v1/verify';
        self::$paymentUrl = 'https://api.tabapay.ir/pay/';

        if (config('payment.sandbox', false)) {
            self::$createUrl = 'https://api.tabapay.ir/v1/sandbox/create';
            self::$verifyUrl = 'https://api.tabapay.ir/v1/sandbox/verify';
            self::$paymentUrl = 'https://api.tabapay.ir/sandbox/pay/';
        }
    }

    public static function createTransaction(
		int $amount,
		string $callbackURL,
		?string $mobile = null,
		?string $email = null,
		?string $name = null,
		?int $sms = null,
		?string $cardNumber = null,
		?string $nationalCode = null,
		?string $description = null,
		?array $additionalData = null
	){
        self::setConfig();
			$data = array_filter([
				'amount' => $amount,
				'callbackURL' => $callbackURL,
				'mobile' => $mobile,
				'email' => $email,
				'name' => $name,
				'sms' => $sms,
				'cardNumber' => $cardNumber,
				'nationalCode' => $nationalCode,
				'description' => $description,
				'additionalData' => $additionalData,
			], fn($value) => !is_null($value));

			$postData = json_encode($data);
			return self::sendRequest('post', self::$createUrl, $postData);
	}

	public static function verifyTransaction(string $token, int $amount)
    {
        self::setConfig();
		$maxAttempts = 3;
		$attempt = 0;
		$responseData = null;

		while ($attempt < $maxAttempts && empty($responseData['status'])) {
			$data = [
				'token' => $token,
				'amount' => $amount,
			];
			$postData = json_encode($data);
			$responseData = self::sendRequest('post', self::$verifyUrl, $postData);
			$attempt++;
		}

		return $responseData ?? [
			'status' => 'error',
			'responseCode' => 0,
			'message' => 'Verification failed after multiple attempts',
			'token' => $token,
			'amount' => $amount,
		];
	}

    private static function sendRequest(string $method, string $url, ?string $postData = null)
    {
        self::setConfig();
        $headers = [
            'Authorization: Bearer ' . self::$merchant,
            'Content-Type: application/json',
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // غیرفعال کردن تأیید SSL
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); // غیرفعال کردن تأیید هاست
        if (strtolower($method) === 'post') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        }

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            throw new \Exception('Curl error: ' . curl_error($ch));
        }

        curl_close($ch);

        return json_decode($response, true);
    }
}
