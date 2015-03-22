<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  stubbles\webapp
 */
namespace stubbles\webapp\ioc;
use \stubbles\ioc\binding\Session as IocSession;
use stubbles\webapp\session\Session as WebAppSession;
/**
 * Session adapter for webapp session to ioc session.
 *
 * @since  6.0.0
 */
class SessionAdapter implements IocSession
{
    /**
     * @type  \stubbles\webapp\session\Session
     */
    private $session;

    /**
     * constructor
     *
     * @param  \stubbles\webapp\session\Session  $session
     */
    public function __construct(WebAppSession $session)
    {
        $this->session = $session;
    }

    /**
     * checks if session contains value under given key
     *
     * @param   string  $key
     * @return  bool
     */
    public function hasValue($key)
    {
        return $this->session->hasValue($key);
    }

    /**
     * returns value stored under given key
     *
     * @param   string  $key
     * @return  mixed
     */
    public function value($key)
    {
        return $this->session->value($key);
    }

    /**
     * stores given value under given key
     *
     * @param  string  $key
     * @param  mixed   $value
     */
    public function putValue($key, $value)
    {
        return $this->session->putValue($key, $value);
    }
}
