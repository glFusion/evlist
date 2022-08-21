<?php

namespace Evlist\Models;

class EventOptions implements \ArrayAccess, \JsonSerializable
{
    private $properties = array(
        'use_rsvp'   => 0,
        'max_rsvp'   => 0,
        'rsvp_cutoff' => 0,
        'rsvp_waitlist' => 0,
        'ticket_types' => array(),
        'tickets' => array(),
        'contactlink' => '',
        'max_user_rsvp' => 1,
        'rsvp_comments' => 0,
        'rsvp_signup_grp' => 1,
        'rsvp_view_grp' => 1,
        'rsvp_cmt_prompts' => array(),
    );

    public function __construct(?array $A=NULL)
    {
        if (is_array($A)) {
            $this->properties = array_merge($this->properties, $A);
        }
    }


    public function setTicket(int $tick_id, array $tick_data) : void
    {
        $this->properties['tickets'][$tick_id] = $tick_data;
    }


    public function jsonSerialize()
    {
        return $this->properties;
    }

    public function toArray()
    {
        return $this->properties;
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
