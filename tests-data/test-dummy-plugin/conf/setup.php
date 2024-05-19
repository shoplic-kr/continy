<?php

if ( ! defined('ABSPATH')) {
    exit;
}

use ShoplicKr\Continy\Continy;

return [
    'main_file' => dirname(__DIR__) . '/test-dummy-plugin.php',
    'version'   => '1.0.0',

    'bindings' => [
        'modCPT' => ShoplicKr\Continy\Tests\DummyPlugin\Modules\CPT::class,
    ],
    'hooks'    => [
        'admin_init' => 0,
        'init'       => 0,
    ],
    'modules'  => [
        'init' => [
            Continy::PR_DEFAULT => [
                'modCPT',
            ],
        ],
    ],
];
