<?php

namespace ShoplicKr\Continy;

class Config
{
    private string $main;
    private string $version;

    public function __construct(array $config = [])
    {
        $this->main    = $config['main'] ?? '';
        $this->version = $config['version'] ?? '';
    }

    public function getMain(): string {
        return $this->main;
    }

    public function getVersion(): string {
        return $this->version;
    }
}
