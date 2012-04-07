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
/**
 * Tests for net\stubbles\webapp\session\NullSession.
 *
 * @since  2.0.0
 * @group  webapp
 * @group  webapp_session
 */
class NullSessionTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * instance to test
     *
     * @type  NullSession
     */
    private $nullSession;
    /**
     * mocked session id
     *
     * @type  \PHPUnit_Framework_MockObject_MockObject
     */
    private $mockSessionId;

    /**
     * set up test enviroment
     */
    public function setUp()
    {
        $this->mockSessionId = $this->getMock('net\\stubbles\\webapp\\session\\SessionId');
        $this->nullSession   = new NullSession($this->mockSessionId);
    }

    /**
     * @test
     */
    public function isAlwaysNew()
    {
        $this->assertTrue($this->nullSession->isNew());
    }

    /**
     * @test
     */
    public function idIsSessionId()
    {
        $this->mockSessionId->expects($this->once())
                            ->method('get')
                            ->will($this->returnValue('303'));
        $this->assertEquals('303', $this->nullSession->getId());
    }

    /**
     * @test
     */
    public function regenerateCreatesNewSessionId()
    {
        $this->mockSessionId->expects($this->once())
                            ->method('regenerate');
        $this->assertEquals($this->nullSession, $this->nullSession->regenerateId());
    }

    /**
     * @test
     */
    public function nameIsSessionIdName()
    {
        $this->mockSessionId->expects($this->once())
                            ->method('getName')
                            ->will($this->returnValue('foo'));
        $this->assertEquals('foo', $this->nullSession->getName());
    }

    /**
     * @test
     */
    public function isAlwaysValid()
    {
        $this->assertTrue($this->nullSession->isValid());
    }

    /**
     * @test
     */
    public function invalidateInvalidatesSessionId()
    {
        $this->mockSessionId->expects($this->once())
                            ->method('invalidate');
        $this->assertEquals($this->nullSession, $this->nullSession->invalidate());
    }

    /**
     * @test
     */
    public function hasNeverAnyValue()
    {
        $this->assertFalse($this->nullSession->hasValue('foo'));
    }

    /**
     * @test
     */
    public function neverReturnsValue()
    {
        $this->assertNull($this->nullSession->getValue('foo'));
    }

    /**
     * @test
     */
    public function alwaysReturnsDefaultValue()
    {
        $this->assertEquals('bar', $this->nullSession->getValue('foo', 'bar'));
    }

    /**
     * @test
     */
    public function putValueDoesNothing()
    {
        $this->assertEquals($this->nullSession, $this->nullSession->putValue('foo', 'bar'));
    }

    /**
     * @test
     */
    public function removeAlwaysTellsValueWasNotPresent()
    {
        $this->assertFalse($this->nullSession->removeValue('foo'));
    }

    /**
     * @test
     */
    public function hasNoValueKeys()
    {
        $this->assertEquals(array(), $this->nullSession->getValueKeys());
    }
}
?>