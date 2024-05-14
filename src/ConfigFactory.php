<?php

namespace ShoplicKr\Continy;

class ConfigFactory
{
    public static function parse(array|string|Config $config): Config
    {
        if ($config instanceof Config) {
            return $config;
        }

        $config = wp_parse_args(
            $config,
            [
                'mainFile' => '',
                'slug'     => '',
                'version'  => '0.0.0',
            ],
        );

        return new Config(
            mainFile: $config['mainFile'],
            slug:     $config['slug'],
            version:  $config['version'],
        );
    }
}
