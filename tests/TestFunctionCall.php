<?php

namespace ShoplicKr\Continy\Tests;

use ShoplicKr\Continy\Continy;
use ShoplicKr\Continy\ContinyException;
use ShoplicKr\Continy\Tests\DummyPlugin\FunctionCall\Dep_A;
use ShoplicKr\Continy\Tests\DummyPlugin\FunctionCall\Dep_B;
use ShoplicKr\Continy\Tests\DummyPlugin\FunctionCall\FunctionCall;
use WP_UnitTestCase;
use function ShoplicKr\Continy\Tests\DummyPlugin\getTestDummyPlugin;

class TestFunctionCall extends WP_UnitTestCase
{
    protected Continy $continy;

    public function setUp(): void
    {
        $this->continy = getTestDummyPlugin();
    }

    /**
     * @throws ContinyException
     */
    public function testCall()
    {
        $return = $this->continy->call([FunctionCall::class, 'call']);
        $this->assertEquals('success', $return);

        $a = $this->continy->get(Dep_A::class);
        $this->assertEquals(1, $a->count);

        $b = $this->continy->get(Dep_B::class);
        $this->assertEquals(1, $b->count);
    }

    public function testConfiguredCall()
    {
        $instance = $this->continy->get(FunctionCall::class);
        $method   = [$instance, 'configuredCall'];

        $return = $this->continy->call($method, ['Hello', ', World!']);
        $this->assertEquals('Hello, World!', $return);

        $return = $this->continy->call($method, ['y' => ', World!', 'x' => 'Hello']);
        $this->assertEquals('Hello, World!', $return);

        // Configured method call.
        $return = $this->continy->call($method);
        $this->assertEquals('KeyboardMouse', $return);
    }
}
