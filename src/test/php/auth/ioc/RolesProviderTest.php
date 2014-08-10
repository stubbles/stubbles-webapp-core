<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  stubbles\webapp
 */
namespace stubbles\webapp\auth\ioc;
use stubbles\lang;
use stubbles\webapp\auth\Roles;
/**
 * Test for stubbles\webapp\auth\ioc\RolesProvider.
 *
 * @since  5.0.0
 * @group  auth
 * @group  ioc
 */
class RolesProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * instance to test
     *
     * @type  \stubbles\webapp\auth\ioc\RolesProvider
     */
    private $rolesProvider;
    /**
     * mocked session
     *
     * @type  \PHPUnit_Framework_MockObject_MockObject
     */
    private $mockSession;

    /**
     * set up test environment
     */
    public function setUp()
    {
        $this->mockSession   = $this->getMock('stubbles\webapp\session\Session');
        $this->rolesProvider = new RolesProvider($this->mockSession);
    }

    /**
     * @test
     */
    public function annotationsPresentOnClass()
    {
        $this->assertTrue(lang\reflect($this->rolesProvider)->hasAnnotation('Singleton'));
    }

    /**
     * @test
     */
    public function annotationsPresentOnConstructor()
    {
        $this->assertTrue(lang\reflectConstructor($this->rolesProvider)->hasAnnotation('Inject'));
    }

    /**
     * @test
     */
    public function isProviderForEmployee()
    {
        $this->assertEquals(
                get_class($this->rolesProvider),
                lang\reflect('stubbles\webapp\auth\Roles')
                    ->getAnnotation('ProvidedBy')
                    ->getValue()
                    ->getName()
        );
    }

    /**
     * @test
     * @expectedException  stubbles\lang\exception\RuntimeException
     */
    public function throwsRuntimeExceptionWhenNoUserInSession()
    {
        $this->mockSession->expects($this->once())
                          ->method('hasValue')
                          ->with($this->equalTo(Roles::SESSION_KEY))
                          ->will($this->returnValue(false));
        $this->rolesProvider->get();
    }

    /**
     * @test
     */
    public function returnsRolesFromSession()
    {
        $roles = new Roles(['admin']);
        $this->mockSession->expects($this->once())
                          ->method('hasValue')
                          ->with($this->equalTo(Roles::SESSION_KEY))
                          ->will($this->returnValue(true));
        $this->mockSession->expects($this->once())
                          ->method('value')
                          ->with($this->equalTo(Roles::SESSION_KEY))
                          ->will($this->returnValue($roles));
        $this->assertSame($roles, $this->rolesProvider->get());
    }
}
