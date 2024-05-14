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

    public static function add(string $key, Continy $item): void
    {
    }

    public static function get(string $key): Continy|null
    {
    }

    public static function remove(string $key): void
    {
    }

    private function __construct()
    {
    }
}
