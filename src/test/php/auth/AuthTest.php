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
use stubbles\lang\Mode;
use stubbles\webapp\auth\session\CachingAuthenticationProvider;
use stubbles\webapp\auth\session\CachingAuthorizationProvider;
use stubbles\webapp\auth\token\TokenAuthenticator;
use stubbles\webapp\auth\token\TokenStore;
use stubbles\webapp\session\Session;
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
        $this->authenticationProviderClass = NewInstance::classname(AuthenticationProvider::class);
        $this->authorizationProviderClass  = NewInstance::classname(AuthorizationProvider::class);
        $this->binder = new Binder();
        $this->binder->bind(Session::class)
                ->toInstance(NewInstance::of(Session::class));
        $this->binder->bindProperties(
                lang\properties(['config' => ['stubbles.webapp.auth.token.salt' => 'pepper']]),
                NewInstance::of(Mode::class)
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
                $injector->getInstance(AuthenticationProvider::class)
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
        assertFalse($injector->hasBinding(AuthorizationProvider::class));
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
                $injector->getInstance(AuthorizationProvider::class)
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
                $injector->getInstance(AuthenticationProvider::class, 'original')
        );
        assertInstanceOf(
                CachingAuthenticationProvider::class,
                $injector->getInstance(AuthenticationProvider::class)
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
                $injector->getInstance(AuthorizationProvider::class, 'original')
        );
        assertInstanceOf(
                CachingAuthorizationProvider::class,
                $injector->getInstance(AuthorizationProvider::class)
        );
    }

    /**
     * @test
     */
    public function usingTokensBindsTokenStore()
    {
        $tokenStoreClass = NewInstance::classname(TokenStore::class);
        Auth::usingTokens($tokenStoreClass, $this->authenticationProviderClass)
            ->configure($this->binder);
        $injector = $this->binder->getInjector();
        assertInstanceOf(
                $tokenStoreClass,
                $injector->getInstance(TokenStore::class)
        );
    }

    /**
     * @test
     */
    public function usingTokensBindsLoginProvider()
    {
        $tokenStoreClass = NewInstance::classname(TokenStore::class);
        Auth::usingTokens($tokenStoreClass, $this->authenticationProviderClass)
            ->configure($this->binder);
        $injector = $this->binder->getInjector();
        assertInstanceOf(
                TokenAuthenticator::class,
                $injector->getInstance(AuthenticationProvider::class)
        );
        assertInstanceOf(
                $this->authenticationProviderClass,
                $injector->getInstance(
                        AuthenticationProvider::class,
                        'stubbles.webapp.auth.token.loginProvider'
                )
        );
    }

    /**
     * @test
     */
    public function usingTokensWithSessionEnabledBindsLoginProvider()
    {
        $tokenStoreClass = NewInstance::classname(TokenStore::class);
        Auth::usingTokens($tokenStoreClass, $this->authenticationProviderClass)
            ->enableSessionCaching()
            ->configure($this->binder);
        $injector = $this->binder->getInjector();
        assertInstanceOf(
                CachingAuthenticationProvider::class,
                $injector->getInstance(AuthenticationProvider::class)
        );
        assertInstanceOf(
                TokenAuthenticator::class,
                $injector->getInstance(AuthenticationProvider::class, 'original')
        );
        assertInstanceOf(
                $this->authenticationProviderClass,
                $injector->getInstance(
                        AuthenticationProvider::class,
                        'stubbles.webapp.auth.token.loginProvider'
                )
        );
    }
}
