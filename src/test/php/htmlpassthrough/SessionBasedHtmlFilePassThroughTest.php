<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  stubbles\webapp
 */
namespace stubbles\webapp\htmlpassthrough;
use org\bovigo\vfs\vfsStream;
use stubbles\webapp\UriPath;
use stubbles\webapp\request\UserAgent;
use stubbles\webapp\response\Error;
/**
 * Test for stubbles\webapp\htmlpassthrough\SessionBasedHtmlFilePassThrough.
 *
 * @group  processor
 */
class SessionBasedHtmlFilePassThroughTest extends \PHPUnit_Framework_TestCase
{
    /**
     * instance to test
     *
     * @type  \stubbles\webapp\htmlpassthrough\SessionBasedHtmlFilePassThrough
     */
    private $sessionBasedHtmlFilePassThrough;
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
        $this->mockSession             = $this->getMock('stubbles\webapp\session\Session');
        $this->mockRequest             = $this->getMock('stubbles\webapp\Request');
        $this->mockResponse            = $this->getMock('stubbles\webapp\Response');
        $this->sessionBasedHtmlFilePassThrough = new SessionBasedHtmlFilePassThrough(
                vfsStream::url('root')
        );
    }

    /**
     * @test
     */
    public function functionReturnsClassName()
    {
        assertEquals(
                get_class($this->sessionBasedHtmlFilePassThrough),
                \stubbles\webapp\sessionBasedHtmlPassThrough()
        );
    }

    /**
     * @param  bool  $acceptsCookies
     */
    private function userAgentAcceptsCookies($acceptsCookies)
    {
        $this->mockRequest->expects($this->once())
                ->method('userAgent')
                ->will($this->returnValue(new UserAgent('foo', $acceptsCookies)));
    }

    /**
     * @test
     */
    public function requestForNonExistingFileWritesNotFoundResponse()
    {
        $error = Error::notFound();
        $this->mockResponse->expects($this->once())
                           ->method('notFound')
                           ->will($this->returnValue($error));
        assertSame(
                $error,
                $this->sessionBasedHtmlFilePassThrough->resolve(
                        $this->mockRequest,
                        $this->mockResponse,
                        new UriPath('/', '/doesNotExist.html')
                )
        );
    }

    /**
     * @test
     */
    public function selectsAvailableRoute()
    {
        $this->userAgentAcceptsCookies(true);
        assertEquals(
                'this is foo.html',
                $this->sessionBasedHtmlFilePassThrough->resolve(
                        $this->mockRequest,
                        $this->mockResponse,
                        new UriPath('/', '/foo.html')
                )
        );
    }

    /**
     * attaches mock session to mock request
     */
    private function attachMockSession()
    {
        $this->mockRequest->expects($this->any())
                ->method('hasSessionAttached')
                ->will($this->returnValue(true));
        $this->mockRequest->expects($this->any())
                ->method('attachedSession')
                ->will($this->returnValue($this->mockSession));
    }

    /**
     * @test
     */
    public function fallsBackToIndexFileIfRequestForSlashOnly()
    {
        $this->userAgentAcceptsCookies(true);
        $this->attachMockSession();
        $this->mockSession->expects($this->once())
                          ->method('putValue')
                          ->with($this->equalTo('stubbles.webapp.lastPage'), $this->equalTo('index.html'));
        assertEquals(
                'this is index.html',
                $this->sessionBasedHtmlFilePassThrough->resolve(
                        $this->mockRequest,
                        $this->mockResponse,
                        new UriPath('/', '/')
                )
        );
    }

    /**
     * @test
     */
    public function writesNoSessionDataToOutputIfCookiesEnabled()
    {
        $this->userAgentAcceptsCookies(true);
        $this->attachMockSession();
        $this->mockSession->expects($this->never())
                          ->method('name');
        $this->mockSession->expects($this->never())
                          ->method('id');
        assertEquals(
                'this is foo.html',
                $this->sessionBasedHtmlFilePassThrough->resolve(
                        $this->mockRequest,
                        $this->mockResponse,
                        new UriPath('/', '/foo.html')
                )
        );
    }

    /**
     * @test
     */
    public function writesSessionDataToOutputIfCookiesDisabled()
    {
        $this->userAgentAcceptsCookies(false);
        $this->attachMockSession();
        $this->mockSession->expects($this->once())
                          ->method('name');
        $this->mockSession->expects($this->once())
                          ->method('id');
        assertEquals(
                'this is foo.html',
                $this->sessionBasedHtmlFilePassThrough->resolve(
                        $this->mockRequest,
                        $this->mockResponse,
                        new UriPath('/', '/foo.html')
                )
        );
    }
}
