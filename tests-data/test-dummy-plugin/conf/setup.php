<?php

if ( ! defined('ABSPATH')) {
    exit;
}

use ShoplicKr\Continy\Continy;
use ShoplicKr\Continy\Tests\DummyPlugin;

return [
    'main_file' => dirname(__DIR__) . '/test-dummy-plugin.php',
    'version'   => '1.0.0',

    // Hooks definition
    'hooks'     => [
        'admin_init' => 0,
        'init'       => 0,
    ],

    // Objects binding
    'bindings'  => [
        'modCPT'                    => DummyPlugin\Modules\CPT::class,
        'reflectionInjectionTester' => DummyPlugin\ReflectionInjection\ReflectionTester::class,

        // IDummy implementation
        DummyPlugin\Dummies\IDummy::class
                                    => DummyPlugin\Dummies\DummyTypeOne::class,

        DummyPlugin\ReflectionInjection\IDependencyTwoOne::class
        => DummyPlugin\ReflectionInjection\DependencyTwoOne::class,
    ],

    // Modules setting
    'modules'   => [
        'init' => [
            Continy::PR_DEFAULT => [
                'modCPT',
                function () {},
            ],
        ],
    ],

    // Argument injection
    'arguments' => [
        DummyPlugin\Dummies\IDummy::class       => [
            'dummy' => 'interface',
        ],
        DummyPlugin\Dummies\DummyTypeTwo::class => function () {
            return [
                'dummy' => 'type-two',
            ];
        },
    ],
];
