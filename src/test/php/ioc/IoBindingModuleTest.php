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
/**
 * Tests for nstubbles\webapp\ioc\IoBindingModule.
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
    public function bindsRequestAndResponseWhenCreatedWithoutSession()
    {
        $injector = $this->createInjector(IoBindingModule::createWithoutSession());
        $this->assertTrue($injector->hasExplicitBinding('stubbles\input\Request'));
        $this->assertTrue($injector->hasExplicitBinding('stubbles\input\web\WebRequest'));
        $this->assertTrue($injector->hasExplicitBinding('stubbles\webapp\response\Response'));
    }

    /**
     * @test
     */
    public function doesNotBindSessionWhenCreatedWithoutSession()
    {
        $injector = $this->createInjector(IoBindingModule::createWithoutSession());
        $this->assertFalse($injector->hasExplicitBinding('stubbles\webapp\session\Session'));
    }

    /**
     * @test
     * @expectedException  stubbles\lang\exception\RuntimeException
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
        $this->assertTrue($injector->hasExplicitBinding('stubbles\input\Request'));
        $this->assertTrue($injector->hasExplicitBinding('stubbles\input\web\WebRequest'));
        $this->assertTrue($injector->hasExplicitBinding('stubbles\webapp\response\Response'));
    }

    /**
     * @test
     */
    public function bindSessionWhenCreatedWithSession()
    {
        $injector = $this->createInjector(IoBindingModule::createWithSession());
        $this->assertTrue($injector->hasExplicitBinding('stubbles\webapp\session\Session'));
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
        } catch (RuntimeException $re) {
            $this->fail($re->getMessage());
        }

        $this->addToAssertionCount(1);
    }

    /**
     * @since  1.7.0
     * @test
     */
    public function bindsSessionToNativeByDefault()
    {
        $injector = $this->createInjector(IoBindingModule::createWithSession());
        $this->assertInstanceOf('stubbles\webapp\session\WebSession',
                                $injector->getInstance('stubbles\webapp\session\Session'));
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
        $this->assertInstanceOf('stubbles\webapp\session\NullSession',
                                $injector->getInstance('stubbles\webapp\session\Session'));
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
        $this->assertInstanceOf('stubbles\webapp\session\NullSession',
                                $injector->getInstance('stubbles\webapp\session\Session'));
    }

    /**
     * @since  2.0.0
     * @test
     */
    public function bindsSessionToInstanceCreatedByClosure()
    {
        $mockSession = $this->getMock('stubbles\webapp\session\Session');
        $injector = $this->createInjector(IoBindingModule::createWithSession()
                                                         ->setSessionCreator(function() use($mockSession)
                                                                             {
                                                                                 return $mockSession;
                                                                             }
                                                           )
                    );
        $this->assertSame($mockSession,
                          $injector->getInstance('stubbles\webapp\session\Session'));
    }

    /**
     * @test
     */
    public function bindResponseToDifferentResponseClass()
    {
        $otherResponseClass = get_class($this->getMock('stubbles\webapp\response\Response'));
        $injector = $this->createInjector(IoBindingModule::createWithoutSession()
                                                         ->setResponseClass($otherResponseClass)
                    );
        $this->assertTrue($injector->hasBinding('stubbles\webapp\response\Response'));
        $this->assertInstanceOf($otherResponseClass,
                                $injector->getInstance('stubbles\webapp\response\Response')
        );
    }

    /**
     * @since  2.0.0
     * @test
     */
    public function bindsListOfMimeTypes()
    {
        $injector = $this->createInjector(IoBindingModule::createWithoutSession());
        $this->assertTrue($injector->hasConstant('stubbles.webapp.response.format.mimetypes'));
        $this->assertEquals(['application/json',
                             'text/json',
                             'text/html',
                             'text/plain',
                             'text/xml',
                             'application/xml',
                             'application/rss+xml'
                            ],
                            $injector->getConstant('stubbles.webapp.response.format.mimetypes')
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
        $this->assertTrue($injector->hasConstant('stubbles.webapp.response.format.mimetypes'));
        $this->assertEquals(['application/json',
                             'text/json',
                             'text/html',
                             'text/plain',
                             'foo/bar',
                             'text/xml',
                             'application/xml',
                             'application/rss+xml'
                            ],
                            $injector->getConstant('stubbles.webapp.response.format.mimetypes')
        );
    }

    /**
     * @since  2.0.0
     * @test
     */
    public function bindsFormattersForAllMimeTypes()
    {
        $injector = $this->createInjector(IoBindingModule::createWithoutSession());
        foreach ($injector->getConstant('stubbles.webapp.response.format.mimetypes') as $mimeType) {
            $this->assertTrue($injector->hasExplicitBinding('stubbles\webapp\response\format\Formatter', $mimeType));
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
        foreach ($injector->getConstant('stubbles.webapp.response.format.mimetypes') as $mimeType) {
            $this->assertTrue($injector->hasExplicitBinding('stubbles\webapp\response\format\Formatter', $mimeType));
        }
    }
}
