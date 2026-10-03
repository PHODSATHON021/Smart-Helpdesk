<?php
declare(strict_types=1);

namespace App\Core;

/** Dependency Injection Container แบบง่าย (singleton ต่อ id) */
final class Container
{
    private array $factories = [];
    private array $instances = [];

    public function bind(string $id, callable $factory): void
    {
        $this->factories[$id] = $factory;
    }

    public function get(string $id): mixed
    {
        if (!isset($this->instances[$id])) {
            if (!isset($this->factories[$id])) {
                throw new \RuntimeException("Service not bound: {$id}");
            }
            $this->instances[$id] = ($this->factories[$id])($this);
        }
        return $this->instances[$id];
    }
}
