<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  stubbles\webapp
 */
namespace stubbles\webapp\ioc;
use stubbles\ioc\Binder;
use stubbles\lang\exception\RuntimeException;
use stubbles\webapp\websession;
/**
 * Tests for stubbles\webapp\ioc\IoBindingModule.
 *
 * @since  1.7.0
 * @group  ioc
 */
class IoBindingModuleTest extends \PHPUnit_Framework_TestCase
{
    /**
     * set up test environment
     */
    public function setUp()
    {
        IoBindingModule::reset();
        $_SERVER['REQUEST_METHOD']  = 'GET';
        $_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';
    }

    /**
     * clean up test environment
     */
    public function tearDown()
    {
        IoBindingModule::reset();
        unset($_SERVER['REQUEST_METHOD']);
        unset($_SERVER['SERVER_PROTOCOL']);
    }

    /**
     * creates injector
     *
     * @return  stubbles\ioc\Injector
     */
    private function createInjector(IoBindingModule $ioBindingModule, Binder $binder = null)
    {
        if (null === $binder) {
            $binder = new Binder();
        }

        $ioBindingModule->configure($binder);
        return $binder->getInjector();
    }

    /**
     * @test
     */
    public function bindsRequestAndResponse()
    {
        $injector = $this->createInjector(new IoBindingModule());
        $this->assertTrue($injector->hasExplicitBinding('stubbles\input\Request'));
        $this->assertTrue($injector->hasExplicitBinding('stubbles\webapp\request\Request'));
        $this->assertTrue($injector->hasExplicitBinding('stubbles\webapp\response\Response'));
    }

    /**
     * @since  2.0.0
     * @test
     */
    public function bindsSessionToInstanceCreatedByCallable()
    {
        $mockSession = $this->getMock('stubbles\webapp\session\Session');
        $injector = $this->createInjector(
                new IoBindingModule(function() use($mockSession) { return $mockSession; })
        );
        $this->assertSame($mockSession,
                          $injector->getInstance('stubbles\webapp\session\Session'));
    }

    /**
     * @test
     */
    public function doesNotBindSessionWhenCreatedWithoutSessionCreator()
    {
        $injector = $this->createInjector(new IoBindingModule());
        $this->assertFalse($injector->hasExplicitBinding('stubbles\webapp\session\Session'));
    }

    /**
     * @since  5.0.0
     * @test
     */
    public function initializesSessionScopeWhenSessionBound()
    {
        $binder      = new Binder();
        (new IoBindingModule(websession\noneDurable()))->configure($binder);
        try {
            $binder->bind('\stdClass')
                   ->to('\stdClass')
                   ->inSession();
            $injector = $binder->getInjector();
            $this->assertSame(
                    $injector->getInstance('\stdClass'),
                    $injector->getInstance('\stdClass')
            );
        } catch (RuntimeException $re) {
            $this->fail($re->getMessage());
        }
    }

    /**
     * @test
     */
    public function bindResponseToDifferentResponseClass()
    {
        $otherResponseClass = get_class($this->getMock('stubbles\webapp\response\Response'));
        $injector = $this->createInjector(
                (new IoBindingModule())->setResponseClass($otherResponseClass)
        );
        $this->assertTrue($injector->hasBinding('stubbles\webapp\response\Response'));
        $this->assertInstanceOf(
                $otherResponseClass,
                $injector->getInstance('stubbles\webapp\response\Response')
        );
    }

    /**
     * @test
     * @since  5.1.2
     */
    public function createInstanceMarksInitializedAsTrue()
    {
        $iobindingingModule = new IoBindingModule();
        $this->assertTrue(IoBindingModule::initialized());
    }
}
