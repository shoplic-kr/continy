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

    public static function add(Continy $item): Continy
    {
        self::getInstance()->items[$item->getKey()] = $item;

        return $item;
    }

    public static function get(string $itemKey): Continy|null
    {
        return self::getInstance()->items[$itemKey] ?? null;
    }

    public static function remove(string $itemKey): void
    {
        unset(self::getInstance()->items[$itemKey]);
    }

    private function __construct()
    {
    }
}
