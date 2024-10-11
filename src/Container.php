<?php

namespace ShoplicKr\Continy;

use Psr\Container\ContainerInterface;

interface Container extends ContainerInterface
{
    public function call(callable|array|string $callable, array|callable $args = []);
}
