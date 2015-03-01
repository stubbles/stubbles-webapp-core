<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  stubbles\webapp\session
 */
namespace stubbles\webapp\websession;
use stubbles\ioc\InjectionProvider;
use stubbles\ioc\binding\BindingScope;
use stubbles\webapp\session\Session;
/**
 * Interface for session storages.
 */
class SessionBindingScope implements BindingScope
{
    /**
     * session prefix key
     */
    const SESSION_KEY  = 'stubbles.webapp.websession.scope#';
    /**
     * session instance to store instances in
     *
     * @type  Session
     */
    private $session;

    /**
     * constructor
     *
     * @param  Session  $session
     */
    public function __construct(Session $session)
    {
        $this->session = $session;
    }

    /**
     * returns the requested instance from the scope
     *
     * @param   \ReflectionClass                 $impl      concrete implementation
     * @param   \stubbles\ioc\InjectionProvider  $provider
     * @return  object
     */
    public function getInstance(\ReflectionClass $impl, InjectionProvider $provider)
    {
        $key = self::SESSION_KEY . $impl->getName();
        if ($this->session->hasValue($key) === true) {
            return $this->session->value($key);
        }

        $instance = $provider->get();
        $this->session->putValue($key, $instance);
        return $instance;
    }
}
