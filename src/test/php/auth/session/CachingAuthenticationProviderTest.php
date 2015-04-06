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
use stubbles\lang\reflect;
use stubbles\webapp\auth\User;
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
     * @type  \PHPUnit_Framework_MockObject_MockObject
     */
    private $session;
    /**
     * mocked base authentication provider
     *
     * @type  \PHPUnit_Framework_MockObject_MockObject
     */
    private $authenticationProvider;

    /**
     * set up test environment
     */
    public function setUp()
    {
        $this->session                = $this->getMock('stubbles\webapp\session\Session');
        $this->authenticationProvider = $this->getMock('stubbles\webapp\auth\AuthenticationProvider');
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
        assertTrue(
                reflect\annotationsOfConstructor($this->cachingAuthenticationProvider)
                        ->contain('Inject')
        );

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
        $user = $this->getMock('stubbles\webapp\auth\User');
        $this->session->method('hasValue')->will(returnValue(true));
        $this->session->method('value')->will(returnValue($user));
        $this->authenticationProvider->expects(never())->method('authenticate');
        assertSame(
                $user,
                $this->cachingAuthenticationProvider->authenticate(
                        $this->getMock('stubbles\webapp\Request')
                )
        );
    }

    /**
     * @test
     */
    public function doesNotStoreReturnValueWhenOriginalAuthenticationProviderReturnsNull()
    {
        $this->session->method('hasValue')->will(returnValue(false));
        $this->authenticationProvider->expects(once())
                    ->method('authenticate')
                    ->will(returnValue(null));
        $this->session->expects(never())->method('putValue');
        assertNull(
                $this->cachingAuthenticationProvider->authenticate(
                        $this->getMock('stubbles\webapp\Request')
                )
        );
    }

    /**
     * @test
     */
    public function storeReturnValueInSessionWhenOriginalAuthenticationProviderReturnsUser()
    {
        $user = $this->getMock('stubbles\webapp\auth\User');
        $this->session->method('hasValue')->will(returnValue(false));
        $this->authenticationProvider->method('authenticate')
                ->will(returnValue($user));
        $this->session->expects(once())
                ->method('putValue')
                ->with(equalTo(User::SESSION_KEY), equalTo($user));
        assertSame(
                $user,
                $this->cachingAuthenticationProvider->authenticate(
                        $this->getMock('stubbles\webapp\Request')
                )
        );
    }

    /**
     * @test
     */
    public function returnsLoginUriFromOriginalAuthenticationProvider()
    {
        $this->authenticationProvider->method('loginUri')
                ->will(returnValue('http://login.example.net/'));
        assertEquals(
                'http://login.example.net/',
                $this->cachingAuthenticationProvider->loginUri(
                        $this->getMock('stubbles\webapp\Request')
                )
        );
    }
}
