<?php

namespace ShoplicKr\Continy;

use Psr\Container\ContainerInterface;

interface Container extends ContainerInterface
{
    public function getKey(): string;
}
