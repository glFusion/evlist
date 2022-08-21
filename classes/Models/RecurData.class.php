<?php

namespace Evlist\Models;

class RecurData implements \ArrayAccess, \JsonSerializable
{
    private $properties = array(
        'type' => 0,
        'stop' => '2037-12-31',
        'freq' => 1,
        'listdays' => array(),
        'skip' => 0,
        'weekday' => 0,
        'interval' => 0,
        'custom' => array(),
    );

    public function __construct(?array $A=NULL)
    {
        if (is_array($A)) {
            $this->properties = array_merge($this->properties, $A);
        }
    }


    public function jsonSerialize()
    {
        return $this->properties;
    }

    public function serialize()
    {
        return @serialize($this->properties);
    }

    public function unserialize($str)
    {
        $this->properties = @unserialize($str);
    }

    public function setType($type)
    {
        $this->properties['type'] = (int)$type;
        return $this;
    }

    public function toArray()
    {
        return $this->properties;
    }

    public function matches($obj2)
    {
        if (array_diff_assoc($this->properties, $obj2->toArray)) {
            return false;
        } else {
            foreach ($this->properties as $key->$v1) {
                if ($obj2[$key] != $v1) {
                    return false;
                }
            }
        }
        return true;
    }

    private function _matchRecursive($obj1, $obj2)
    {
        foreach ($obj1->toArray() as $key=>$v1) {
            if (is_array($v1)) {
            }
        }
    }


    public function offsetSet($key, $value)
    {
        $this->properties[$key] = $value;
    }

    public function offsetExists($key)
    {
        return isset($this->properties[$key]);
    }

    public function offsetUnset($key)
    {
        unset($this->properties[$key]);
    }

    public function offsetGet($key)
    {
        return isset($this->properties[$key]) ? $this->properties[$key] : null;
    }

}
