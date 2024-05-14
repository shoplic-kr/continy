<?php

namespace ShoplicKr\Continy {

    function bootstrap(array|string|Config $configuration = []): Continy
    {
        return Pool::add(ContinyFactory::create($configuration));
    }

    function retrieve(string $key): Continy|null
    {
        return Pool::get($key);
    }
}
