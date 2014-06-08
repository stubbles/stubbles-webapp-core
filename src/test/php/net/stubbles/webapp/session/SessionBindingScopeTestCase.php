<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  net\stubbles\webapp
 */
namespace net\stubbles\webapp\session;
use stubbles\lang\reflect\ReflectionClass;
/**
 * Tests for net\stubbles\webapp\session\SessionBindingScope.
 *
 * @group  session
 */
class SessionBindingScopeTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * instance to test
     *
     * @type  SessionScope
     */
    private $sessionScope;
    /**
     * mocked session id
     *
     * @type  \PHPUnit_Framework_MockObject_MockObject
     */
    private $mockSession;
    /**
     * mocked injection provider
     *
     * @type  \PHPUnit_Framework_MockObject_MockObject
     */
    private $mockInjectionProvider;
    /**
     * reflection class for instance to create
     *
     * @type  ReflectionClass
     */
    private $refClass;

    /**
     * set up test enviroment
     */
    public function setUp()
    {
        $this->mockSession           = $this->getMock('net\\stubbles\\webapp\\session\\Session');
        $this->sessionScope          = new SessionBindingScope($this->mockSession);
        $this->mockInjectionProvider = $this->getMock('stubbles\ioc\InjectionProvider');
        $this->refClass              = new ReflectionClass('\\stdClass');
    }

    /**
     * @test
     */
    public function returnsInstanceFromSessionIfPresent()
    {
        $instance = new \stdClass();
        $this->mockSession->expects($this->once())
                          ->method('hasValue')
                          ->will($this->returnValue(true));
        $this->mockSession->expects($this->once())
                          ->method('getValue')
                          ->will($this->returnValue($instance));
        $this->mockInjectionProvider->expects($this->never())
                          ->method('get');
        $this->assertSame($instance,
                          $this->sessionScope->getInstance($this->refClass,
                                                           $this->mockInjectionProvider
                                               )
        );
    }

    /**
     * @test
     */
    public function createsInstanceIfNotPresent()
    {
        $instance = new \stdClass();
        $this->mockSession->expects($this->once())
                          ->method('hasValue')
                          ->will($this->returnValue(false));
        $this->mockSession->expects($this->never())
                          ->method('getValue');
        $this->mockInjectionProvider->expects($this->once())
                          ->method('get')
                          ->will($this->returnValue($instance));
        $this->assertSame($instance,
                          $this->sessionScope->getInstance($this->refClass,
                                                           $this->mockInjectionProvider
                                               )
        );
    }
}
