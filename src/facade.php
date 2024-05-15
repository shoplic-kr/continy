<?php

namespace ShoplicKr\Continy {

    function bootstrap(string $slug, array $configuration = []): Continy
    {
        return Pool::add($slug, ContinyFactory::create($configuration));
    }

    function retrieve(string $key): Continy|null
    {
        return Pool::get($key);
    }
}
