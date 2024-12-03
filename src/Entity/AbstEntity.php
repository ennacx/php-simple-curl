<?php
declare(strict_types=1);

namespace Ennacx\SimpleCurl\Entity;

use Ennacx\SimpleCurl\Static\Utils;
use InvalidArgumentException;

abstract class AbstEntity {

    /** @var array ダイナミックプロパティー */
    protected array $dynamicProps = [];

    /** @var array エンティティーに用意されている仮想プロパティ値取得メソッドをまとめたアクセサー */
    private static array $_accessors = [];

    /**
     * 未指定プロパティの取得
     *
     * @param  string $name
     * @return mixed
     * @throws InvalidArgumentException
     */
    public function &__get(string $name): mixed {
        return $this->get($name);
    }

    /**
     * 未指定プロパティへのセット
     *
     * @param  string     $name
     * @param  mixed|null $value
     * @return void
     * @throws InvalidArgumentException
     */
    public function __set(string $name, mixed $value): void {
        $this->set($name, $value);
    }

    /**
     * 未指定プロパティの存在確認
     *
     * @param  string  $name
     * @return boolean
     * @throws InvalidArgumentException
     */
    public function __isset(string $name): bool {
        return ($this->get($name) !== null);
    }

    /**
     * 未指定プロパティのアンセット
     *
     * @param  string $field
     * @return void
     */
    public function __unset(string $field): void {
        $this->unset($field);
    }

    /**
     * 未指定プロパティの取得処理の実体
     *
     * @param  string $name
     * @return mixed
     * @throws InvalidArgumentException
     */
    protected function &get(string $name): mixed {

        if($name === '')
            throw new InvalidArgumentException('Cannot get an empty name');

        $value = null;
        if(array_key_exists($name, $this->dynamicProps))
            $value = &$this->dynamicProps[$name];

        $method = static::_accessor($name, 'get');
        if($method)
            $value = $this->{$method}();

        return $value;
    }

    /**
     * 未指定プロパティへのセット処理の実体
     *
     * @param  string     $name
     * @param  mixed|null $value
     * @return void
     * @throws InvalidArgumentException
     */
    protected function set(string $name, mixed $value = null): void {

        if($name === '')
            throw new InvalidArgumentException('Cannot set an empty name');

        $setter = static::_accessor($name, 'set');
        if($setter)
            $value = $this->{$setter}($value);

        $this->dynamicProps[$name] = $value;
    }

    /**
     * 未指定プロパティのアンセット処理の実体
     *
     * @param  string $name
     * @return void
     */
    protected function unset(string $name): void {

        if(array_key_exists($name, $this->dynamicProps))
            unset($this->dynamicProps[$name]);
    }

    /**
     * アクセサーからの取得またはセット
     *
     * @param  string $prop
     * @param  string $type
     * @return string
     */
    private static function _accessor(string $prop, string $type): string {

        $class = static::class;

        if(isset(static::$_accessors[$class][$type][$prop]))
            return static::$_accessors[$class][$type][$prop];

        if(!empty(static::$_accessors[$class]))
            return static::$_accessors[$class][$type][$prop] = '';

        foreach(get_class_methods($class) as $method){
            $prefix = substr($method, 1, 3);
            if($method[0] !== '_' || !preg_match('/^[g|s]et$/', $prefix))
                continue;

            $lcName    = lcfirst(substr($method, 4));
            $snakeName = Utils::snakize($lcName);
            $ucName    = ucfirst($lcName);
            static::$_accessors[$class][$prefix][$lcName]    = $method;
            static::$_accessors[$class][$prefix][$snakeName] = $method;
            static::$_accessors[$class][$prefix][$ucName]    = $method;
        }

        if(!isset(static::$_accessors[$class][$type][$prop]))
            static::$_accessors[$class][$type][$prop] = '';

        return static::$_accessors[$class][$type][$prop];
    }
}