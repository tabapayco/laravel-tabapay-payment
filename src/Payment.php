<?php

namespace Tabapay\Payment;

class TabaPay
{
    private $createUrl;
    private $verifyUrl;
    private $paymentUrl;
    private $merchant;

    public function __construct()
    {
        $this->merchant = config('payment.merchant_code');
        $this->createUrl = 'https://api.tabapay.ir/v1/create';
        $this->verifyUrl = 'https://api.tabapay.ir/v1/verify';
        $this->paymentUrl = 'https://api.tabapay.ir/pay/';

        if (config('payment.sandbox', false)) {
            $this->createUrl = 'https://api.tabapay.ir/v1/sandbox/create';
            $this->verifyUrl = 'https://api.tabapay.ir/v1/sandbox/verify';
            $this->paymentUrl = 'https://api.tabapay.ir/sandbox/pay/';
        }
    }

    public function createTransaction(
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

			return $this->sendRequest('post', $this->createUrl, $postData);
	}

	public function verifyTransaction(string $token, int $amount){
		$maxAttempts = 3;
		$attempt = 0;
		$responseData = null;

		while ($attempt < $maxAttempts && empty($responseData['status'])) {
			$data = [
				'token' => $token,
				'amount' => $amount,
			];
			$postData = json_encode($data);
			$responseData = $this->sendRequest('post', $this->verifyUrl, $postData);
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

    private function sendRequest(string $method, string $url, ?string $postData = null)
    {
        $headers = [
            'Authorization: Bearer ' . $this->merchant,
            'Content-Type: application/json',
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

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
