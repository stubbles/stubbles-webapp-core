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
    private $mockSession;
    /**
     * mocked base authentication provider
     *
     * @type  \PHPUnit_Framework_MockObject_MockObject
     */
    private $mockAuthenticationProvider;

    /**
     * set up test environment
     */
    public function setUp()
    {
        $this->mockSession                = $this->getMock('stubbles\webapp\session\Session');
        $this->mockAuthenticationProvider = $this->getMock('stubbles\webapp\auth\AuthenticationProvider');
        $this->cachingAuthenticationProvider = new CachingAuthenticationProvider(
                $this->mockSession,
                $this->mockAuthenticationProvider
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
        $this->mockSession->method('hasValue')->will(returnValue(true));
        $this->mockSession->method('value')->will(returnValue($user));
        $this->mockAuthenticationProvider->expects(never())
                ->method('authenticate');
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
        $this->mockSession->method('hasValue')->will(returnValue(false));
        $this->mockAuthenticationProvider->expects($this->once())
                                         ->method('authenticate')
                                         ->will($this->returnValue(null));
        $this->mockSession->expects(never())->method('putValue');
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
        $this->mockSession->method('hasValue')->will(returnValue(false));
        $this->mockAuthenticationProvider->method('authenticate')
                ->will(returnValue($user));
        $this->mockSession->expects(once())
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
        $this->mockAuthenticationProvider->method('loginUri')
                ->will(returnValue('http://login.example.net/'));
        assertEquals(
                'http://login.example.net/',
                $this->cachingAuthenticationProvider->loginUri(
                        $this->getMock('stubbles\webapp\Request')
                )
        );
    }
}
