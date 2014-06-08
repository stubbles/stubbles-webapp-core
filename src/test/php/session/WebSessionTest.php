<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  stubbles\webapp
 */
namespace stubbles\webapp\session;
/**
 * Tests for stubbles\webapp\session\WebSession.
 *
 * @group  session
 */
class WebSessionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * mocked session storage
     *
     * @type  \PHPUnit_Framework_MockObject_MockObject
     */
    private $mockSessionStorage;
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
        $this->mockSessionStorage = $this->getMock('stubbles\webapp\session\SessionStorage');
        $this->mockSessionId      = $this->getMock('stubbles\webapp\session\SessionId');
    }

    /**
     * creates valid web session
     *
     * @param   string  $givenFingerprint
     * @param   string  $storageFingerprint
     * @return  WebSession
     */
    private function createWebSession($givenFingerprint = 'aFingerprint',
                                      $storageFingerprint = 'aFingerprint')
    {
        $this->mockSessionStorage->expects($this->any())
                                 ->method('hasValue')
                                 ->will($this->returnValue(null !== $storageFingerprint));
        $this->mockSessionStorage->expects($this->any())
                                 ->method('getValue')
                                 ->will($this->returnValue($storageFingerprint));
        return new WebSession($this->mockSessionStorage,
                              $this->mockSessionId,
                              $givenFingerprint
        );
    }

    /**
     * @test
     */
    public function isNewWhenSessionContainsNoFingerprint()
    {
        $this->assertTrue($this->createWebSession('aFingerprint', null)->isNew());
    }

    /**
     * @test
     */
    public function regeneratesSessionIdWhenSessionIsNew()
    {
        $this->mockSessionId->expects($this->once())
                            ->method('regenerate');
        $this->createWebSession('aFingerprint', null);
    }

    /**
     * @test
     */
    public function storesFingerPrintWhenSessionIsNew()
    {
        $this->mockSessionStorage->expects($this->once())
                                 ->method('putValue')
                                 ->with($this->equalTo(Session::FINGERPRINT),
                                        $this->equalTo('aFingerprint')
                                   );
        $this->createWebSession('aFingerprint', null);
    }

    /**
     * @test
     */
    public function regeneratesSessionIdWhenSessionIsHijacked()
    {
        $this->mockSessionId->expects($this->once())
                            ->method('regenerate');
        $this->createWebSession('otherFingerprint');
    }

    /**
     * @test
     */
    public function clearsSessionDataWhenSessionIsHijacked()
    {
        $this->mockSessionStorage->expects($this->once())
                                 ->method('clear');
        $this->createWebSession('otherFingerprint');
    }

    /**
     * @test
     */
    public function storesGivenFingerPrintWhenSessionIsHijacked()
    {
        $this->mockSessionStorage->expects($this->once())
                                 ->method('putValue')
                                 ->with($this->equalTo(Session::FINGERPRINT),
                                        $this->equalTo('otherFingerprint')
                                   );
        $this->createWebSession('otherFingerprint');
    }

    /**
     * @test
     */
    public function idIsSessionId()
    {
        $this->mockSessionId->expects($this->once())
                            ->method('get')
                            ->will($this->returnValue('303'));
        $this->assertEquals('303', $this->createWebSession()->getId());
    }

    /**
     * @test
     */
    public function regenerateCreatesNewSessionId()
    {
        $webSession = $this->createWebSession();
        $this->mockSessionId->expects($this->once())
                            ->method('regenerate');
        $this->assertEquals($webSession, $webSession->regenerateId());
    }

    /**
     * @test
     */
    public function nameIsSessionIdName()
    {
        $this->mockSessionId->expects($this->once())
                            ->method('getName')
                            ->will($this->returnValue('foo'));
        $this->assertEquals('foo', $this->createWebSession()->getName());
    }

    /**
     * @test
     */
    public function isValidByDefault()
    {
        $this->assertTrue($this->createWebSession()->isValid());
    }

    /**
     * creates invalid web session
     *
     * A web session is invalid if the fingerprint is removed. This is simulated
     * in this case by simply saying that no fingerprint is present on the
     * second request to the storage.
     *
     * @return  WebSession
     */
    private function createInvalidWebSession()
    {
        $this->mockSessionStorage->expects($this->exactly(2))
                                 ->method('hasValue')
                                 ->will($this->onConsecutiveCalls(true, false));
        $this->mockSessionStorage->expects($this->any())
                                 ->method('getValue')
                                 ->will($this->returnValue('aFingerprint'));
        return new WebSession($this->mockSessionStorage,
                              $this->mockSessionId,
                              'aFingerprint'
        );
    }
    /**
     * @test
     */
    public function isNotValidWhenFingerprintIsRemoved()
    {
        $this->assertFalse($this->createInvalidWebSession()->isValid());
    }

    /**
     * @test
     */
    public function invalidateClearsSessionData()
    {
        $webSession = $this->createWebSession();
        $this->mockSessionStorage->expects($this->once())
                                 ->method('clear');
        $this->assertEquals($webSession, $webSession->invalidate());
    }

    /**
     * @test
     */
    public function invalidateInvalidatesSessionId()
    {
        $webSession = $this->createWebSession();
        $this->mockSessionId->expects($this->once())
                            ->method('invalidate');
        $this->assertEquals($webSession, $webSession->invalidate());
    }

    /**
     * @test
     */
    public function hasValueReturnsFalseOnInvalidSession()
    {
        $this->assertFalse($this->createInvalidWebSession()->hasValue('foo'));
    }

    /**
     * creates valid web session with value expectations
     *
     * @param   string  $value
     * @return  WebSession
     */
    private function createWebSessionWithValues($value = null)
    {
        $this->mockSessionStorage->expects($this->any())
                                 ->method('hasValue')
                                 ->will($this->onConsecutiveCalls(true, true, null !== $value));
        $this->mockSessionStorage->expects($this->any())
                                 ->method('getValue')
                                 ->will($this->onConsecutiveCalls('aFingerprint', $value));
        return new WebSession($this->mockSessionStorage,
                              $this->mockSessionId,
                              'aFingerprint'
        );
    }

    /**
     * @test
     */
    public function hasNeverAnyValueByDefault()
    {
        $this->assertFalse($this->createWebSessionWithValues()->hasValue('foo'));
    }

    /**
     * @test
     */
    public function hasValueIfStored()
    {
        $this->assertTrue($this->createWebSessionWithValues('bar')->hasValue('foo'));
    }

    /**
     * @test
     */
    public function getValueReturnsNullIfValueNotStored()
    {
        $this->assertNull($this->createWebSessionWithValues()->getValue('foo'));
    }

    /**
     * @test
     */
    public function getValueReturnsDefaultIfValueNotStoredAndDefaultGiven()
    {
        $this->assertEquals('bar',
                            $this->createWebSessionWithValues()
                                 ->getValue('foo', 'bar')
        );
    }

    /**
     * @test
     */
    public function getValueReturnsStoredValue()
    {
        $this->assertEquals('baz',
                            $this->createWebSessionWithValues('baz')
                                 ->getValue('foo', 'bar')
        );
    }

    /**
     * @test
     * @expectedException  stubbles\lang\exception\IllegalStateException
     */
    public function getValueThrowsIllegalStateExceptionFalseOnInvalidSession()
    {
        $this->createInvalidWebSession()->getValue('foo');
    }

    /**
     * @test
     */
    public function putValueStoresValue()
    {
        $this->mockSessionStorage->expects($this->once())
                                 ->method('putValue')
                                 ->with($this->equalTo('foo'), $this->equalTo('bar'));
        $webSession = $this->createWebSession();
        $this->assertEquals($webSession, $webSession->putValue('foo', 'bar'));
    }

    /**
     * @test
     * @expectedException  stubbles\lang\exception\IllegalStateException
     */
    public function putValueThrowsIllegalStateExceptionFalseOnInvalidSession()
    {
        $this->createInvalidWebSession()->putValue('foo', 'bar');
    }

    /**
     * @test
     */
    public function removeReturnsFalseIfValueWasNotStoredBefore()
    {
        $this->assertFalse($this->createWebSessionWithValues()->removeValue('foo'));
    }

    /**
     * @test
     */
    public function removeReturnsTrueIfValueWasNotStoredBefore()
    {
        $this->mockSessionStorage->expects($this->once())
                                 ->method('removeValue')
                                 ->with($this->equalTo('foo'));
        $this->assertTrue($this->createWebSessionWithValues('bar')->removeValue('foo'));
    }

    /**
     * @test
     * @expectedException  stubbles\lang\exception\IllegalStateException
     */
    public function removeValueThrowsIllegalStateExceptionFalseOnInvalidSession()
    {
        $this->createInvalidWebSession()->removeValue('foo');
    }

    /**
     * @test
     */
    public function getValueKeysReturnsAllKeysWithoutFingerprint()
    {
        $this->mockSessionStorage->expects($this->once())
                                 ->method('getValueKeys')
                                 ->will($this->returnValue([Session::FINGERPRINT,
                                                            'foo'
                                                           ]

                                        )
                                   );
        $this->assertEquals(['foo'],
                            $this->createWebSession()->getValueKeys()
        );
    }

    /**
     * @test
     * @expectedException  stubbles\lang\exception\IllegalStateException
     */
    public function getValueKeysThrowsIllegalStateExceptionFalseOnInvalidSession()
    {
        $this->createInvalidWebSession()->getValueKeys();
    }
}
