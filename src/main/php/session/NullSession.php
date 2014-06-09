<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  stubbles\webapp
 */
namespace stubbles\webapp\session;
/**
 * Null session for usages in non-sessionbased web applications.
 *
 * @since  2.0.0
 */
class NullSession implements Session
{
    /**
     * if of the session
     *
     * @type  SessionId
     */
    private $id;

    /**
     * constructor
     *
     * @param  SessionId  $id
     */
    public function __construct(SessionId $id)
    {
        $this->id = $id;
    }

    /**
     * checks whether session has been started
     *
     * Typically, a session is new on the first request of a user,
     * afterwards it should never be new.
     *
     * @return  bool  true if session has been started, else false
     */
    public function isNew()
    {
        return true;
    }

    /**
     * returns session id
     *
     * @return  string  the session id
     */
    public function getId()
    {
        return $this->id->get();
    }

    /**
     * regenerates the session id but leaves session data
     *
     * @return  Session
     */
    public function regenerateId()
    {
        $this->id->regenerate();
        return $this;
    }

    /**
     * returns the name of the session
     *
     * @return  string
     */
    public function getName()
    {
        return $this->id->getName();
    }

    /**
     * checks if this session is valid
     *
     * @return  bool
     */
    public function isValid()
    {
        return true;
    }

    /**
     * invalidates current session and creates a new one
     *
     * @return  Session
     */
    public function invalidate()
    {
        $this->id->invalidate();
        return $this;
    }

    /**
     * checks whether a value associated with key exists
     *
     * @param   string  $key  key where value is stored under
     * @return  bool
     */
    public function hasValue($key)
    {
        return false;
    }

    /**
     * returns a value associated with the key or the default value
     *
     * @param   string  $key      key where value is stored under
     * @param   mixed   $default  optional  return this if no data is associated with $key
     * @return  mixed
     */
    public function getValue($key, $default = null)
    {
        return $default;
    }

    /**
     * stores a value associated with the key
     *
     * @param   string  $key    key to store value under
     * @param   mixed   $value  data to store
     * @return  Session
     */
    public function putValue($key, $value)
    {
        return $this;
    }

    /**
     * removes a value from the session
     *
     * @param   string  $key  key where value is stored under
     * @return  bool    true if value existed and was removed, else false
     */
    public function removeValue($key)
    {
        return false;
    }

    /**
     * return an array of all keys registered in this session
     *
     * @return  string[]
     */
    public function getValueKeys()
    {
        return [];
    }
}
