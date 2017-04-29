<?php
/**
 * @author      Jiří Mihal <jiri@mihal.me>
 * @copyright   2017 Jiří Mihal
 * @link        https://github.com/webiik/webiik
 * @license     MIT
 */
namespace Webiik;
use \Pimple\Container;

/**
 * Class Container - Provides functions to work with Pimple\Container
 * @package Webiik
 */
class WContainer
{
    /**
     * @var Container
     */
    protected $c;

    public function __construct(\Pimple\Container $c)
    {
        $this->c = $c;
    }

    /**
     * Add Pimple service
     * @param string $name
     * @param callable $factory
     */
    public function addService($name, $factory)
    {
        $this->c[$name] = $factory;
    }

    /**
     * Add Pimple service factory
     * @param string $name
     * @param callable $factory
     */
    public function addServiceFactory($name, $factory)
    {
        $this->c[$name] = $this->c->factory($factory);
    }

    /**
     * Add value into Pimple container
     * @param string $name
     * @param mixed $val
     */
    public function addParam($name, $val)
    {
        $this->c[$name] = $val;
    }

    /**
     * Add function into Pimple container
     * @param string $name
     * @param callable $function
     */
    public function addFunction($name, $function)
    {
        $this->c[$name] = $this->c->protect($function);
    }

    /**
     * Get value from Pimple
     * @param string $name
     * @param bool $isset
     * @return mixed
     */
    public function get($name, $isset = false)
    {
        if ($isset) {
            return isset($this->c[$name]) ? $this->c[$name] : null;
        } else {
            return $this->c[$name];
        }
    }

    /**
     * Inject dependencies from Container to $object using @inject doc comment
     * @param $object
     * @param WContainer $container
     */
    public static function DIcomment($object, WContainer $container)
    {
        $reflection = new \ReflectionClass(get_class($object));
        $properties = $reflection->getProperties();

        foreach ($properties as $property) {
            $pn = $property->name;
            preg_match('/(\$?(\w*))\s@inject/', $reflection->getProperty($property->name)->getDocComment(), $match);
            if (isset($match[1])) {
                if ($match[1][0] == '$') {
                    // Property isn't class
                    $object->$pn = $container[$match[2]];
                } else {
                    // Property is class
                    $object->$pn = $container[$reflection->getNamespaceName() . '\\' . $match[1]];
                }
            }
        }
    }

    /**
     * Inject dependencies from Container to $object using object constructor method
     * @param $className
     * @param WContainer $container
     * @return array
     */
    public static function DIconstructor($className, WContainer $container)
    {
        return self::prepareMethodParameters($className, '__construct', $container);
    }

    /**
     * Inject dependencies from Container to $object using object methods with inject prefix
     * @param $object
     * @param WContainer $container
     */
    public static function DImethod($object, WContainer $container)
    {
        $methods = get_class_methods($object);
        foreach ($methods as $method) {
            preg_match('/^inject([A-Z]\w*)$/', $method, $match);
            if (isset($match[0]) && isset($match[1])) {
                $object->$method(...self::prepareMethodParameters($object, $method, $container));
            }
        }
    }

    /**
     * Return array with prepared parameters for give class and method
     * @param $className
     * @param $methodName
     * @param WContainer $container
     * @return array
     */
    private static function prepareMethodParameters($className, $methodName, WContainer $container)
    {
        $p = [];

        if (method_exists($className, $methodName)) {
            $reflection = new \ReflectionMethod($className, $methodName);
            $params = $reflection->getParameters();
            foreach ($params as $param) {

                $class = $param->getClass();

                if ($class) {
                    // parameter is class
                    $itemName = $param->getClass()->getName();
                } else {
                    // parameter isn't class
                    $itemName = $param->getName();
                }

                if ($param->isOptional()) {
                    if ($container->get($itemName, true)) {
                        $p[] = $container->get($itemName);
                    }
                } else {
                    $p[] = $container->get($itemName);
                }
            }
        }

        return $p;
    }
}