<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  stubbles\webapp
 */
namespace stubbles\webapp\auth\session;
use bovigo\callmap;
use bovigo\callmap\NewInstance;
use stubbles\lang\reflect;
use stubbles\webapp\Request;
use stubbles\webapp\auth\AuthenticationProvider;
use stubbles\webapp\auth\User;
use stubbles\webapp\session\Session;
/**
 * Tests for stubbles\webapp\auth\session\CachingAuthenticationProvider
 *
 * @since  5.0.0
 */
class CachingAuthenticationProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * instance to test
     *
     * @type  \stubbles\webapp\auth\session\CachingAuthenticationProvider
     */
    private $cachingAuthenticationProvider;
    /**
     * mocked session
     *
     * @type  \bovigo\callmap\Proxy
     */
    private $session;
    /**
     * mocked base authentication provider
     *
     * @type  \bovigo\callmap\Proxy
     */
    private $authenticationProvider;

    /**
     * set up test environment
     */
    public function setUp()
    {
        $this->session                = NewInstance::of(Session::class);
        $this->authenticationProvider = NewInstance::of(AuthenticationProvider::class);
        $this->cachingAuthenticationProvider = new CachingAuthenticationProvider(
                $this->session,
                $this->authenticationProvider
        );
    }

    /**
     * @test
     */
    public function annotationsPresentOnConstructor()
    {
        $parameterAnnotations = reflect\annotationsOfConstructorParameter(
                'authenticationProvider',
                $this->cachingAuthenticationProvider
        );
        assertTrue($parameterAnnotations->contain('Named'));
        assertEquals(
                'original',
                $parameterAnnotations->firstNamed('Named')->getName()
        );
    }

    /**
     * @test
     */
    public function usesSessionValueIfUserStoredInSession()
    {
        $user = NewInstance::of(User::class);
        $this->session->mapCalls(['hasValue' => true, 'value' => $user]);
        assertSame(
                $user,
                $this->cachingAuthenticationProvider->authenticate(
                        NewInstance::of(Request::class)
                )
        );
        callmap\verify($this->authenticationProvider, 'authenticate')
                ->wasNeverCalled();
    }

    /**
     * @test
     */
    public function doesNotStoreReturnValueWhenOriginalAuthenticationProviderReturnsNull()
    {
        $this->session->mapCalls(['hasValue' => false]);
        assertNull(
                $this->cachingAuthenticationProvider->authenticate(
                        NewInstance::of(Request::class)
                )
        );
        callmap\verify($this->session, 'putValue')->wasNeverCalled();
    }

    /**
     * @test
     */
    public function storeReturnValueInSessionWhenOriginalAuthenticationProviderReturnsUser()
    {
        $user = NewInstance::of(User::class);
        $this->session->mapCalls(['hasValue' => false]);
        $this->authenticationProvider->mapCalls(['authenticate' => $user]);
        assertSame(
                $user,
                $this->cachingAuthenticationProvider->authenticate(
                        NewInstance::of(Request::class)
                )
        );
        callmap\verify($this->session, 'putValue')
                ->received(User::SESSION_KEY, $user);
    }

    /**
     * @test
     */
    public function returnsLoginUriFromOriginalAuthenticationProvider()
    {
        $this->authenticationProvider->mapCalls(
                ['loginUri' => 'http://login.example.net/']
        );
        assertEquals(
                'http://login.example.net/',
                $this->cachingAuthenticationProvider->loginUri(
                        NewInstance::of(Request::class)
                )
        );
    }
}
