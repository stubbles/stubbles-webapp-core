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
use bovigo\callmap\NewInstance;
use stubbles\ioc\Binder;
use stubbles\lang;
/**
 * Tests for stubbles\webapp\auth\Auth.
 *
 * @since  5.0.0
 */
class AuthTest extends \PHPUnit_Framework_TestCase
{
    /**
     * an authentication provider which can be used for the tests
     *
     * @type  string
     */
    private $authenticationProviderClass;
    /**
     * an authorization provider which can be used for the tests
     *
     * @type  string
     */
    private $authorizationProviderClass;
    /**
     * @type  \stubbles\ioc\Binder
     */
    private $binder;

    /**
     * set up test environment
     */
    public function setUp()
    {
        $this->authenticationProviderClass = NewInstance::classname('stubbles\webapp\auth\AuthenticationProvider');
        $this->authorizationProviderClass  = NewInstance::classname('stubbles\webapp\auth\AuthorizationProvider');
        $this->binder = new Binder();
        $this->binder->bind('stubbles\webapp\session\Session')
                ->toInstance(NewInstance::of('stubbles\webapp\session\Session'));
        $this->binder->bindProperties(
                lang\properties(['config' => ['stubbles.webapp.auth.token.salt' => 'pepper']]),
                NewInstance::of('stubbles\lang\Mode')
        );
    }
    /**
     * @test
     */
    public function bindsOriginalAuthenticationProviderOnlyIfSessionCachingNotEnabled()
    {
        Auth::with($this->authenticationProviderClass)
            ->configure($this->binder);
        $injector = $this->binder->getInjector();
        assertInstanceOf(
                $this->authenticationProviderClass,
                $injector->getInstance('stubbles\webapp\auth\AuthenticationProvider')
        );
    }

    /**
     * @test
     */
    public function bindsNoAuthorizationProviderIfNoneGiven()
    {
        Auth::with($this->authenticationProviderClass)
            ->configure($this->binder);
        $injector = $this->binder->getInjector();
        assertFalse($injector->hasBinding('stubbles\webapp\auth\AuthorizationProvider'));
    }

    /**
     * @test
     */
    public function bindsOriginalAuthorizationProviderOnlyIfSessionCachingNotEnabled()
    {
        Auth::with($this->authenticationProviderClass, $this->authorizationProviderClass)
            ->configure($this->binder);
        $injector = $this->binder->getInjector();
        assertInstanceOf(
                $this->authorizationProviderClass,
                $injector->getInstance('stubbles\webapp\auth\AuthorizationProvider')
        );
    }

    /**
     * @test
     */
    public function bindsAllAuthenticationProviderOnlyIfSessionCachingEnabled()
    {
        Auth::with($this->authenticationProviderClass)
            ->enableSessionCaching()
            ->configure($this->binder);
        $injector = $this->binder->getInjector();
        assertInstanceOf(
                $this->authenticationProviderClass,
                $injector->getInstance('stubbles\webapp\auth\AuthenticationProvider', 'original')
        );
        assertInstanceOf(
                'stubbles\webapp\auth\session\CachingAuthenticationProvider',
                $injector->getInstance('stubbles\webapp\auth\AuthenticationProvider')
        );
    }

    /**
     * @test
     */
    public function bindsAllAuthorizationProviderOnlyIfSessionCachingNotEnabled()
    {
        Auth::with($this->authenticationProviderClass, $this->authorizationProviderClass)
            ->enableSessionCaching()
            ->configure($this->binder);
        $injector = $this->binder->getInjector();
        assertInstanceOf(
                $this->authorizationProviderClass,
                $injector->getInstance('stubbles\webapp\auth\AuthorizationProvider', 'original')
        );
        assertInstanceOf(
                'stubbles\webapp\auth\session\CachingAuthorizationProvider',
                $injector->getInstance('stubbles\webapp\auth\AuthorizationProvider')
        );
    }

    /**
     * @test
     */
    public function usingTokensBindsTokenStore()
    {
        $tokenStoreClass = NewInstance::classname('stubbles\webapp\auth\token\TokenStore');
        Auth::usingTokens($tokenStoreClass, $this->authenticationProviderClass)
            ->configure($this->binder);
        $injector = $this->binder->getInjector();
        assertInstanceOf(
                $tokenStoreClass,
                $injector->getInstance('stubbles\webapp\auth\token\TokenStore')
        );
    }

    /**
     * @test
     */
    public function usingTokensBindsLoginProvider()
    {
        $tokenStoreClass = NewInstance::classname('stubbles\webapp\auth\token\TokenStore');
        Auth::usingTokens($tokenStoreClass, $this->authenticationProviderClass)
            ->configure($this->binder);
        $injector = $this->binder->getInjector();
        assertInstanceOf(
                'stubbles\webapp\auth\token\TokenAuthenticator',
                $injector->getInstance('stubbles\webapp\auth\AuthenticationProvider')
        );
        assertInstanceOf(
                $this->authenticationProviderClass,
                $injector->getInstance(
                        'stubbles\webapp\auth\AuthenticationProvider',
                        'stubbles.webapp.auth.token.loginProvider'
                )
        );
    }

    /**
     * @test
     */
    public function usingTokensWithSessionEnabledBindsLoginProvider()
    {
        $tokenStoreClass = NewInstance::classname('stubbles\webapp\auth\token\TokenStore');
        Auth::usingTokens($tokenStoreClass, $this->authenticationProviderClass)
            ->enableSessionCaching()
            ->configure($this->binder);
        $injector = $this->binder->getInjector();
        assertInstanceOf(
                'stubbles\webapp\auth\session\CachingAuthenticationProvider',
                $injector->getInstance('stubbles\webapp\auth\AuthenticationProvider')
        );
        assertInstanceOf(
                'stubbles\webapp\auth\token\TokenAuthenticator',
                $injector->getInstance('stubbles\webapp\auth\AuthenticationProvider', 'original')
        );
        assertInstanceOf(
                $this->authenticationProviderClass,
                $injector->getInstance(
                        'stubbles\webapp\auth\AuthenticationProvider',
                        'stubbles.webapp.auth.token.loginProvider'
                )
        );
    }
}
