<?php

namespace ShoplicKr\Continy {

    function bootstrap(Array|Config $config): void
    {
        // Get the main file.
        $backtrace = debug_backtrace();
        $top       = array_shift($backtrace);
        $main      = $top['file'];

        // Get the version.
        $version = '1.0.0';

        // Continy
    }
}
