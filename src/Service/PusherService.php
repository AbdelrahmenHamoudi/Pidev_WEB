<?php

namespace App\Service;

use Pusher\Pusher;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class PusherService
{
    private $pusher;

    public function __construct(ParameterBagInterface $params)
    {
        $appId = $_ENV['PUSHER_APP_ID'] ?? 'YOUR_PUSHER_APP_ID';
        $key = $_ENV['PUSHER_KEY'] ?? 'YOUR_PUSHER_KEY';
        $secret = $_ENV['PUSHER_SECRET'] ?? 'YOUR_PUSHER_SECRET';
        $cluster = $_ENV['PUSHER_CLUSTER'] ?? 'eu';

        $options = [
            'cluster' => $cluster,
            'useTLS' => true
        ];

        $this->pusher = new Pusher(
            $key,
            $secret,
            $appId,
            $options
        );
    }

    public function trigger(string $channel, string $event, array $data)
    {
        try {
            $this->pusher->trigger($channel, $event, $data);
        } catch (\Exception $e) {
            // Silently fail if Pusher is not configured properly yet
        }
    }
}
