<?php

declare(strict_types=1);

namespace ShoplicKr\Continy;

/**
 * Continy - A tiny container class for WordPress plugin and theme development that supports really simple D.I.
 *
 * Glossary
 * --------
 * Component: Any instance which Continy can store. Usually continy itself, module instances, and support instances.
 * Module:    A special form of component for initializing plugin or theme.
 *            Instantiated by add_action() function call when Continy is getting started.
 * Support:   Another special form of component. As the name says, it is to assist module components in many ways.
 * Alias:
 */
class Continy implements Container
{
    /* Priority constants */
    public const PR_URGENT    = -10000;
    public const PR_VERY_HIGH = 1;
    public const PR_HIGH      = 5;
    public const PR_DEFAULT   = 10;
    public const PR_LOW       = 50;
    public const PR_VERY_LOW  = 100;
    public const PR_LAZY      = 10000;

    private string $mainFile = '';
    private string $version  = '';

    /**
     * Component storage.
     *
     * Key:   FQCN
     * Value: object
     *
     * @var array<string, mixed>
     */
    private array $storage = [];

    /**
     * Array of component names that are resolved.
     *
     * Key:   FQCN, or alias string
     * Value: FQCN
     *
     * @var array<string, string>
     */
    private array $resolved = [];

    /**
     * Array of arguments
     */
    private array $arguments = [];

    /**
     * @throws \ShoplicKr\Continy\ContinyException
     */
    public function __construct(array $args = [])
    {
        $defaults = [
            'main_file' => '',
            'version'   => '0.0.0',
            'hooks'     => [],
            'bindings'  => [],
            'modules'   => [],
            'arguments' => [],
        ];

        $args = wp_parse_args($args, $defaults);

        if (empty($args['main_file'])) {
            throw new ContinyException("'main_file' is required");
        }

        if (empty($args['version'])) {
            $args['version'] = $defaults['version'];
        }

        $this->mainFile  = $args['main_file'];
        $this->version   = $args['version'];
        $this->arguments = $args['arguments'];

        $this->initialize($args['hooks'], $args['bindings'], $args['modules']);
    }

    public function __get(string $name)
    {
        try {
            return $this->get($name);
        } catch (ContinyException|ContinyNotFoundException $e) {
            return null;
        }
    }

    public function __set(string $name, $value)
    {
        $this->storage[$name] = $value;
    }

    public function __isset(string $name)
    {
        return $this->has($name);
    }

    /**
     * @template T
     * @param class-string<T> $id
     *
     * @return T|object|null
     * @throws \ShoplicKr\Continy\ContinyException
     * @throws \ShoplicKr\Continy\ContinyNotFoundException
     */
    public function get(string $id): mixed
    {
        if (empty($id)) {
            throw new ContinyException("'$id' is required");
        }

        if ( ! $this->has($id)) {
            throw new ContinyNotFoundException(sprintf("The container does not have '%s' item.", $id));
        }

        return $this->instantiate($id);
    }

