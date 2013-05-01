<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  net\stubbles\webapp
 */
namespace net\stubbles\webapp\session;
/**
 * Interface for sessions.
 */
interface Session
{
    /**
     * key to be associated with the fingerprint of the user
     */
    const FINGERPRINT = '__stubbles_SessionFingerprint';

    /**
     * checks whether session has been started
     *
     * Typically, a session is new on the first request of a user,
     * afterwards it should never be new.
     *
     * @return  bool  true if session has been started, else false
     */
    public function isNew();

    /**
     * returns session id
     *
     * @return  string  the session id
     */
    public function getId();

    /**
     * regenerates the session id but leaves session data
     *
     * @return  Session
     */
    public function regenerateId();

    /**
     * returns the name of the session
     *
     * @return  string
     */
    public function getName();

    /**
     * checks if this session is valid
     *
     * @return  bool
     */
    public function isValid();

    /**
     * invalidates current session and creates a new one
     *
     * @return  Session
     */
    public function invalidate();

    /**
     * checks whether a value associated with key exists
     *
     * @param   string  $key  key where value is stored under
     * @return  bool
     */
    public function hasValue($key);

    /**
     * returns a value associated with the key or the default value
     *
     * @param   string  $key      key where value is stored under
     * @param   mixed   $default  optional  return this if no data is associated with $key
     * @return  mixed
     */
    public function getValue($key, $default = null);

    /**
     * stores a value associated with the key
     *
     * @param   string  $key    key to store value under
     * @param   mixed   $value  data to store
     * @return  Session
     */
    public function putValue($key, $value);

    /**
     * removes a value from the session
     *
     * @param   string  $name  key where value is stored under
     * @return  bool    true if value existed and was removed, else false
     */
    public function removeValue($name);

    /**
     * return an array of all keys registered in this session
     *
     * @return  string[]
     */
    public function getValueKeys();
}
?>