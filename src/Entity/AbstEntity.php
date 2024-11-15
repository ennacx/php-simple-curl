<?php
declare(strict_types=1);

namespace Ennacx\SimpleCurl\Entity;

use Ennacx\SimpleCurl\Static\Utils;
use InvalidArgumentException;

abstract class AbstEntity {

    protected static array $_accessors = [];

    protected array $dynamicProps = [];

    public function &__get(string $name): mixed {
        return $this->get($name);
    }

    public function __set(string $name, $value): void {
        $this->set($name, $value);
    }

    public function __isset(string $name): bool {
        return ($this->get($name) !== null);
    }

    public function __unset(string $field): void {
        $this->unset($field);
    }

    protected function &get(string $name): mixed {

        if($name === ''){
            throw new InvalidArgumentException('Cannot get an empty name');
        }

        $value = null;
        if(array_key_exists($name, $this->dynamicProps)){
            $value = &$this->dynamicProps[$name];
        }

        $method = static::_accessor($name, 'get');
        if($method){
            // 参照渡しなので一旦変数に
            $result = $this->{$method}($value);

            return $result;
        }

        return $value;
    }

    protected function set(string $name, mixed $value = null): void {

        if($name === ''){
            throw new InvalidArgumentException('Cannot set an empty name');
        }

        $setter = static::_accessor($name, 'set');
        if($setter){
            $value = $this->{$setter}($value);
        }

        $this->dynamicProps[$name] = $value;
    }

    protected function unset(string $name): void {

        if(array_key_exists($name, $this->dynamicProps)){
            unset($this->dynamicProps[$name]);
        }
    }

    protected static function _accessor(string $prop, string $type): string {

        $class = static::class;

        if(isset(static::$_accessors[$class][$type][$prop])){
            return static::$_accessors[$class][$type][$prop];
        }

        if(!empty(static::$_accessors[$class])){
            return static::$_accessors[$class][$type][$prop] = '';
        }

        foreach(get_class_methods($class) as $method){
            $prefix = substr($method, 1, 3);
            if($method[0] !== '_' || !preg_match('/^[g|s]et$/', $prefix)){
                continue;
            }

            $lcName    = lcfirst(substr($method, 4));
            $snakeName = Utils::snakize($lcName);
            $ucName    = ucfirst($lcName);
            static::$_accessors[$class][$prefix][$lcName]    = $method;
            static::$_accessors[$class][$prefix][$snakeName] = $method;
            static::$_accessors[$class][$prefix][$ucName]    = $method;
        }

        if(!isset(static::$_accessors[$class][$type][$prop])){
            static::$_accessors[$class][$type][$prop] = '';
        }

        return static::$_accessors[$class][$type][$prop];
    }
}