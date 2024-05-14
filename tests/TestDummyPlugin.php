<?php

namespace ShoplicKr\Continy\Tests;

use ShoplicKr\Continy\Continy;
use WP_UnitTestCase;

use function ShoplicKr\Continy\Tests\DummyPlugin\getTestDummyPlugin;

/**
 * Class ContainerTest
 */
class TestDummyPlugin extends WP_UnitTestCase
{
    protected static string $pluginRoot;
    protected static string $pluginSetup;

    protected Continy $continy;

    public static function setUpBeforeClass(): void
    {
        self::$pluginRoot  = dirname(__FILE__, 2) . '/tests-data/test-dummy-plugin';
        self::$pluginSetup = self::$pluginRoot . '/conf/setup.php';
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->continy = getTestDummyPlugin();
    }

    public function test_getMain()
    {
        $this->assertEquals($this->continy->getMain(), self::$pluginRoot . '/test-dummy-plugin.php');
    }

    public function test_getVersion()
    {
        $array   = include self::$pluginSetup;
        $version = $array['version'] ?? false;

        $this->assertEquals($this->continy->getVersion(), $version);
    }

    public function test_get_ReuseTester()
    {
        $className = \ShoplicKr\Continy\Tests\DummyPlugin\ReuseTester::class;

        // Make sure the instance is created.
        $count = $className::$constructCount;
        $this->assertInstanceOf($className, $this->continy->get($className));
        $this->assertEquals($count + 1, $className::$constructCount);

        // The instance should be re-used. Thus, the count is unchanged.
        $this->continy->get($className);
        $this->assertEquals($count + 1, $className::$constructCount);

        // Manual construction should increase the count.
        new $className();
        /** @noinspection PhpConditionAlreadyCheckedInspection */
        $this->assertEquals($count + 2, $className::$constructCount);
    }

    public function test_get_ModuleCPT()
    {
        $className = \ShoplicKr\Continy\Tests\DummyPlugin\Modules\CPT::class;

        // Test if CPT can get by alias
        $this->assertInstanceOf($className, $this->continy->modCPT);

        // Test if module can get by FQCN.
        $instance = $this->continy->get($className);
        $this->assertInstanceOf($className, $instance);

        // Test if the post type is registered.
        $this->assertTrue(post_type_exists('dummy_type'));
        $this->assertFalse(post_type_exists('unknown_type'));
    }

    public function test_get_Binding_IDummy()
    {
        // Test interface - implementation binding.
        // By setup, DummyTypeOne is bound to interface.
        $Interface   = \ShoplicKr\Continy\Tests\DummyPlugin\Dummies\IDummy::class;
        $TypeOne     = \ShoplicKr\Continy\Tests\DummyPlugin\Dummies\DummyTypeOne::class;
        $instanceOne = $this->continy->get($Interface);
        $this->assertInstanceOf($Interface, $instanceOne);
        $this->assertInstanceOf($TypeOne, $instanceOne);
        // Test argument injection.
        $this->assertEquals('dummy-interface', $instanceOne->dummyMethod());

        // Explicitly call DummyTypeTwo.
        $TypeTwo     = \ShoplicKr\Continy\Tests\DummyPlugin\Dummies\DummyTypeTwo::class;
        $instanceTwo = $this->continy->get($TypeTwo);
        $this->assertInstanceOf($Interface, $instanceTwo);
        $this->assertInstanceOf($TypeTwo, $instanceTwo);
        // Test argument injection.
        $this->assertEquals('dummy-type-two', $instanceTwo->dummyMethod());
    }

    public function test_get_ReflectionInjection()
    {
        $tester = $this->continy->reflectionInjectionTester;
        $this->assertInstanceOf(
            \ShoplicKr\Continy\Tests\DummyPlugin\ReflectionInjection\ReflectionTester::class,
            $tester,
        );

        // $tester's dependency one in __construct()
        $this->assertInstanceOf(
            \ShoplicKr\Continy\Tests\DummyPlugin\ReflectionInjection\DependencyOne::class,
            $tester->dependencyOne,
        );

        // $tester's dependency one->oneOne in __construct(), chained
        $this->assertInstanceOf(
            \ShoplicKr\Continy\Tests\DummyPlugin\ReflectionInjection\DependencyOneOne::class,
            $tester->dependencyOne->oneOne,
        );

        // $tester's dependency two in __construct()
        $this->assertInstanceOf(
            \ShoplicKr\Continy\Tests\DummyPlugin\ReflectionInjection\DependencyTwo::class,
            $tester->dependencyTwo,
        );

        // $tester's dependency tow->twoOne in __construct(), chained
        // twoOne interface check
        $this->assertInstanceOf(
            \ShoplicKr\Continy\Tests\DummyPlugin\ReflectionInjection\IDependencyTwoOne::class,
            $tester->dependencyTwo->twoOne,
        );
        // twoOne class check
        $this->assertInstanceOf(
            \ShoplicKr\Continy\Tests\DummyPlugin\ReflectionInjection\DependencyTwoOne::class,
            $tester->dependencyTwo->twoOne,
        );
    }
}
