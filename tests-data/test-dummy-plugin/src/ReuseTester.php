<?php

namespace ShoplicKr\Continy\Tests\DummyPlugin;

class ReuseTester
{
    public static int $constructCount = 999;

    public function __construct()
    {
        static::$constructCount++;
    }
}

