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
use stubbles\webapp\auth\User;
/**
 * Test for stubbles\webapp\auth\ioc\UserProvider.
 *
 * @since  5.0.0
 * @group  auth
 * @group  ioc
 */
class UserProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * instance to test
     *
     * @type  \stubbles\webapp\auth\ioc\UserProvider
     */
    private $userProvider;
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
        $this->mockSession  = $this->getMock('stubbles\webapp\session\Session');
        $this->userProvider = new UserProvider($this->mockSession);
    }

    /**
     * @test
     */
    public function annotationsPresentOnConstructor()
    {
        $this->assertTrue(lang\reflectConstructor($this->userProvider)->hasAnnotation('Inject'));
    }

    /**
     * @test
     */
    public function isProviderForEmployee()
    {
        $this->assertEquals(
                get_class($this->userProvider),
                lang\reflect('stubbles\webapp\auth\User')
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
                          ->with($this->equalTo(User::SESSION_KEY))
                          ->will($this->returnValue(false));
        $this->userProvider->get();
    }

    /**
     * @test
     */
    public function returnsUserFromSession()
    {
        $user = $this->getMock('stubbles\webapp\auth\User');
        $this->mockSession->expects($this->once())
                          ->method('hasValue')
                          ->with($this->equalTo(User::SESSION_KEY))
                          ->will($this->returnValue(true));
        $this->mockSession->expects($this->once())
                          ->method('value')
                          ->with($this->equalTo(User::SESSION_KEY))
                          ->will($this->returnValue($user));
        $this->assertSame($user, $this->userProvider->get());
    }
}
