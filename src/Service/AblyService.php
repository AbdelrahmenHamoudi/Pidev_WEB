<?php

namespace App\Service;

use Ably\AblyRest;

class AblyService
{
    private $ably;
    private $apiKey;

    public function __construct(string $apiKey = null)
    {
        $this->apiKey = $apiKey;
        
        if ($this->apiKey) {
            $this->ably = new AblyRest($this->apiKey);
        }
    }

    public function isConfigured(): bool
    {
        return $this->ably !== null;
    }

    public function getAbly(): ?AblyRest
    {
        return $this->ably;
    }

    public function publishMessage(string $channelName, string $event, array $data): bool
    {
        if (!$this->ably) {
            return false;
        }

        try {
            $channel = $this->ably->channel($channelName);
            $channel->publish($event, $data);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getChannelHistory(string $channelName, int $limit = 100): array
    {
        if (!$this->ably) {
            return [];
        }

        try {
            $channel = $this->ably->channel($channelName);
            $history = $channel->history(['limit' => $limit]);
            
            $messages = [];
            foreach ($history->items as $item) {
                $messages[] = [
                    'id' => $item->id,
                    'data' => $item->data,
                    'timestamp' => $item->timestamp,
                    'clientId' => $item->clientId
                ];
            }
            
            return array_reverse($messages);
        } catch (\Exception $e) {
            return [];
        }
    }

    public function generateTokenRequest(array $capability = ['*' => ['publish', 'subscribe', 'history', 'presence']], string $clientId = null): ?array
    {
        if (!$this->ably) {
            return null;
        }

        try {
            $tokenParams = [
                'clientId' => $clientId ?? ('user_' . uniqid()),
                'capability' => $capability
            ];
            
            $tokenRequest = $this->ably->auth->createTokenRequest($tokenParams);
            return (array) $tokenRequest;
        } catch (\Exception $e) {
            error_log('Ably Token Request Error: ' . $e->getMessage());
            return null;
        }
    }
}