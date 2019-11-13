<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace stubbles\webapp\auth;
use bovigo\callmap\NewInstance;
use PHPUnit\Framework\TestCase;
use stubbles\Environment;
use stubbles\ioc\Binder;
use stubbles\values\Properties;
use stubbles\webapp\auth\session\CachingAuthenticationProvider;
use stubbles\webapp\auth\session\CachingAuthorizationProvider;
use stubbles\webapp\auth\token\TokenAuthenticator;
use stubbles\webapp\auth\token\TokenStore;
use stubbles\webapp\session\Session;

use function bovigo\assert\assertThat;
use function bovigo\assert\assertFalse;
use function bovigo\assert\predicate\equals;
use function bovigo\assert\predicate\isInstanceOf;
/**
 * Tests for stubbles\webapp\auth\Auth.
 *
 * @since  5.0.0
 * @group  auth
 */
class AuthTest extends TestCase
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

    protected function setUp(): void
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
        assertThat(
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
        assertThat(
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
        assertThat(
                $injector->getInstance(AuthenticationProvider::class, 'original'),
                isInstanceOf($this->authenticationProviderClass)
        );
        assertThat(
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
        assertThat(
                $injector->getInstance(AuthorizationProvider::class, 'original'),
                isInstanceOf($this->authorizationProviderClass)
        );
        assertThat(
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
        assertThat(
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
        assertThat(
                $injector->getInstance(AuthenticationProvider::class),
                isInstanceOf(TokenAuthenticator::class)
        );
        assertThat(
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
        assertThat(
                $injector->getInstance(AuthenticationProvider::class),
                isInstanceOf(CachingAuthenticationProvider::class)
        );
        assertThat(
                $injector->getInstance(AuthenticationProvider::class, 'original'),
                isInstanceOf(TokenAuthenticator::class)
        );
        assertThat(
                $injector->getInstance(
                        AuthenticationProvider::class,
                        'stubbles.webapp.auth.token.loginProvider'
                ),
                isInstanceOf($this->authenticationProviderClass)
        );
    }
}
