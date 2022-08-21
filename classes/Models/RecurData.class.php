<?php

namespace Evlist\Models;

class RecurData implements \ArrayAccess, \JsonSerializable
{
    private $properties = array(
        'type' => 0,
        'stop' => '2037-12-31',
        'freq' => 1,
        'listdays' => array(),  // weekdays
        'skip' => 0,
        'weekday' => 0,
        'interval' => 0,
        'custom' => array(),    // array of custom dates
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


    /**
     * Check if this data object matches another.
     *
     * @param   object  $obj2   Object to check against this one
     * @return  boolean     True if matching, False if not
     */
    public function matches(RecurData $obj2) : bool
    {
        return $this->properties == $obj2->toArray();
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
