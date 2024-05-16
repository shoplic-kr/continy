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
    private string $nsPrefix = '';
    private string $version  = '';

    /**
     * Component storage.
     *
     * Usually keys are FQCNs, and values are instances of components.
     *
     * @var array<string, mixed>
     */
    private array $storage = [];

    /**
     * Array of component names that are resolved.
     *
     * Key:   Component name
     * Value: FQCN
     *
     * @var array<string, string>
     */
    private array $resolved = [];

    /**
     * @throws \ShoplicKr\Continy\ContinyException
     */
    public function __construct(array $args = [])
    {
        $defaults = [
            'main_file' => '',
            'version'   => '0.0.0',
            'ns_prefix' => '',
            'modules'   => [],
            // TODO: bindings 구현.
            'bindings'  => [],
        ];

        $args = wp_parse_args($args, $defaults);

        if (empty($args['main_file'])) {
            throw new ContinyException("'main_file' is required");
        }

        if (empty($args['version'])) {
            $args['version'] = $defaults['version'];
        }

        $this->mainFile = $args['main_file'];
        $this->version  = $args['version'];
        $this->nsPrefix = $args['ns_prefix'] ?? '';

        if ( ! str_ends_with($this->nsPrefix, '\\')) {
            $this->nsPrefix .= '\\';
        }

        $this->initialize($args);
    }

    public function __get(string $name)
    {
        return $this->storage[$name] ?? null;
    }

    public function __set(string $name, $value)
    {
        $this->storage[$name] = $value;
    }

    public function __isset(string $name)
    {
        return isset($this->storage[$name]) || isset($this->reserved[$name]);
    }

    /**
     * @param string $id
     *
     * @return mixed
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

    private function bindModule(string|callable $module, string $property, array|callable|null $args): \Closure
    {
        return function () use ($module, $property, $args) {
            if (is_callable($module)) {
                call_user_func_array($module, func_get_args());

                return;
            }

            $split = explode('@', $module, 2);
            $count = count($split);

            try {
                if (1 === $count) {
                    if (is_callable($split[0])) {
                        call_user_func_array($split[0], func_get_args());
                    } else {
                        $this->instantiateModule($split[0], $property, $args);
                    }
                } else {
                    // 2 === $count
                    $callback = [$this->instantiateModule($split[0], $property, $args), $split[1]];
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
     * @param string $moduleName
     *
     * @return string|false FQN of module, or false
     */
    private function getModuleFqcn(string $moduleName): string|false
    {
        $globally = str_replace(['/', '-'], ['\\', '_'], $moduleName);
        $locally  = $this->nsPrefix . $globally;

        if (class_exists($locally)) {
            return $locally;
        }

        if (class_exists($globally)) {
            return $globally;
        }

        return false;
    }

    /**
     * More specific initialization
     *
     * @param array $setup
     *
     * @used-by __construct()
     *
     * @return void
     */
    private function initialize(array $setup): void
    {
        // Manually assign continy itself.
        $this->resolved[__CLASS__] = __CLASS__;
        $this->storage[__CLASS__]  = $this;

        // Module initialization.
        foreach ($setup['modules'] ?? [] as $hook => $modules) {
            $acceptedArgs = (int)($modules['accepted_args'] ?? 1);
            unset($modules['accepted_args']);

            foreach ($modules as $priority => $items) {
                $priority = (int)$priority;

                foreach ($items as $item) {
                    $module   = '';
                    $property = '';
                    $args     = null;

                    if (is_array($item)) {
                        $module   = $item['module'] ?? '';
                        $property = $item['property'] ?? '';
                        $args     = $item['args'] ?? null;
                    } elseif (is_string($item) || is_callable($item)) {
                        $module = $item;
                    }

                    if (is_string($module)) {
                        $module = trim(str_replace('/', '\\', $module), '/\\');
                        if (empty($property)) {
                            $property = str_replace(['/', '\\'], '', lcfirst(str_replace('-', '_', $module)));
                        }
                    }

                    if ($module) {
                        add_action($hook, $this->bindModule($module, $property, $args), $priority, $acceptedArgs);
                    }
                }
            }
        }
    }

    /**
     * Instantiate a fully-qualified class
     *
     * @param string              $component Our module name to look for.
     * @param array|callable|null $args      Module arguments. Modules may use this explicitly.
     *
     * @return mixed
     * @throws \ShoplicKr\Continy\ContinyNotFoundException
     * @throws \ShoplicKr\Continy\ContinyException
     */
    private function instantiate(string $component, array|callable|null $args = null): mixed
    {
        $fqcn = $this->resolve($component);
        if ( ! $fqcn) {
            throw new ContinyNotFoundException("Module '$component' does not exist");
        }

        // Reuse.
        if (isset($this->storage[$fqcn])) {
            return $this->storage[$fqcn];
        }

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
     * @param string              $module Our module name to look for.
     * @param string              $name   Container's dynamic property name.
     * @param array|callable|null $args   Arguments for module.
     *
     * @return mixed
     * @throws \ShoplicKr\Continy\ContinyNotFoundException
     * @throws \ShoplicKr\Continy\ContinyException
     */
    private function instantiateModule(string $module, string $name, array|callable|null $args = null): mixed
    {
        if ( ! isset($this->$name)) {
            $this->$name = $this->instantiate($module, $args);
        }

        return $this->$name;
    }

    /**
     * @param string $component Module name to resolve
     *
     * @return string|false FQN of the module, return false if failed.
     */
    private function resolve(string $component): string|false
    {
        if ( ! isset($this->resolved[$component])) {
            // Make sure that $this->$name is instantiated by now.
            $this->resolved[$component] = $this->getModuleFqcn($component);
        }

        return $this->resolved[$component];
    }
}
