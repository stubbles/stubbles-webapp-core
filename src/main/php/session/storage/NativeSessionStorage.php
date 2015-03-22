<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  stubbles\webapp
 */
namespace stubbles\webapp\session\storage;
use stubbles\webapp\session\id\SessionId;
/**
 * Session storage using default PHP sessions.
 *
 * This session storage offers session handling based on the default PHP session
 * functions.
 *
 * @since  2.0.0
 */
class NativeSessionStorage implements SessionStorage, SessionId
{
    /**
     * name of session
     *
     * @type  string
     */
    private $sessionName;
    /**
     * switch whether storage is already initialized or not
     *
     * @type  bool
     */
    private $initialized = false;

    /**
     * constructor
     *
     * @param  string  $sessionName  name of the session
     */
    public function __construct($sessionName)
    {
        $this->sessionName = $sessionName;
        session_name($this->sessionName);
    }

    /**
     * initialize storage
     */
    private function init()
    {
        if ($this->initialized || session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        @session_start();
        $this->initialized = true;
    }

    /**
     * returns session name
     *
     * @return  string
     */
    public function name()
    {
        return $this->sessionName;
    }

    /**
     * returns session id
     *
     * @return  string  the session id
     */
    public function __toString()
    {
        $this->init();
        return session_id();
    }

    /**
     * regenerates the session id but leaves session data
     *
     * @return  \stubbles\webapp\session\storage\SessionStorage
     */
    public function regenerate()
    {
        $this->init();
        @session_regenerate_id(true);
        return $this;
    }

    /**
     * invalidates current session
     *
     * @return  \stubbles\webapp\session\storage\SessionStorage
     */
    public function invalidate()
    {
        if ($this->initialized && session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }

        return $this->regenerate();
    }

    /**
     * removes all data from storage
     *
     * @return  \stubbles\webapp\session\storage\SessionStorage
     */
    public function clear()
    {
        $_SESSION = [];
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
        $this->init();
        return isset($_SESSION[$key]);
    }

    /**
     * returns a value associated with the key or the default value
     *
     * @param   string  $key  key where value is stored under
     * @return  mixed
     */
    public function value($key)
    {
        $this->init();
        if (isset($_SESSION[$key])) {
            return $_SESSION[$key];
        }

        return null;
    }

    /**
     * stores a value associated with the key
     *
     * @param   string  $key    key to store value under
     * @param   mixed   $value  data to store
     * @return  \stubbles\webapp\session\storage\SessionStorage
     */
    public function putValue($key, $value)
    {
        $this->init();
        $_SESSION[$key] = $value;
        return $this;
    }

    /**
     * removes a value from the session
     *
     * @param   string  $key  key where value is stored under
     * @return  \stubbles\webapp\session\storage\SessionStorage
     */
    public function removeValue($key)
    {
        $this->init();
        unset($_SESSION[$key]);
        return $this;
    }

    /**
     * return an array of all keys registered in this session
     *
     * @return  string[]
     */
    public function valueKeys()
    {
        $this->init();
        return array_keys($_SESSION);
    }
}
