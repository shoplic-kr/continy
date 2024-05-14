<?php

namespace ShoplicKr\Continy;

class Config
{
    public function __construct(private string $mainFile, private string $slug, private string $version)
    {
    }

    public function getMain(): string
    {
        return $this->mainFile;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function getVersion(): string
    {
        return $this->version;
    }
}
