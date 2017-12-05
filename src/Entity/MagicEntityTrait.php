<?php 

namespace JochLAin\Database\Entity;

use Doctrine\Common\Inflector\Inflector;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

trait MagicEntityTrait
{
    public function __construct() { $this->_construct(); }
    public function __call(string $method, $parameters) { return $this->_call($method, $parameters); }
    public function __isset(string $name) { return $this->_isset($name); }
    public function __get(string $name) { return $this->_get($name); }
    public function __set(string $name, $value) { return $this->_set($name, $value); }
    public function __unset(string $name) { return $this->_unset($name); }
    public function __clone() { return $this->_clone(); }
    public function __invoke() { return $this->_invoke(); }

    private function _isset(string $name) { 
        $plural = Inflector::pluralize($name);
        $properties = $this->_getProperties($reflection);
        return in_array($singular, $properties) || in_array($plural, $properties);
    }

    private function _get(string $name) { 
        return $this->_call('get'.ucfirst($name)); 
    }

    private function _set(string $name, $value) {
        $classname = get_class($this);
        $reflection = new \ReflectionClass($classname);
        $singular = Inflector::singularize($name);
        $plural = Inflector::pluralize($name);
        $properties = $this->_getProperties($reflection);

        if (in_array($plural, $properties) && $this->$plural instanceof Collection) {
            if ($name == $plural) {
                return $this->_call('set'.ucfirst($plural), $value);
            } else if ($name == $singular) {
                return $this->_call('add'.ucfirst($singular), $value);
            }
        } else if (in_array($singular, $properties) && $name == $singular) {
            $this->_call('set'.$singular, $value);
        }
    }

    private function _unset(string $name) {
        $classname = get_class($this);
        $reflection = new \ReflectionClass($classname);
        $singular = Inflector::singularize($name);
        $plural = Inflector::pluralize($name);
        $properties = $this->_getProperties($reflection);

        if (in_array($plural, $properties) && $this->$plural instanceof Collection) {
            if ($name == $plural) {
                $this->$plural = new ArrayCollection;
            }
        } else if (in_array($singular, $properties) && $name == $singular) {
            $this->$singular = null;
        }
    }

    private function _construct() {
        $classname = get_class($this);
        $reflection = new \ReflectionClass($classname);
        $properties = $this->_getProperties($reflection);

        foreach ($properties as $property) {
            if (is_null($this->$property)) {
                $singular = Inflector::singularize($property);
                $plural = Inflector::pluralize($property);

                if ($property == $plural) {
                    $this->$property = new ArrayCollection;
                }
            }
        }
    }

    private function _call($method, $parameters = null) {
        $action = false;
        foreach (['get', 'set', 'add', 'remove', 'has', 'is'] as $available) {
            if (strpos($method, $available) === 0) {
                $property = lcfirst(substr($method, strlen($available)));
                $action = substr($method, 0, strlen($available));
                break;
            }
        }
        $classname = get_class($this);
        if (!$action) return self::throwUndefinedMethodCall($method, $classname);

        $reflection = new \ReflectionClass($classname);
        $singular = Inflector::singularize($property);
        $plural = Inflector::pluralize($property);
        $properties = $this->_getProperties($reflection);

        if (!in_array($singular, $properties) && !in_array($plural, $properties)) return self::throwUndefinedMethodCall($method, $classname);
        switch ($action) {
            case 'get': 
                if (in_array($singular, $properties)) return $this->$singular;
                else if (in_array($plural, $properties)) return $this->$plural;
            case 'set':
                if (in_array($plural, $properties) && $this->$plural instanceof Collection) {
                    foreach ($parameters[0] as $parameter) {
                        $adder = 'add'.ucfirst($singular);
                        $this->$adder($parameter);
                    }
                    return $this;
                } else if ($parameters instanceof \DateTime) {
                    $this->$property = $parameters;
                    return $this;
                } else if ($property != 'id') {
                    $this->$property = $parameters[0];
                    return $this;
                }
                break;
            case 'add':
                if ($property != $singular || !in_array($plural, $properties)) return;
                if ($this->$plural instanceof Collection) {
                    $entity = $parameters[0];
                    if (!$this->$plural->contains($entity)) $this->$plural->add($entity);

                    if (is_object($entity)) {
                        $_classname = explode('\\', $classname);
                        $_property = lcfirst($_classname[count($_classname) - 1]);
                        $_singular = Inflector::singularize($_property);
                        $_plural = Inflector::pluralize($_property);
                        $_reflection = new \ReflectionClass(get_class($entity));
                        $_properties = $this->_getProperties($_reflection);
                        if (in_array($_singular, $_properties)) {
                            $setter = 'set'.ucfirst($_singular);
                            $getter = 'get'.ucfirst($_singular);
                            if ($entity && !$entity->$getter($this)) $entity->$setter($this);
                        } else if (in_array($_plural, $_properties)) {
                            $haser = 'has'.ucfirst($_singular);
                            $adder = 'add'.ucfirst($_singular);
                            if ($entity && !$entity->$haser($this)) $entity->$adder($this);
                        }
                    }
                    return $this;
                } else if (is_array($this->$plural)) {
                    $this->$plural[] = $parameters[0];
                    return $this;
                }
                break;
            case 'remove':
                if ($property != $singular || !in_array($plural, $properties)) return;
                if ($this->$plural instanceof Collection) {
                    $this->$plural->removeElement($parameters[0]);
                    return $this;
                } else if (is_array($this->$plural)) {
                    if (in_array($parameters[0], $this->$plural)) {
                        array_splice($this->$plural, array_search($this->$plural, $parameters[0]), 1);
                    }
                    return $this;
                }
                break;
            case 'has':
                if ($property != $singular || !in_array($plural, $properties)) return;
                if ($this->$plural instanceof Collection) {
                    return $this->$plural->contains($parameters[0]);
                } else if (is_array($this->$plural)) {
                    return in_array($parameters[0], $this->$plural);
                }
                break;
            case 'is': return (bool) $this->$property;
        }

        if ($parent = $reflection->getParentClass()) {
            $reflection = new \ReflectionClass($parent);
            if ($reflection->hasMethod('__call')) {
                if ($reflection->getMethod('__call')->isPublic()) {
                    return parent::__call($method, $parameters);
                }
            }
        }
        return self::throwUndefinedMethodCall($method, $classname);
    }

    private function _clone() {
        $classname = get_class($this);
        $reflection = new \ReflectionClass($classname);
        $properties = $this->_getProperties($reflection);

        $clone = new $classname();
        foreach ($properties as $property) {
            if ($property == 'id') continue;
            $clone->_call('set'.ucfirst($property), $this->_call('get'.ucfirst($property)));
        }
        return $clone;
    }

    private function _invoke() {
        $clone = clone($this);
        $clone->setId($this->id);
        return $clone;
    }

    private function _getProperties(\ReflectionClass $reflection) {
        $props = array_map(function ($property) {
            return $property->getName();
        }, array_filter($reflection->getProperties(), function ($property) {
            return !$property->isStatic();
        }));

        if ($parent = $reflection->getParentClass()) {
            $props = array_merge($props, $this->_getProperties($parent));
        }
        return $props;
    }

    public static function throwUndefinedMethodCall($method, $classname) {
        throw new \Exception(sprintf('Method "%s" in class "%s" doesn\'t exists', $method, $classname));
    }
}