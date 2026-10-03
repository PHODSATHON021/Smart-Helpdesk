<?php
declare(strict_types=1);

namespace App\Notifications;

use App\Core\Logger;

final class LineMessagingService implements NotificationChannelInterface
{
    private const PUSH_ENDPOINT = 'https://api.line.me/v2/bot/message/push';

    public function __construct(private string $channelToken, private string $defaultTo = '') {}

    public function send(string $message, ?string $to = null): bool
    {
        $to = $to ?: $this->defaultTo;
        if ($this->channelToken === '' || $to === '') {
            Logger::info("[LINE skipped - no token/recipient] {$message}");
            return false;
        }
        $res = $this->callApi(self::PUSH_ENDPOINT, [
            'to'       => $to,
            'messages' => [['type' => 'text', 'text' => mb_substr($message, 0, 4900)]],
        ]);
        return $res['status'] === 200;
    }

    /** @return array{status:int,body:string} */
    private function callApi(string $endpoint, array $body): array
    {
        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($body, JSON_UNESCAPED_UNICODE),
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'Authorization: Bearer ' . $this->channelToken],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 2,
            CURLOPT_TIMEOUT        => 4,   // สั้นๆ เพื่อไม่ให้ผู้ใช้รอนาน
        ]);
        $resp   = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($resp === false) {
            Logger::error('LINE cURL error: ' . curl_error($ch));
        } elseif ($status !== 200) {
            Logger::error("LINE API {$status}: {$resp}");
        }
        curl_close($ch);
        return ['status' => $status, 'body' => (string) $resp];
    }
}
