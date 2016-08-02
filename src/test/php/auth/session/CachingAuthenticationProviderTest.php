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
namespace stubbles\webapp\auth\session;
use bovigo\callmap\NewInstance;
use stubbles\webapp\Request;
use stubbles\webapp\auth\AuthenticationProvider;
use stubbles\webapp\auth\User;
use stubbles\webapp\session\Session;

use function bovigo\assert\assert;
use function bovigo\assert\assertNull;
use function bovigo\assert\assertTrue;
use function bovigo\assert\predicate\equals;
use function bovigo\assert\predicate\isSameAs;
use function bovigo\callmap\verify;
use function stubbles\reflect\annotationsOfConstructorParameter;
/**
 * Tests for stubbles\webapp\auth\session\CachingAuthenticationProvider
 *
 * @since  5.0.0
 * @group  auth
 * @group  auth_session
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
        $parameterAnnotations = annotationsOfConstructorParameter(
                'authenticationProvider',
                $this->cachingAuthenticationProvider
        );
        assertTrue($parameterAnnotations->contain('Named'));
        assert(
                $parameterAnnotations->firstNamed('Named')->getName(),
                equals('original')
        );
    }

    /**
     * @test
     */
    public function usesSessionValueIfUserStoredInSession()
    {
        $user = NewInstance::of(User::class);
        $this->session->mapCalls(['hasValue' => true, 'value' => $user]);
        assert(
                $this->cachingAuthenticationProvider->authenticate(
                        NewInstance::of(Request::class)
                ),
                isSameAs($user)
        );
        verify($this->authenticationProvider, 'authenticate')->wasNeverCalled();
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
        verify($this->session, 'putValue')->wasNeverCalled();
    }

    /**
     * @test
     */
    public function storeReturnValueInSessionWhenOriginalAuthenticationProviderReturnsUser()
    {
        $user = NewInstance::of(User::class);
        $this->session->mapCalls(['hasValue' => false]);
        $this->authenticationProvider->mapCalls(['authenticate' => $user]);
        assert(
                $this->cachingAuthenticationProvider->authenticate(
                        NewInstance::of(Request::class)
                ),
                isSameAs($user)
        );
        verify($this->session, 'putValue')->received(User::SESSION_KEY, $user);
    }

    /**
     * @test
     */
    public function returnsLoginUriFromOriginalAuthenticationProvider()
    {
        $this->authenticationProvider->mapCalls(
                ['loginUri' => 'http://login.example.net/']
        );
        assert(
                $this->cachingAuthenticationProvider->loginUri(
                        NewInstance::of(Request::class)
                ),
                equals('http://login.example.net/')
        );
    }
}
