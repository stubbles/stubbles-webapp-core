<?php
declare(strict_types=1);
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
use stubbles\Environment;
use stubbles\ioc\Binder;
use stubbles\values\Properties;
use stubbles\webapp\auth\session\CachingAuthenticationProvider;
use stubbles\webapp\auth\session\CachingAuthorizationProvider;
use stubbles\webapp\auth\token\TokenAuthenticator;
use stubbles\webapp\auth\token\TokenStore;
use stubbles\webapp\session\Session;

use function bovigo\assert\assert;
use function bovigo\assert\assertFalse;
use function bovigo\assert\predicate\equals;
use function bovigo\assert\predicate\isInstanceOf;
/**
 * Tests for stubbles\webapp\auth\Auth.
 *
 * @since  5.0.0
 * @group  auth
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
                new Properties(['config' => ['stubbles.webapp.auth.token.salt' => 'pepper']]),
                'development'
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
        assert(
                $injector->getInstance(AuthenticationProvider::class),
                isInstanceOf($this->authenticationProviderClass)
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
        assert(
                $injector->getInstance(AuthorizationProvider::class),
                isInstanceOf($this->authorizationProviderClass)
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
        assert(
                $injector->getInstance(AuthenticationProvider::class, 'original'),
                isInstanceOf($this->authenticationProviderClass)
        );
        assert(
                $injector->getInstance(AuthenticationProvider::class),
                isInstanceOf(CachingAuthenticationProvider::class)
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
        assert(
                $injector->getInstance(AuthorizationProvider::class, 'original'),
                isInstanceOf($this->authorizationProviderClass)
        );
        assert(
                $injector->getInstance(AuthorizationProvider::class),
                isInstanceOf(CachingAuthorizationProvider::class)
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
        assert(
                $injector->getInstance(TokenStore::class),
                isInstanceOf($tokenStoreClass)
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
        assert(
                $injector->getInstance(AuthenticationProvider::class),
                isInstanceOf(TokenAuthenticator::class)
        );
        assert(
                $injector->getInstance(
                        AuthenticationProvider::class,
                        'stubbles.webapp.auth.token.loginProvider'
                ),
                isInstanceOf($this->authenticationProviderClass)
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
        assert(
                $injector->getInstance(AuthenticationProvider::class),
                isInstanceOf(CachingAuthenticationProvider::class)
        );
        assert(
                $injector->getInstance(AuthenticationProvider::class, 'original'),
                isInstanceOf(TokenAuthenticator::class)
        );
        assert(
                $injector->getInstance(
                        AuthenticationProvider::class,
                        'stubbles.webapp.auth.token.loginProvider'
                ),
                isInstanceOf($this->authenticationProviderClass)
        );
    }
}
