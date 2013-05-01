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
 * Session id which is always created new.
 *
 * @since  2.0.0
 */
class NoneDurableSessionId implements SessionId
{
    /**
     * actual id
     *
     * @type  string
     */
    private $id;
    /**
     * name of session
     *
     * @type  string
     */
    private $sessionName;

    /**
     * constructor
     *
     * @param  string  $sessionName  name of the session
     */
    public function __construct($sessionName)
    {
        $this->sessionName = $sessionName;
    }

    /**
     * returns session name
     *
     * @return  string
     */
    public function getName()
    {
        return $this->sessionName;
    }

    /**
     * reads session id
     *
     * @return  string
     */
    public function get()
    {
        if (null === $this->id) {
            $this->id = $this->create();
        }

        return $this->id;
    }

    /**
     * creates session id
     *
     * @return  string
     */
    private function create()
    {
        return md5(uniqid(rand(), true));
    }

    /**
     * stores session id for given session name
     *
     * @return  SessionId
     */
    public function regenerate()
    {
        $this->id = $this->create();
        return $this;
    }

    /**
     * invalidates session id
     *
     * @return  SessionId
     */
    public function invalidate()
    {
        return $this;
    }
}
?>