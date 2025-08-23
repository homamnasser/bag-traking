<?php
namespace App\Services;

use Illuminate\Support\Facades\Http;

class WhatsAppService
{
    protected $instanceId;
    protected $token;

    public function __construct()
    {
        $this->instanceId = config('services.ultramsg.instance_id');
        $this->token = config('services.ultramsg.token');
    }

    public function sendMessage($to, $message)
    {
        $url = "https://api.ultramsg.com/{$this->instanceId}/messages/chat";

        $response = Http::asForm()->post($url, [
            'token' => $this->token,
            'to' => $to,
            'body' => $message,
        ]);

        return $response->json();
    }
}
