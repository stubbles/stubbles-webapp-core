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
class IoBindingModuleTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * set up test environment
     */
    public function setUp()
    {
        $_SERVER['REQUEST_METHOD']  = 'GET';
        $_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';
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
     * @test
     */
    public function bindsRequestAndResponseWhenCreatedWithoutSession()
    {
        $injector = $this->createInjector(IoBindingModule::createWithoutSession());
        $this->assertTrue($injector->hasExplicitBinding('net\stubbles\input\Request'));
        $this->assertTrue($injector->hasExplicitBinding('net\stubbles\input\web\WebRequest'));
        $this->assertTrue($injector->hasExplicitBinding('net\stubbles\webapp\response\Response'));
    }

    /**
     * @test
     */
    public function doesNotBindSessionWhenCreatedWithoutSession()
    {
        $injector = $this->createInjector(IoBindingModule::createWithoutSession());
        $this->assertFalse($injector->hasExplicitBinding('net\stubbles\webapp\session\Session'));
    }

    /**
     * @test
     * @expectedException net\stubbles\lang\exception\RuntimeException
     */
    public function doesNotAddSessionBindingScopeWhenCreatedWithoutSession()
    {
        $binder   = new Binder();
        $this->createInjector(IoBindingModule::createWithoutSession(), $binder);
        $binder->bind('\stdClass')
               ->to('\stdClass')
               ->inSession();

    }

    /**
     * @test
     */
    public function bindsRequestAndResponseWhenCreatedWithSession()
    {
        $injector = $this->createInjector(IoBindingModule::createWithSession());
        $this->assertTrue($injector->hasExplicitBinding('net\stubbles\input\Request'));
        $this->assertTrue($injector->hasExplicitBinding('net\stubbles\input\web\WebRequest'));
        $this->assertTrue($injector->hasExplicitBinding('net\stubbles\webapp\response\Response'));
    }

    /**
     * @test
     */
    public function bindSessionWhenCreatedWithSession()
    {
        $injector = $this->createInjector(IoBindingModule::createWithSession());
        $this->assertTrue($injector->hasExplicitBinding('net\stubbles\webapp\session\Session'));
    }

    /**
     * @test
     */
    public function addsSessionBindingScopeWhenCreatedWithSession()
    {
        $binder = new Binder();
        $this->createInjector(IoBindingModule::createWithSession(), $binder);
        try {
            $binder->bind('\stdClass')
                   ->to('\stdClass')
                   ->inSession();
        } catch (\net\stubbles\lang\exception\RuntimeException $re) {
            $this->fail($re->getMessage());
        }
    }

    /**
     * @since  1.7.0
     * @test
     */
    public function bindsSessionToNativeByDefault()
    {
        $injector = $this->createInjector(IoBindingModule::createWithSession());
        $this->assertInstanceOf('net\stubbles\webapp\session\WebSession',
                                $injector->getInstance('net\stubbles\webapp\session\Session'));
    }

    /**
     * @since  1.7.0
     * @test
     */
    public function bindsSessionToNoneDurable()
    {
        $injector = $this->createInjector(IoBindingModule::createWithSession()
                                                         ->useNoneDurableSession()
                    );
        $this->assertInstanceOf('net\stubbles\webapp\session\NullSession',
                                $injector->getInstance('net\stubbles\webapp\session\Session'));
    }

    /**
     * @since  1.7.0
     * @test
     */
    public function bindsSessionToNoneStoring()
    {
        $injector = $this->createInjector(IoBindingModule::createWithSession()
                                                         ->useNoneStoringSession()
                    );
        $this->assertInstanceOf('net\stubbles\webapp\session\NullSession',
                                $injector->getInstance('net\stubbles\webapp\session\Session'));
    }

    /**
     * @since  2.0.0
     * @test
     */
    public function bindsSessionToInstanceCreatedByClosure()
    {
        $mockSession = $this->getMock('net\stubbles\webapp\session\Session');
        $injector = $this->createInjector(IoBindingModule::createWithSession()
                                                         ->setSessionCreator(function() use($mockSession)
                                                                             {
                                                                                 return $mockSession;
                                                                             }
                                                           )
                    );
        $this->assertSame($mockSession,
                          $injector->getInstance('net\stubbles\webapp\session\Session'));
    }

    /**
     * @test
     */
    public function bindResponseToDifferentResponseClass()
    {
        $injector = $this->createInjector(IoBindingModule::createWithoutSession()
                                                         ->setResponseClass('org\stubbles\webapp\response\DummyResponse')
                    );
        $this->assertTrue($injector->hasBinding('net\stubbles\webapp\response\Response'));
        $this->assertInstanceOf('org\stubbles\webapp\response\DummyResponse',
                                $injector->getInstance('net\stubbles\webapp\response\Response')
        );
    }

    /**
     * @since  2.0.0
     * @test
     */
    public function bindsListOfMimeTypes()
    {
        $injector = $this->createInjector(IoBindingModule::createWithoutSession());
        $this->assertTrue($injector->hasConstant('net.stubbles.webapp.response.format.mimetypes'));
        $this->assertEquals(array('application/json',
                                  'text/json',
                                  'text/html',
                                  'text/plain',
                                  'text/xml',
                                  'application/xml',
                                  'application/rss+xml'
                            ),
                            $injector->getConstant('net.stubbles.webapp.response.format.mimetypes')
        );
    }

    /**
     * @since  2.0.0
     * @test
     */
    public function bindsListOfMimeTypesWithAdditionalMimeTypes()
    {
        $injector = $this->createInjector(IoBindingModule::createWithoutSession()
                                                         ->addFormatter('foo/bar', 'foo\BarFormatter')
                    );
        $this->assertTrue($injector->hasConstant('net.stubbles.webapp.response.format.mimetypes'));
        $this->assertEquals(array('application/json',
                                  'text/json',
                                  'text/html',
                                  'text/plain',
                                  'text/xml',
                                  'application/xml',
                                  'application/rss+xml',
                                  'foo/bar'
                            ),
                            $injector->getConstant('net.stubbles.webapp.response.format.mimetypes')
        );
    }

    /**
     * @since  2.0.0
     * @test
     */
    public function bindsFormattersForAllMimeTypes()
    {
        $injector = $this->createInjector(IoBindingModule::createWithoutSession());
        foreach ($injector->getConstant('net.stubbles.webapp.response.format.mimetypes') as $mimeType) {
            $this->assertTrue($injector->hasExplicitBinding('net\stubbles\webapp\response\format\Formatter', $mimeType));
        }
    }

    /**
     * @since  2.0.0
     * @test
     */
    public function bindsFormattersForAllMimeTypesWithAdditionalMimeTypes()
    {
        $injector = $this->createInjector(IoBindingModule::createWithoutSession()
                                                         ->addFormatter('foo/bar', 'foo\BarFormatter')
                    );
        foreach ($injector->getConstant('net.stubbles.webapp.response.format.mimetypes') as $mimeType) {
            $this->assertTrue($injector->hasExplicitBinding('net\stubbles\webapp\response\format\Formatter', $mimeType));
        }
    }
}
?>