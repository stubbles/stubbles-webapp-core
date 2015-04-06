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
    private $session;
    /**
     * mocked request instance
     *
     * @type  \PHPUnit_Framework_MockObject_MockObject
     */
    private $request;
    /**
     * mocked response instance
     *
     * @type  \PHPUnit_Framework_MockObject_MockObject
     */
    private $response;

    /**
     * set up the test environment
     */
    public function setUp()
    {
        $root = vfsStream::setup();
        vfsStream::newFile('index.html')->withContent('this is index.html')->at($root);
        vfsStream::newFile('foo.html')->withContent('this is foo.html')->at($root);
        $this->session  = $this->getMock('stubbles\webapp\session\Session');
        $this->request  = $this->getMock('stubbles\webapp\Request');
        $this->response = $this->getMock('stubbles\webapp\Response');
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
        $this->request->expects(any())
                ->method('userAgent')
                ->will(returnValue(new UserAgent('foo', $acceptsCookies)));
    }

    /**
     * @test
     */
    public function requestForNonExistingFileWritesNotFoundResponse()
    {
        $error = Error::notFound();
        $this->response->method('notFound')->will(returnValue($error));
        assertSame(
                $error,
                $this->sessionBasedHtmlFilePassThrough->resolve(
                        $this->request,
                        $this->response,
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
                        $this->request,
                        $this->response,
                        new UriPath('/', '/foo.html')
                )
        );
    }

    /**
     * attaches mock session to mock request
     */
    private function attachMockSession()
    {
        $this->request->expects(any())
                ->method('hasSessionAttached')
                ->will(returnValue(true));
        $this->request->expects(any())
                ->method('attachedSession')
                ->will(returnValue($this->session));
    }

    /**
     * @test
     */
    public function fallsBackToIndexFileIfRequestForSlashOnly()
    {
        $this->userAgentAcceptsCookies(true);
        $this->attachMockSession();
        $this->session->expects(once())
                ->method('putValue')
                ->with(equalTo('stubbles.webapp.lastPage'), equalTo('index.html'));
        assertEquals(
                'this is index.html',
                $this->sessionBasedHtmlFilePassThrough->resolve(
                        $this->request,
                        $this->response,
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
        $this->session->expects(never())->method('name');
        $this->session->expects(never())->method('id');
        assertEquals(
                'this is foo.html',
                $this->sessionBasedHtmlFilePassThrough->resolve(
                        $this->request,
                        $this->response,
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
        $this->session->expects(once())->method('name');
        $this->session->expects(once())->method('id');
        assertEquals(
                'this is foo.html',
                $this->sessionBasedHtmlFilePassThrough->resolve(
                        $this->request,
                        $this->response,
                        new UriPath('/', '/foo.html')
                )
        );
    }
}
