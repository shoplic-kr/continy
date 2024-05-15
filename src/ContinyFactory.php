<?php

declare(strict_types=1);

namespace ShoplicKr\Continy;

class ContinyFactory
{
    /**
     * @throws \ShoplicKr\Continy\ContinyException
     */
    public static function create(array $args): Continy
    {
        $args = wp_parse_args(
            $args,
            [
                'mainFile' => '',
                'setup'    => '',
                'prefix'   => '',
                'version'  => '0.0.0',
            ],
        );

        if (empty($args['mainFile'])) {
            $trace = debug_backtrace();
            $top   = array_shift($trace);
            if ($top) {
                $args['mainFile'] = $top['file'];
            }
        }

        if (empty($args['setup']) && file_exists($args['mainFile'])) {
            $args['setup'] = dirname($args['mainFile']) . '/conf/setup.php';
        }

        return new Continy(
            mainFile: $args['mainFile'],
            prefix:   $args['prefix'],
            setup:    $args['setup'],
            version:  $args['version'],
        );
    }
}
