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
use stubbles\webapp\auth\Roles;
/**
 * Tests for stubbles\webapp\auth\session\CachingAuthorizationProvider
 *
 * @since  5.0.0
 */
class CachingAuthorizationProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * instance to test
     *
     * @type  \stubbles\webapp\auth\session\CachingAuthorizationProvider
     */
    private $cachingAuthorizationProvider;
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
    private $mockAuthorizationProvider;

    /**
     * set up test environment
     */
    public function setUp()
    {
        $this->mockSession                  = $this->getMock('stubbles\webapp\session\Session');
        $this->mockAuthorizationProvider    = $this->getMock('stubbles\webapp\auth\AuthorizationProvider');
        $this->cachingAuthorizationProvider = new CachingAuthorizationProvider(
                $this->mockSession,
                $this->mockAuthorizationProvider
        );
    }

    /**
     * @test
     */
    public function annotationsPresentOnConstructor()
    {
        $this->assertTrue(
                reflect\constructorAnnotationsOf($this->cachingAuthorizationProvider)
                        ->contain('Inject')
        );

        $annotations = reflect\annotationsOfConstructorParameter(
                'authorizationProvider',
                $this->cachingAuthorizationProvider
        );
        $this->assertTrue($annotations->contain('Named'));
        $this->assertEquals(
                'original',
                $annotations->firstNamed('Named')->getName()
        );
    }

    /**
     * @test
     */
    public function usesSessionValueIfRolesStoredInSession()
    {
        $roles = new Roles(['admin']);
        $this->mockSession->expects($this->once())
                          ->method('hasValue')
                          ->will($this->returnValue(true));
        $this->mockSession->expects($this->once())
                          ->method('value')
                          ->will($this->returnValue($roles));
        $this->mockAuthorizationProvider->expects($this->never())
                                        ->method('roles');
        $this->assertSame(
                $roles,
                $this->cachingAuthorizationProvider->roles(
                        $this->getMock('stubbles\webapp\auth\User')
                )
        );
    }

    /**
     * @test
     */
    public function storeReturnValueInSessionWhenOriginalAuthenticationProviderReturnsRoles()
    {
        $roles = new Roles(['admin']);
        $this->mockSession->expects($this->once())
                          ->method('hasValue')
                          ->will($this->returnValue(false));
        $this->mockAuthorizationProvider->expects($this->once())
                                         ->method('roles')
                                         ->will($this->returnValue($roles));
        $this->mockSession->expects($this->once())
                          ->method('putValue')
                          ->with($this->equalTo(Roles::SESSION_KEY), $this->equalTo($roles));
        $this->assertSame(
                $roles,
                $this->cachingAuthorizationProvider->roles(
                        $this->getMock('stubbles\webapp\auth\User')
                )
        );
    }
}
