<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  net\stubbles\webapp
 */
namespace net\stubbles\webapp\ioc;
use net\stubbles\ioc\Binder;
/**
 * Tests for net\stubbles\webapp\ioc\IoBindingModule.
 *
 * @since  1.7.0
 * @group  ioc
 */
class IoBindingModuleHttpVersionTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * set up test environment
     */
    public function setUp()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
    }

    /**
     * clean up test environment
     */
    public function tearDown()
    {
        unset($_SERVER['REQUEST_METHOD']);
        unset($_SERVER['SERVER_PROTOCOL']);
    }

    /**
     * creates injector
     *
     * @return  net\stubbles\ioc\Injector
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
     * returns list of valid http versions
     *
     * @return array
     */
    public function getValidHttpVersions()
    {
        return array(array('1.0'),
                     array('1.1')
        );
    }

    /**
     * @test
     * @dataProvider  getValidHttpVersions
     */
    public function requestWithValidHttpVersionReceivesResponseWithSameHttpVersion($httpVersion)
    {
        $_SERVER['SERVER_PROTOCOL'] = 'HTTP/' . $httpVersion;
        $this->assertEquals($httpVersion,
                            $this->createInjector(IoBindingModule::createWithoutSession())
                                 ->getInstance('net\\stubbles\\webapp\\response\\Response')
                                 ->getVersion()
        );
    }

    /**
     * @test
     * @dataProvider  getValidHttpVersions
     */
    public function requestWithValidHttpVersionReceivesResponseWithStatusCode200($httpVersion)
    {
        $_SERVER['SERVER_PROTOCOL'] = 'HTTP/' . $httpVersion;
        $this->assertEquals(200,
                            $this->createInjector(IoBindingModule::createWithoutSession())
                                 ->getInstance('net\\stubbles\\webapp\\response\\Response')
                                 ->getStatusCode()
        );
    }

    /**
     * @test
     * @dataProvider  getValidHttpVersions
     */
    public function requestWithValidHttpVersionIsNotCancelled($httpVersion)
    {
        $_SERVER['SERVER_PROTOCOL'] = 'HTTP/' . $httpVersion;
        $this->assertFalse($this->createInjector(IoBindingModule::createWithoutSession())
                                ->getInstance('net\\stubbles\\input\\web\\WebRequest')
                                ->isCancelled()
        );
    }

    /**
     * returns list of valid http versions
     *
     * @return array
     */
    public function getInvalidHttpVersions()
    {
        return array(array('HTTP/1.2'),
                     array('HTTP/0.9'),
                     array('invalid')
        );
    }

    /**
     * @test
     * @dataProvider  getInvalidHttpVersions
     */
    public function requestWithInvalidHttpVersionReceivesResponseWithDefaultHttpVersion($httpVersion)
    {
        $_SERVER['SERVER_PROTOCOL'] = $httpVersion;
        $this->assertEquals('1.1',
                            $this->createInjector(IoBindingModule::createWithoutSession())
                                 ->getInstance('net\\stubbles\\webapp\\response\\Response')
                                 ->getVersion()
        );
    }

    /**
     * @test
     * @dataProvider  getInvalidHttpVersions
     */
    public function requestWithInvalidHttpVersionReceivesResponseWithStatusCode505($httpVersion)
    {
        $_SERVER['SERVER_PROTOCOL'] = $httpVersion;
        $this->assertEquals(505,
                            $this->createInjector(IoBindingModule::createWithoutSession())
                                 ->getInstance('net\\stubbles\\webapp\\response\\Response')
                                 ->getStatusCode()
        );
    }

    /**
     * @test
     * @dataProvider  getInvalidHttpVersions
     */
    public function requestWithInvalidHttpVersionIsCancelled($httpVersion)
    {
        $_SERVER['SERVER_PROTOCOL'] = $httpVersion;
        $this->assertTrue($this->createInjector(IoBindingModule::createWithoutSession())
                               ->getInstance('net\\stubbles\\input\\web\\WebRequest')
                               ->isCancelled()
        );
    }
}
?>