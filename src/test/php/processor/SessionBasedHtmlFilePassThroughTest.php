<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  stubbles\webapp
 */
namespace stubbles\webapp\processor;
use org\bovigo\vfs\vfsStream;
use stubbles\lang;
use stubbles\webapp\UriPath;
/**
 * Test for stubbles\webapp\processor\SessionBasedHtmlFilePassThrough.
 *
 * @group  processor
 */
class SessionBasedHtmlFilePassThroughTest extends \PHPUnit_Framework_TestCase
{
    /**
     * instance to test
     *
     * @type  SessionBasedHtmlFilePassThrough
     */
    private $sessionBasedHtmlFilePassThrough;
    /**
     * mocked request instance
     *
     * @type  \PHPUnit_Framework_MockObject_MockObject
     */
    private $mockuserAgent;
    /**
     * mocked session to use
     *
     * @type  \PHPUnit_Framework_MockObject_MockObject
     */
    private $mockSession;
    /**
     * mocked request instance
     *
     * @type  \PHPUnit_Framework_MockObject_MockObject
     */
    private $mockRequest;
    /**
     * mocked response instance
     *
     * @type  \PHPUnit_Framework_MockObject_MockObject
     */
    private $mockResponse;

    /**
     * set up the test environment
     */
    public function setUp()
    {
        $root = vfsStream::setup();
        vfsStream::newFile('index.html')->withContent('this is index.html')->at($root);
        vfsStream::newFile('foo.html')->withContent('this is foo.html')->at($root);
        $this->mockuserAgent           = $this->getMockBuilder('stubbles\input\web\useragent\UserAgent')
                                              ->disableOriginalConstructor()
                                              ->getMock();
        $this->mockSession             = $this->getMock('stubbles\webapp\session\Session');
        $this->mockRequest             = $this->getMock('stubbles\input\web\WebRequest');
        $this->mockResponse            = $this->getMock('stubbles\webapp\response\Response');
        $this->sessionBasedHtmlFilePassThrough = new SessionBasedHtmlFilePassThrough(
                vfsStream::url('root'),
                $this->mockuserAgent,
                $this->mockSession
        );
    }

    /**
     * @test
     */
    public function functionReturnsClassName()
    {
        $this->assertEquals(
                get_class($this->sessionBasedHtmlFilePassThrough),
                \stubbles\webapp\sessionBasedHtmlPassThrough()
        );
    }

    /**
     * @test
     */
    public function annotationsPresentOnConstructor()
    {
        $constructor = lang\reflectConstructor($this->sessionBasedHtmlFilePassThrough);
        $this->assertTrue($constructor->hasAnnotation('Inject'));

        $refParams = $constructor->getParameters();
        $this->assertTrue($refParams[0]->hasAnnotation('Named'));
        $this->assertEquals('stubbles.pages.path', $refParams[0]->getAnnotation('Named')->getName());
    }

    /**
     * @test
     */
    public function requestForNonExistingFileWritesNotFoundResponse()
    {
        $this->mockResponse->expects($this->once())
                           ->method('notFound');
        $this->mockResponse->expects($this->never())
                           ->method('write');
        $this->mockSession->expects($this->never())
                          ->method('putValue');
        $this->sessionBasedHtmlFilePassThrough->process(
                $this->mockRequest,
                $this->mockResponse,
                new UriPath('/', '/doesNotExist.html')
        );
    }

    /**
     * @test
     */
    public function selectsAvailableRoute()
    {
        $this->mockResponse->expects($this->once())
                           ->method('write')
                           ->with($this->equalTo('this is foo.html'));
        $this->sessionBasedHtmlFilePassThrough->process(
                $this->mockRequest,
                $this->mockResponse,
                new UriPath('/', '/foo.html')
        );
    }

    /**
     * @test
     */
    public function fallsBackToIndexFileIfRequestForSlashOnly()
    {
        $this->mockResponse->expects($this->once())
                           ->method('write')
                           ->with($this->equalTo('this is index.html'));
        $this->mockSession->expects($this->once())
                          ->method('putValue')
                          ->with($this->equalTo('stubbles.webapp.lastPage'), $this->equalTo('index.html'));
        $this->sessionBasedHtmlFilePassThrough->process(
                $this->mockRequest,
                $this->mockResponse,
                new UriPath('/', '/')
        );
    }

    /**
     * @test
     */
    public function writesNoSessionDataToOutputIfCookiesEnabled()
    {
        $this->mockuserAgent->expects($this->once())
                            ->method('acceptsCookies')
                            ->will($this->returnValue(true));
        $this->mockSession->expects($this->never())
                          ->method('name');
        $this->mockSession->expects($this->never())
                          ->method('id');
        $this->mockResponse->expects($this->once())
                           ->method('write')
                           ->with($this->equalTo('this is foo.html'));
        $this->sessionBasedHtmlFilePassThrough->process(
                $this->mockRequest,
                $this->mockResponse,
                new UriPath('/', '/foo.html')
        );
    }

    /**
     * @test
     */
    public function writesSessionDataToOutputIfCookiesDisabled()
    {
        $this->mockuserAgent->expects($this->once())
                            ->method('acceptsCookies')
                            ->will($this->returnValue(false));
        $this->mockSession->expects($this->once())
                          ->method('name');
        $this->mockSession->expects($this->once())
                          ->method('id');
        $this->mockResponse->expects($this->once())
                           ->method('write')
                           ->with($this->equalTo('this is foo.html'));
        $this->sessionBasedHtmlFilePassThrough->process(
                $this->mockRequest,
                $this->mockResponse,
                new UriPath('/', '/foo.html')
        );
    }
}
