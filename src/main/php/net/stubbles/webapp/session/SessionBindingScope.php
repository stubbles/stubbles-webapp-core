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
use net\stubbles\ioc\InjectionProvider;
use net\stubbles\ioc\binding\BindingScope;
use net\stubbles\lang\reflect\BaseReflectionClass;
use net\stubbles\webapp\session\Session;
/**
 * Interface for session storages.
 */
class SessionBindingScope implements BindingScope
{
    /**
     * session prefix key
     */
    const SESSION_KEY  = 'net.stubbles.webapp.session.ioc.scope#';
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
     * @param   BaseReflectionClass  $impl      concrete implementation
     * @param   InjectionProvider    $provider
     * @return  Object
     */
    public function getInstance(BaseReflectionClass $impl, InjectionProvider $provider)
    {
        $key = self::SESSION_KEY . $impl->getName();
        if ($this->session->hasValue($key) === true) {
            return $this->session->getValue($key);
        }

        $instance = $provider->get();
        $this->session->putValue($key, $instance);
        return $instance;
    }
}
?>