    public function getMain(): string
    {
        return $this->mainFile;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function has(string $id): bool
    {
        return class_exists($id) || isset($this->resolved[$id]);
    }

    private function bindModule(callable|string $alias): \Closure
    {
        return function () use ($alias) {
            if (is_callable($alias)) {
                call_user_func_array($alias, func_get_args());

                return;
            }

            $split = explode('@', $alias, 2);
            $count = count($split);

            try {
                if (1 === $count) {
                    if (is_callable($split[0])) {
                        call_user_func_array($split[0], func_get_args());
                    } else {
                        $this->instantiate($split[0]);
                    }
                } else {
                    // 2 === $count
                    $callback = [$this->instantiate($split[0]), $split[1]];
                    if (is_callable($callback)) {
                        call_user_func_array($callback, func_get_args());
                    }
                }
            } catch (ContinyException $e) {
                // Skip the module.
                error_log('ContinyException: ' . $e->getMessage());
            }
        };
    }

    /**
     * Get FQCN (Fully Qualified Class Name) from module name
     *
     * @param string $componentOrAlias
     *
     * @return string|false FQN of module, or false
     */
    private function getComponentFqcn(string $componentOrAlias): string|false
    {
        if (class_exists($componentOrAlias)) {
            return $componentOrAlias;
        }

        return false;
    }

    /**
     * More specific initialization
     *
     * @param array $hooks
     * @param array $bindings
     * @param array $modules
     *
     * @used-by __construct()
     *
     * @return void
     */
    private function initialize(array $hooks, array $bindings, array $modules): void
    {
        // Manually assign continy itself.
        $this->resolved['continy'] = __CLASS__;
        $this->resolved[__CLASS__] = __CLASS__;
        $this->storage[__CLASS__]  = $this;

        // Binding initialization.
        foreach ($bindings as $alias => $fqcn) {
            $this->resolved[$alias] = $fqcn;
        }

        // Planned hooks.
        $hooks = wp_parse_args($hooks, ['admin_init' => 0, 'init' => 0]);

        // Module initialization.
        foreach ($modules as $hook => $groups) {
            $numArgs = (int)($hooks[$hook] ?? 1);

            foreach ($groups as $priority => $items) {
                $priority = (int)$priority;

                foreach ($items as $alias) {
                    if (isset($this->resolved[$alias])) {
                        $callback = $this->bindModule($alias);
                        add_action($hook, $callback, $priority, $numArgs);
                    }
                }
            }
        }
    }

    /**
     * Instantiate a fully-qualified class
     *
     * @param string $fqcnOrAlias Our module name to look for.
     *
     * @return mixed
     * @throws \ShoplicKr\Continy\ContinyNotFoundException
     * @throws \ShoplicKr\Continy\ContinyException
     */
    private function instantiate(string $fqcnOrAlias): mixed
    {
        $fqcn = $this->resolve($fqcnOrAlias);
        if ( ! $fqcn) {
            throw new ContinyNotFoundException("Module '$fqcnOrAlias' does not exist");
        }

        // Reuse.
        if (isset($this->storage[$fqcn])) {
            return $this->storage[$fqcn];
        }

        $args = $this->arguments[$fqcn] ?? $this->arguments[$fqcnOrAlias] ?? null;

        $constructorArguments = [];

        if (is_null($args)) {
            try {
                $reflection  = new \ReflectionClass($fqcn);
                $constructor = $reflection->getConstructor();
                $parameters  = $constructor?->getParameters();

                if ($parameters) {
                    foreach ($parameters as $parameter) {
                        $typeName   = $parameter->getType()->getName();
                        $isNullable = $parameter->allowsNull();

                        if ($parameter->getType()->isBuiltin()) {
                            if ($parameter->isOptional()) {
                                $constructorArguments[] = $parameter->getDefaultValue();
                            } elseif ($isNullable) {
                                $constructorArguments[] = null;
                            } else {
                                throw new ContinyException(
                                    sprintf(
                                        "Error while injecting '%s' constructor parameter '%s'." .
                                        " Built-in type should have default value or can be nullish," .
                                        " or invoke an explicit injection function.",
                                        $fqcn,
                                        $parameter->getName(),
                                    ),
                                );
                            }
                            continue;
                        }

                        // Remove heading '?' for nullish parameters.
                        if ($isNullable && str_starts_with($typeName, '?')) {
                            $typeName = substr($typeName, 1);
                        }

                        $constructorArguments[] = $this->get($typeName);
                    }
                }
            } catch (\ReflectionException $e) {
                throw new ContinyException($e->getMessage(), $e->getCode(), $e);
            }
        } elseif (is_callable($args)) {
            $constructorArguments = (array)call_user_func($args, $this);
        } elseif (is_array($args)) {
            $constructorArguments = $args;
        }

        // As of PHP 8.0+, unpacking array with string keys are possible.
        $instance             = new $fqcn(...$constructorArguments);
        $this->storage[$fqcn] = $instance;

        return $instance;
    }

    /**
     * @param string $fqcnOrAlias
     *
     * @return string|false FQCN of the module, return false if failed.
     */
    private function resolve(string $fqcnOrAlias): string|false
    {
        if ( ! isset($this->resolved[$fqcnOrAlias])) {
            $fqcn = $this->getComponentFqcn($fqcnOrAlias);

            // Make sure that $this->$name is instantiated by now.
            $this->resolved[$fqcnOrAlias] = $fqcn;

            if ($fqcn !== $fqcnOrAlias) {
                $this->resolved[$fqcn] = $fqcn;
            }
        }

        return $this->resolved[$fqcnOrAlias];
    }
}
