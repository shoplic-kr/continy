<?php

namespace ShoplicKr\Continy\Tests\DummyPlugin\ReflectionInjection;

class DependencyOne
{
    public function __construct(public DependencyOneOne $oneOne)
    {
    }
}
