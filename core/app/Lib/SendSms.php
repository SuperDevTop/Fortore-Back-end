<?php

namespace App\Lib;

use MessageBird\Objects\Message;
use Textmagic\Services\TextmagicRestClient;
use Twilio\Rest\Client;
use Vonage\Client\Credentials\Basic;
use Vonage\SMS\Message\SMS;

class SendSms
{

    public function ourSms($to, $message)
    {
        $message = urlencode($message);
        $ourSmsUsername = config('services.our_sms.username');
        $ourSmsPassword = config('services.our_sms.password');
        @file_get_contents("http://www.oursms.net/api/sendsms.php?username=$ourSmsUsername&password=$ourSmsPassword&numbers=$to&message=$message&unicode=E&return=json&sender=Fortoremall");
    }

    public function clickatell($to, $fromName, $message, $credentials)
    {
        $message = urlencode($message);
        $ourSmsUsername = config('services.our_sms.username');
        $ourSmsPassword = config('services.our_sms.password');
        @file_get_contents("http://www.oursms.net/api/sendsms.php?username=$ourSmsUsername&password=$ourSmsPassword&numbers=$to&message=$message");
    }

    public function infobip($to, $fromName, $message, $credentials)
    {
        $message = urlencode($message);
        @file_get_contents("https://api.infobip.com/api/v3/sendsms/plain?user=$credentials->infobip_username&password=$credentials->infobip_password&sender=$fromName&SMSText=$message&GSM=$to&type=longSMS");
    }

    public function messageBird($to, $fromName, $message, $credentials)
    {
        $MessageBird = new \MessageBird\Client($credentials->message_bird_api_key);
        $Message = new Message();
        $Message->originator = $fromName;
        $Message->recipients = array($to);
        $Message->body = $message;
        $MessageBird->messages->create($Message);
    }

    public function nexmo($to, $fromName = 'admin', $message, $credentials)
    {
        $basic = new Basic($credentials->nexmo_api_key, $credentials->nexmo_api_secret);
        $client = new \Vonage\Client($basic);
        $response = $client->sms()->send(
            new SMS($to, $fromName, $message)
        );
        $message = $response->current();
    }

    public function smsBroadcast($to, $fromName, $message, $credentials)
    {
        $message = urlencode($message);
        $response = @file_get_contents("https://api.smsbroadcast.com.au/api-adv.php?username=$credentials->sms_broadcast_username&password=$credentials->sms_broadcast_password&to=$to&from=$fromName&message=$message&ref=112233&maxsplit=5&delay=15");
    }

    public function twilio($to, $fromName, $message, $credentials)
    {
        $account_sid = $credentials->account_sid;
        $auth_token = $credentials->auth_token;
        $twilio_number = $credentials->from;

        $client = new Client($account_sid, $auth_token);
        $client->messages->create(
            '+' . $to,
            array(
                'from' => $twilio_number,
                'body' => $message
            )
        );
    }

    public function textMagic($to, $fromName, $message, $credentials)
    {
        $client = new TextmagicRestClient($credentials->text_magic_username, $credentials->apiv2_key);
        $result = $client->messages->create(
            array(
                'text' => $message,
                'phones' => $to
            )
        );
    }

}
