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
    public function bindsRequestAndResponse()
    {
        $injector = $this->createInjector(new IoBindingModule());
        $this->assertTrue($injector->hasExplicitBinding('stubbles\input\Request'));
        $this->assertTrue($injector->hasExplicitBinding('stubbles\input\web\WebRequest'));
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
        } catch (RuntimeException $re) {
            $this->fail($re->getMessage());
        }

        $injector = $binder->getInjector();
        $this->assertSame(
                $injector->getInstance('\stdClass'),
                $injector->getInstance('\stdClass')
        );
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
        $injector = $this->createInjector(new IoBindingModule());
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
        $injector = $this->createInjector(
                (new IoBindingModule())->addFormatter('foo/bar', 'foo\BarFormatter')
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
        $injector = $this->createInjector(new IoBindingModule());
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
        $injector = $this->createInjector(
                (new IoBindingModule())->addFormatter('foo/bar', 'foo\BarFormatter')
        );
        foreach ($injector->getConstant('stubbles.webapp.response.format.mimetypes') as $mimeType) {
            $this->assertTrue($injector->hasExplicitBinding('stubbles\webapp\response\format\Formatter', $mimeType));
        }
    }
}
