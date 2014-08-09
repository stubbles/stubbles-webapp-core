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
use stubbles\ioc\Binder;
/**
 * Tests for stubbles\webapp\ioc\Auth.
 *
 * @since  5.0.0
 */
class AuthTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function bindsOriginalAuthenticationProviderOnlyIfSessionCachingNotEnabled()
    {
        $binder = new Binder();
        Auth::with('example\AuthenticationProvider')
            ->configure($binder);
        $injector = $binder->getInjector();
        $this->assertTrue($injector->hasBinding('stubbles\webapp\auth\AuthenticationProvider', 'original'));
        $this->assertFalse($injector->hasBinding('stubbles\webapp\auth\AuthenticationProvider'));
    }

    /**
     * @test
     */
    public function bindsNoAuthorizationProviderIfNoneGiven()
    {
        $binder = new Binder();
        Auth::with('example\AuthenticationProvider')
            ->configure($binder);
        $injector = $binder->getInjector();
        $this->assertFalse($injector->hasBinding('stubbles\webapp\auth\AuthorizationProvider', 'original'));
        $this->assertFalse($injector->hasBinding('stubbles\webapp\auth\AuthorizationProvider'));
    }

    /**
     * @test
     */
    public function bindsOriginalAuthorizationProviderOnlyIfSessionCachingNotEnabled()
    {
        $binder = new Binder();
        Auth::with('example\AuthenticationProvider', 'example\AuthorizationProvider')
            ->configure($binder);
        $injector = $binder->getInjector();
        $this->assertTrue($injector->hasBinding('stubbles\webapp\auth\AuthorizationProvider', 'original'));
        $this->assertFalse($injector->hasBinding('stubbles\webapp\auth\AuthorizationProvider'));
    }

    /**
     * @test
     */
    public function bindsAllAuthenticationProviderOnlyIfSessionCachingEnabled()
    {
        $binder = new Binder();
        Auth::with('example\AuthenticationProvider')
            ->enableSessionCaching()
            ->configure($binder);
        $injector = $binder->getInjector();
        $this->assertTrue($injector->hasBinding('stubbles\webapp\auth\AuthenticationProvider', 'original'));
        $this->assertTrue($injector->hasBinding('stubbles\webapp\auth\AuthenticationProvider'));
    }

    /**
     * @test
     */
    public function bindsAllAuthorizationProviderOnlyIfSessionCachingNotEnabled()
    {
        $binder = new Binder();
        Auth::with('example\AuthenticationProvider', 'example\AuthorizationProvider')
            ->enableSessionCaching()
            ->configure($binder);
        $injector = $binder->getInjector();
        $this->assertTrue($injector->hasBinding('stubbles\webapp\auth\AuthorizationProvider', 'original'));
        $this->assertTrue($injector->hasBinding('stubbles\webapp\auth\AuthorizationProvider'));
    }

    /**
     * @test
     */
    public function usingTokensBindsLoginProvider()
    {
        $binder = new Binder();
        Auth::usingTokens('example\TokenStore', 'example\LoginAuthenticationProvider')
            ->configure($binder);
        $injector = $binder->getInjector();
        $this->assertTrue(
                $injector->hasBinding(
                        'stubbles\webapp\auth\AuthenticationProvider',
                        'stubbles.webapp.auth.token.loginProvider'
                )
        );
        $this->assertTrue(
                $injector->hasBinding('stubbles\webapp\auth\token\TokenStore')
        );
    }
}
