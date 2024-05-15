<?php

namespace ShoplicKr\Continy;

final class Pool
{
    public static self|null $instance = null;

    /** @var array<string, Continy> */
    private array $items = [];

    private static function getInstance(): self
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public static function add(string $key, Continy $item): Continy
    {
        self::getInstance()->items[$key()] = $item;

        return $item;
    }

    public static function get(string $key): Continy|null
    {
        return self::getInstance()->items[$key] ?? null;
    }

    public static function remove(string $key): void
    {
        unset(self::getInstance()->items[$key]);
    }

    private function __construct()
    {
    }
}
