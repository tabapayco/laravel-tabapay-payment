# Tabapay Payment Package for Laravel

A flexible payment integration package for TabaPay API in Laravel.

## Installation

1. Install via Composer:
composer require tabapay/payment

2. Publish the configuration (optional):
php artisan vendor:publish --tag=config

3. Add your merchant code to `.env`:
TABAPAY_MERCHANT_CODE=your-merchant-code
TABAPAY_SANDBOX=false

## Usage
```php

### Create a Transaction

use Tabapay\Payment\Facades\Payment;

$data = [
    'amount' => 10000,
    'callbackURL' => 'https://your-site.com/verify',
    'mobile' => '09123456789', // optional
    'email' => 'user@example.com', // optional
	'name' => null,
    'sms' => 0, // True or False
    'cardNumber' => null,
    'nationalCode' => null,
    'description' => null,
    'additionalData' => null,
	
];
$response = TabaPay::createTransaction($data);

if ($response['status'] === 'success') {
    return redirect($response['url']);
}

### Verify a Transaction

The `verifyTransaction` method sends a verification request to the TabaPay API and retries up to 3 times if needed. Use it for manual verification after a GET callback. Automatic verification by TabaPay requires separate handling in your controller.

use Tabapay\Payment\Facades\Payment;

public function verifyCallback(Request $request)
{
	if (!empty($request->header('HTTP_AUTHORIZE')) && md5(config('payment.merchant_code')) === $request->header('HTTP_AUTHORIZE')) {
        $responseData = $request->all(); // Data from TabaPay POST
        if ($responseData['status'] === 'success' && $responseData['responseCode'] == 1) {
            // Transaction verified successfully
            return response()->json($responseData);
        }
    }elseif ($request->query('status') === 'success' && $request->query('responseCode') == 1) {
		$token = $request->query('token');
		$amount = $request->query('amount');
        $response = TabaPay::verifyTransaction($token, $amount);
        return response()->json($response);
    }
	
#Response
	$responseData = array(
		"status" => $responseData['status'] ?? null,
		"responseCode" => $responseData['responseCode'] ?? null,
		"message" => $responseData['message'] ?? null,
		"token" => $responseData['token'] ?? null,
		'trackingCode' => $responseData['trackingCode'] ?? null,
		"shaparakRefNumber" => $responseData['shaparakRefNumber'] ?? null,
		"cardNumber" => $responseData['cardNumber'] ?? null,
		"hashedCardNumber" => $responseData['hashedCardNumber'] ?? null,
		"amount" => $responseData['amount'] ?? null,
		"finalAmount" => $responseData['finalAmount'] ?? null,
		"ip" => $responseData['ip'] ?? null,
		"date" => $responseData['date'] ?? null,
		"additionalData" => !empty($responseData['additionalData']) ? $responseData['additionalData'] : null
	);
}
