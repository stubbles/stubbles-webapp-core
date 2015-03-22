<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  stubbles\webapp
 */
namespace stubbles\webapp\auth;
use stubbles\ioc\Binder;
use stubbles\ioc\module\BindingModule;
/**
 * Binds authentication and authorization providers.
 *
 * @since  5.0.0
 */
class Auth implements BindingModule
{
    /**
     * class name of authentication provider to use
     *
     * @type  string
     */
    private $authenticationProvider;
    /**
     * login provider to use of authentication provider has no own means of login
     *
     * @type  string
     */
    private $loginProvider;
    /**
     * class which stores tokens
     *
     * @type  string
     */
    private $tokenStore;
    /**
     * class name of authorization provider to use
     *
     * @type  string
     */
    private $authorizationProvider;
    /**
     * switch whether to enable session caching or not
     *
     * @type  bool
     */
    private $enableSessionCaching = false;

    /**
     * constructor
     *
     * @param  string  $authenticationProvider
     * @param  string  $authorizationProvider   optional
     */
    public function __construct($authenticationProvider, $authorizationProvider = null)
    {
        $this->authenticationProvider = $authenticationProvider;
        $this->authorizationProvider  = $authorizationProvider;
    }

    /**
     * factory method
     *
     * @param   string  $authenticationProvider
     * @param   string  $authorizationProvider   optional
     * @return  \stubbles\webapp\ioc\Auth
     */
    public static function with($authenticationProvider, $authorizationProvider = null)
    {
        return new self($authenticationProvider, $authorizationProvider);
    }

    /**
     *
     * @param   string  $tokenStore             class which stores tokens
     * @param   string  $loginProvider          login provider to use because token authenticator has no own means of a login
     * @param   string  $authorizationProvider  optional
     * @return  \stubbles\webapp\ioc\Auth
     */
    public static function usingTokens($tokenStore, $loginProvider, $authorizationProvider = null)
    {
        $self = new self(
                'stubbles\webapp\auth\token\TokenAuthenticator',
                $authorizationProvider
        );
        $self->loginProvider = $loginProvider;
        $self->tokenStore    = $tokenStore;
        return $self;
    }

    /**
     * enables session caching of authentication and authorization information
     *
     * @return  \stubbles\webapp\ioc\Auth
     */
    public function enableSessionCaching()
    {
        $this->enableSessionCaching = true;
        return $this;
    }

    /**
     * configure the binder
     *
     * @param  \stubbles\ioc\Binder  $binder
     */
    public function configure(Binder $binder)
    {
        if ($this->enableSessionCaching) {
            $binder->bind('stubbles\webapp\auth\AuthenticationProvider')
                   ->to('stubbles\webapp\auth\session\CachingAuthenticationProvider');
            $binder->bind('stubbles\webapp\auth\AuthenticationProvider')
               ->named('original')
               ->to($this->authenticationProvider);
            if (null !== $this->authorizationProvider) {
                $binder->bind('stubbles\webapp\auth\AuthorizationProvider')
                   ->to('stubbles\webapp\auth\session\CachingAuthorizationProvider');
                $binder->bind('stubbles\webapp\auth\AuthorizationProvider')
                       ->named('original')
                       ->to($this->authorizationProvider);
            }

            if (null !== $this->tokenStore) {
                $binder->bind('stubbles\webapp\auth\AuthenticationProvider')
                       ->named('stubbles.webapp.auth.token.loginProvider')
                       ->to($this->loginProvider);
                $binder->bind('stubbles\webapp\auth\token\TokenStore')
                       ->to($this->tokenStore);
            }
        } else {
            $binder->bind('stubbles\webapp\auth\AuthenticationProvider')
               ->to($this->authenticationProvider);
            if (null !== $this->tokenStore) {
                $binder->bind('stubbles\webapp\auth\AuthenticationProvider')
                       ->named('stubbles.webapp.auth.token.loginProvider')
                       ->to($this->loginProvider);
                $binder->bind('stubbles\webapp\auth\token\TokenStore')
                       ->to($this->tokenStore);
            }

            if (null !== $this->authorizationProvider) {
                $binder->bind('stubbles\webapp\auth\AuthorizationProvider')
                       ->to($this->authorizationProvider);
            }
        }
    }
}
