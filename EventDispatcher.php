<?php
declare(strict_types=1);

namespace App\Core;

/** Observer Pattern: Service dispatch event แล้ว Observer ที่ listen อยู่จะถูกเรียก */
final class EventDispatcher
{
    /** @var array<string, callable[]> */
    private array $listeners = [];

    public function listen(string $event, callable $handler): void
    {
        $this->listeners[$event][] = $handler;
    }

    public function dispatch(string $event, array $payload = []): void
    {
        foreach ($this->listeners[$event] ?? [] as $handler) {
            try {
                $handler($payload);
            } catch (\Throwable $e) {
                // Listener ล้มเหลว (เช่น LINE ล่ม) ต้องไม่ทำให้ธุรกรรมหลักพัง
                Logger::error("Listener for {$event} failed: " . $e->getMessage());
            }
        }
    }
}
