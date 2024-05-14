<?php

namespace ShoplicKr\Continy\Tests\DummyPlugin\ReflectionInjection;

class DependencyTwo
{
    public function __construct(public IDependencyTwoOne $twoOne)
    {
    }
}
