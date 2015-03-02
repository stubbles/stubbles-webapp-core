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
        $this->assertTrue(
                reflect\annotationsOfConstructor($this->cachingAuthenticationProvider)
                        ->contain('Inject')
        );

        $parameterAnnotations = reflect\annotationsOfConstructorParameter(
                'authenticationProvider',
                $this->cachingAuthenticationProvider
        );
        $this->assertTrue($parameterAnnotations->contain('Named'));
        $this->assertEquals(
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
        $this->mockSession->expects($this->once())
                          ->method('hasValue')
                          ->will($this->returnValue(true));
        $this->mockSession->expects($this->once())
                          ->method('value')
                          ->will($this->returnValue($user));
        $this->mockAuthenticationProvider->expects($this->never())
                                         ->method('authenticate');
        $this->assertSame(
                $user,
                $this->cachingAuthenticationProvider->authenticate($this->getMock('stubbles\input\web\WebRequest'))
        );
    }

    /**
     * @test
     */
    public function doesNotStoreReturnValueWhenOriginalAuthenticationProviderReturnsNull()
    {
        $this->mockSession->expects($this->once())
                          ->method('hasValue')
                          ->will($this->returnValue(false));
        $this->mockAuthenticationProvider->expects($this->once())
                                         ->method('authenticate')
                                         ->will($this->returnValue(null));
        $this->mockSession->expects($this->never())
                          ->method('putValue');
        $this->assertNull($this->cachingAuthenticationProvider->authenticate($this->getMock('stubbles\input\web\WebRequest')));
    }

    /**
     * @test
     */
    public function storeReturnValueInSessionWhenOriginalAuthenticationProviderReturnsUser()
    {
        $user = $this->getMock('stubbles\webapp\auth\User');
        $this->mockSession->expects($this->once())
                          ->method('hasValue')
                          ->will($this->returnValue(false));
        $this->mockAuthenticationProvider->expects($this->once())
                                         ->method('authenticate')
                                         ->will($this->returnValue($user));
        $this->mockSession->expects($this->once())
                          ->method('putValue')
                          ->with($this->equalTo(User::SESSION_KEY), $this->equalTo($user));
        $this->assertSame(
                $user,
                $this->cachingAuthenticationProvider->authenticate($this->getMock('stubbles\input\web\WebRequest'))
        );
    }

    /**
     * @test
     */
    public function returnsLoginUriFromOriginalAuthenticationProvider()
    {
        $this->mockAuthenticationProvider->expects($this->once())
                                         ->method('loginUri')
                                         ->will($this->returnValue('http://login.example.net/'));
        $this->assertEquals(
                'http://login.example.net/',
                $this->cachingAuthenticationProvider->loginUri($this->getMock('stubbles\input\web\WebRequest'))
        );
    }
}
