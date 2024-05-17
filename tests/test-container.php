<?php

use ShoplicKr\Continy\Continy;
use ShoplicKr\Continy\ContinyFactory;
use ShoplicKr\ContinySample\Modules\SubModules\ReuseTester;

/**
 * Class ContainerTest
 */
class ContainerTest extends WP_UnitTestCase
{
    protected static string $pluginRoot;
    protected static string $pluginSetup;

    protected Continy $continy;

    public static function setUpBeforeClass(): void
    {
        self::$pluginRoot  = dirname(__FILE__, 2);
        self::$pluginSetup = self::$pluginRoot . '/conf/setup.php';
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->continy = ContinyFactory::create(self::$pluginSetup);
    }

    public function test_getMain()
    {
        $this->assertEquals($this->continy->getMain(), self::$pluginRoot . '/continy-sample.php');
    }

    public function test_getVersion()
    {
        $array   = include self::$pluginSetup;
        $version = $array['version'] ?? false;

        $this->assertEquals($this->continy->getVersion(), $version);
    }

    public function test_get_ReuseTester()
    {
        // Make sure the instance is created.
        $count = ReuseTester::$constructCount;
        $this->assertInstanceOf(ReuseTester::class, $this->continy->get(ReuseTester::class));
        $this->assertEquals($count + 1, ReuseTester::$constructCount);

        // The instance should be re-used. Thus, the count is unchanged.
        $this->continy->get(ReuseTester::class);
        $this->assertEquals($count + 1, ReuseTester::$constructCount);

        // Manual construction should increase the count.
        new ReuseTester();
        $this->assertEquals($count + 2, ReuseTester::$constructCount);
    }
}
