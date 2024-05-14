<?php
/**
 * Plugin Name: Test Dummy plugin.
 * Description: Our test dummy plugin activated while unit test bootstrap
 */

namespace ShoplicKr\Continy\Tests\DummyPlugin;

if ( ! defined('ABSPATH')) {
    exit;
}

function getTestDummyPlugin(): \ShoplicKr\Continy\Continy
{
    static $continy = null;

    if (is_null($continy)) {
        try {
            $continy = \ShoplicKr\Continy\ContinyFactory::create(__DIR__ . '/conf/setup.php');
        } catch (\ShoplicKr\Continy\ContinyException $e) {
            die($e->getMessage());
        }
    }

    return $continy;
}

getTestDummyPlugin();
