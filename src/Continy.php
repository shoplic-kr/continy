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
    private string $prefix   = '';
    private string $version  = '';

    /**
     * Component storage.
     *
     * Usually keys are FQCNs, and values are instances of components.
     *
     * @var array<string, mixed>
     */
    private $storage = [];

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
            'prefix'    => '',
            'modules'   => [],
        ];

        $args = wp_parse_args($args, $defaults);

        if (empty($args['main_file'])) {
            throw new ContinyException("'main_file' is required");
        }

        if ($args['prefix'] && ! str_ends_with($args['prefix'], '\\')) {
            $this->prefix .= '\\';
        }

        if (empty($args['version'])) {
            $args['version'] = $defaults['version'];
        }

        $this->mainFile = $args['main_file'];
        $this->prefix   = $args['prefix'];
        $this->version  = $args['version'];

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

    private function bindModule(string|callable $module, string $property, array $args): \Closure
    {
        if (empty($property) && is_string($module)) {
            $property = str_replace(['/', '\\'], '', lcfirst(str_replace('-', '_', $module)));
        }

        return function () use ($module, $property, $args) {
            if (is_callable($module)) {
                return $module;
            }

            $split = explode('@', $module, 2);
            $count = count($split);
            $args  = func_get_args();

            try {
                if (1 === $count) {
                    if (is_callable($split[0])) {
                        call_user_func_array($split[0], $args);
                    } else {
                        $this->instantiateModule($split[0], $property, $args);
                    }
                } else {
                    // 2 === $count
                    $callback = [$this->instantiateModule($split[0], $property, $args), $split[1]];
                    if (is_callable($callback)) {
                        call_user_func_array($callback, $args);
                    }
                }
            } catch (ContinyException $e) {
                // Skip the module.
                error_log('ContinyException: ' . $e->getMessage());
            }
        };
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
        // TODO: 2024-05-15 여기 점검을 마지막으로 했음. 여기서부터 코드 진행을 체크해 봐야 함.
        foreach ($setup['modules'] ?? [] as $hook => $items) {
            $acceptedArgs = (int)($items['accepted_args'] ?? 1);
            unset($items['accepted_args']);

            foreach ($items as $priority => $item) {
                $priority = (int)$priority;
                $module   = '';
                $property = '';
                $args     = [];

                if (is_array($item)) {
                    $module   = $item['module'] ?? '';
                    $property = $item['property'] ?? '';
                    $args     = $item['args'] ?? [];
                } elseif (is_string($item) || is_callable($item)) {
                    $module = $item;
                }

                if ($module) {
                    add_action($hook, $this->bindModule($module, $property, $args), $priority, $acceptedArgs);
                }
            }
        }
    }

    /**
     * Instantiate a fully-qualified class
     *
     * @param string         $module        Our module name to look for.
     * @param array|callable $args          Module arguments. Modules may use this explicitly.
     * @param bool           $skipInjection When true, explicitly skip dependency injection step.
     *
     * @return mixed
     * @throws \ShoplicKr\Continy\ContinyNotFoundException
     * @throws \ShoplicKr\Continy\ContinyException
     */
    private function instantiate(string $module, array|callable $args = [], bool $skipInjection = false): mixed
    {
        $fqn = $this->resolve($module);
        if ( ! $fqn) {
            throw new ContinyNotFoundException("Module '$module' does not exist");
        }

        if (empty($args) && ! $skipInjection) {
            try {
                $reflection  = new \ReflectionClass($fqn);
                $constructor = $reflection->getConstructor();
                $parameters  = $constructor->getParameters();
                $args        = [];

                foreach ($parameters as $parameter) {
                    $typeName   = $parameter->getType()->getName();
                    $isNullable = $parameter->allowsNull();

                    if ($parameter->getType()->isBuiltin()) {
                        if ($parameter->isOptional()) {
                            $args[] = $parameter->getDefaultValue();
                        } elseif ($isNullable) {
                            $args[] = null;
                        } else {
                            throw new ContinyException(
                                sprintf(
                                    "Error while injecting '%s' constructor parameter '%s'." .
                                    " Built-in type should have default value or can be nullish," .
                                    " or invoke an explicit injection function.",
                                    $fqn,
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

                    $args[] = $this->get($typeName);
                }
            } catch (\ReflectionException $e) {
                throw new ContinyException($e->getMessage(), $e->getCode(), $e);
            }
        } elseif (is_callable($args)) {
            $args = (array)call_user_func($args, $this);
        }

        // As of PHP 8.0+, unpacking array with string keys are possible.
        return new $fqn(...$args);
    }

    /**
     * @param string         $module Our module name to look for.
     * @param string         $name   Container's dynamic property name.
     * @param array|callable $args   Arguments for module.
     *
     * @return mixed
     * @throws \ShoplicKr\Continy\ContinyNotFoundException
     * @throws \ShoplicKr\Continy\ContinyException
     */
    private function instantiateModule(string $module, string $name, array|callable $args = []): mixed
    {
        if ( ! isset($this->$name)) {
            $this->$name = $this->instantiate($module, $args, skipInjection: true);
        }

        return $this->$name;
    }

    /**
     * Get FQCN (Fully Qualified Class Name) from module name
     *
     * @param string $moduleName
     *
     * @return string|false FQN of module, or false
     */
    private function moduleNameToFqcn(string $moduleName): string|false
    {
        $globally = str_replace(['/', '-'], ['\\', '_'], $moduleName);
        if (class_exists($globally)) {
            return $globally;
        }

        $locally = $this->prefix . $globally;
        if (class_exists($locally)) {
            return $locally;
        }

        return false;
    }

    /**
     * @param string $module Module name to resolve
     *
     * @return string|false FQN of the module, return false if failed.
     */
    private function resolve(string $module): string|false
    {
        if ( ! isset($this->resolved[$module])) {
            // Make sure that $this->$name is instantiated by now.
            $this->resolved[$module] = $this->moduleNameToFqcn($module);
        }

        return $this->resolved[$module];
    }
}
