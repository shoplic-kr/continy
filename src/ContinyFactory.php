<?php

declare(strict_types=1);

namespace ShoplicKr\Continy;

class ContinyFactory
{
    public static function create(array|string|Config $config): Continy
    {
        return new Continy($config);
    }
}
