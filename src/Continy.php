<?php

namespace ShoplicKr\Continy;

class Continy implements Container
{
    private Config $config;

    public function __construct(array|string|Config $config)
    {
        $this->config = ConfigFactory::parse($config);
    }

    /**
     * @param string $id
     *
     * @return mixed
     * @throws \ShoplicKr\Continy\ContinyException
     * @throws \ShoplicKr\Continy\ContinyNotFoundException
     */
    public function get(string $id)
    {
        // TODO: Implement get() method.
        if ( ! $this->has($id)) {
            throw new ContinyNotFoundException(
                sprintf("Continy '%s' does not have '%s' item.", $this->getKey(), $id),
            );
        }

        // class?
        if (class_exists($id)) {
            // instantiate the class and return.
        }

        // alias?
        // bound?
    }

    public function getKey(): string
    {
        return $this->config->getSlug();
    }

    public function getMain(): string
    {
        return $this->config->getMain();
    }

    public function getVersion(): string
    {
        return $this->config->getVersion();
    }

    public function has(string $id): bool
    {
        // TODO: Implement has() method.

        if (class_exists($id)) {
            return true;
        }

        // string, or interface binding found?
        // alias found?

        return false;
    }
}
