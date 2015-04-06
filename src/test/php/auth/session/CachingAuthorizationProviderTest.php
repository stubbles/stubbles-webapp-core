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
        assertTrue(
                reflect\annotationsOfConstructor($this->cachingAuthorizationProvider)
                        ->contain('Inject')
        );

        $annotations = reflect\annotationsOfConstructorParameter(
                'authorizationProvider',
                $this->cachingAuthorizationProvider
        );
        assertTrue($annotations->contain('Named'));
        assertEquals(
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
        $this->mockSession->method('hasValue')->will(returnValue(true));
        $this->mockSession->method('value')->will(returnValue($roles));
        $this->mockAuthorizationProvider->expects(never())->method('roles');
        assertSame(
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
        $this->mockSession->method('hasValue')->will(returnValue(false));
        $this->mockAuthorizationProvider->method('roles')
                ->will(returnValue($roles));
        $this->mockSession->expects(once())
                ->method('putValue')
                ->with(equalTo(Roles::SESSION_KEY), equalTo($roles));
        assertSame(
                $roles,
                $this->cachingAuthorizationProvider->roles(
                        $this->getMock('stubbles\webapp\auth\User')
                )
        );
    }
}
