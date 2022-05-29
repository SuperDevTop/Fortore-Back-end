<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
// use Gazzle\Http\Client;
use GuzzleHttp\Client as GuzzleHttpClient;
use GuzzleHttp\Psr7\Request;

class FatoorahServices
{
    private $baseUrl;
    private $headers;
    private $client;
    public function __construct(GuzzleHttpClient $client)
    {
        $this->baseUrl = env('FATOORAH_BASE_URL');
        $this->headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . env(' ')
        ];
        $this->client = $client;
    }

    private function buildRequest($url, $method, $body = null)
    {
        $request = new Request($method, $this->baseUrl . $url, $this->headers);
        if (!$body) {
            return false;
        }
        $response = $this->client->send($request, ['json' => $body]);
        if ($response->getStatusCode() == 200) {
            return false;
            // return json_decode($response->getBody()->getContents());
        }
    }

    private function sendPayment($user_id, $fee, $plan_id, $subscription_plan)
    {
        $requestData = $this->parseRequestData($user_id, $fee, $plan_id, $subscription_plan);
        $response = $this->buildRequest('/payments', 'POST', $requestData);
        if ($response) {
            $this->saveTransaction($user_id, $response['Data']['Invoiced']);
        }

        return $response;
    }
}
