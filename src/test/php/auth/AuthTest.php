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
    private $mockAuthenticationProviderClass;
    /**
     * an authorization provider which can be used for the tests
     *
     * @type  string
     */
    private $mockAuthorizationProviderClass;
    /**
     * @type  \stubbles\ioc\Binder
     */
    private $binder;

    /**
     * set up test environment
     */
    public function setUp()
    {
        $this->mockAuthenticationProviderClass = $this->getMockClass('stubbles\webapp\auth\AuthenticationProvider');
        $this->mockAuthorizationProviderClass  = $this->getMockClass('stubbles\webapp\auth\AuthorizationProvider');
        $this->binder = new Binder();
        $this->binder->bind('stubbles\webapp\session\Session')
                     ->toInstance($this->getMock('stubbles\webapp\session\Session'));
        $this->binder->bindProperties(
                lang\properties(['config' => ['stubbles.webapp.auth.token.salt' => 'pepper']]),
                $this->getMock('stubbles\lang\Mode')
        );
    }
    /**
     * @test
     */
    public function bindsOriginalAuthenticationProviderOnlyIfSessionCachingNotEnabled()
    {
        Auth::with($this->mockAuthenticationProviderClass)
            ->configure($this->binder);
        $injector = $this->binder->getInjector();
        assertInstanceOf(
                $this->mockAuthenticationProviderClass,
                $injector->getInstance('stubbles\webapp\auth\AuthenticationProvider')
        );
    }

    /**
     * @test
     */
    public function bindsNoAuthorizationProviderIfNoneGiven()
    {
        Auth::with($this->mockAuthenticationProviderClass)
            ->configure($this->binder);
        $injector = $this->binder->getInjector();
        assertFalse($injector->hasBinding('stubbles\webapp\auth\AuthorizationProvider'));
    }

    /**
     * @test
     */
    public function bindsOriginalAuthorizationProviderOnlyIfSessionCachingNotEnabled()
    {
        Auth::with($this->mockAuthenticationProviderClass, $this->mockAuthorizationProviderClass)
            ->configure($this->binder);
        $injector = $this->binder->getInjector();
        assertInstanceOf(
                $this->mockAuthorizationProviderClass,
                $injector->getInstance('stubbles\webapp\auth\AuthorizationProvider')
        );
    }

    /**
     * @test
     */
    public function bindsAllAuthenticationProviderOnlyIfSessionCachingEnabled()
    {
        Auth::with($this->mockAuthenticationProviderClass)
            ->enableSessionCaching()
            ->configure($this->binder);
        $injector = $this->binder->getInjector();
        assertInstanceOf(
                $this->mockAuthenticationProviderClass,
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
        Auth::with($this->mockAuthenticationProviderClass, $this->mockAuthorizationProviderClass)
            ->enableSessionCaching()
            ->configure($this->binder);
        $injector = $this->binder->getInjector();
        assertInstanceOf(
                $this->mockAuthorizationProviderClass,
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
        $mockTokenStoreClass = $this->getMockClass('stubbles\webapp\auth\token\TokenStore');
        Auth::usingTokens($mockTokenStoreClass, $this->mockAuthenticationProviderClass)
            ->configure($this->binder);
        $injector = $this->binder->getInjector();
        assertInstanceOf(
                $mockTokenStoreClass,
                $injector->getInstance('stubbles\webapp\auth\token\TokenStore')
        );
    }

    /**
     * @test
     */
    public function usingTokensBindsLoginProvider()
    {
        $mockTokenStoreClass = $this->getMockClass('stubbles\webapp\auth\token\TokenStore');
        Auth::usingTokens($mockTokenStoreClass, $this->mockAuthenticationProviderClass)
            ->configure($this->binder);
        $injector = $this->binder->getInjector();
        assertInstanceOf(
                'stubbles\webapp\auth\token\TokenAuthenticator',
                $injector->getInstance('stubbles\webapp\auth\AuthenticationProvider')
        );
        assertInstanceOf(
                $this->mockAuthenticationProviderClass,
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
        $mockTokenStoreClass = $this->getMockClass('stubbles\webapp\auth\token\TokenStore');
        Auth::usingTokens($mockTokenStoreClass, $this->mockAuthenticationProviderClass)
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
                $this->mockAuthenticationProviderClass,
                $injector->getInstance(
                        'stubbles\webapp\auth\AuthenticationProvider',
                        'stubbles.webapp.auth.token.loginProvider'
                )
        );
    }
}
