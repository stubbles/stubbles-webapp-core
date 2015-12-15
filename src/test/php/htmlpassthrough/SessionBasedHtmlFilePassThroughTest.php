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
use bovigo\callmap\NewInstance;
use org\bovigo\vfs\vfsStream;
use stubbles\webapp\Request;
use stubbles\webapp\Response;
use stubbles\webapp\UriPath;
use stubbles\webapp\request\UserAgent;
use stubbles\webapp\response\Error;
use stubbles\webapp\session\Session;

use function bovigo\callmap\verify;
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
     * @type  \bovigo\callmap\Proxy
     */
    private $session;
    /**
     * mocked request instance
     *
     * @type  \bovigo\callmap\Proxy
     */
    private $request;
    /**
     * mocked response instance
     *
     * @type  \bovigo\callmap\Proxy
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
        $this->session  = NewInstance::of(Session::class);
        $this->request  = NewInstance::of(Request::class);
        $this->response = NewInstance::of(Response::class);
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
                SessionBasedHtmlFilePassThrough::class,
                \stubbles\webapp\sessionBasedHtmlPassThrough()
        );
    }

    /**
     * @test
     */
    public function requestForNonExistingFileWritesNotFoundResponse()
    {
        $error = Error::notFound();
        $this->response->mapCalls(['notFound' => $error]);
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
        $this->request->mapCalls(['userAgent' => new UserAgent('foo', true)]);
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
    public function fallsBackToIndexFileIfRequestForSlashOnly()
    {
        $this->request->mapCalls(
                ['userAgent'          => new UserAgent('foo', true),
                 'hasSessionAttached' => true,
                 'attachedSession'    => $this->session
                ]
        );
        assertEquals(
                'this is index.html',
                $this->sessionBasedHtmlFilePassThrough->resolve(
                        $this->request,
                        $this->response,
                        new UriPath('/', '/')
                )
        );
        verify($this->session, 'putValue')
                ->received('stubbles.webapp.lastPage', 'index.html');
    }

    /**
     * @test
     */
    public function writesNoSessionDataToOutputIfCookiesEnabled()
    {
        $this->request->mapCalls(
                ['userAgent'          => new UserAgent('foo', true),
                 'hasSessionAttached' => true,
                 'attachedSession'    => $this->session
                ]
        );
        assertEquals(
                'this is foo.html',
                $this->sessionBasedHtmlFilePassThrough->resolve(
                        $this->request,
                        $this->response,
                        new UriPath('/', '/foo.html')
                )
        );
        verify($this->session, 'name')->wasNeverCalled();
        verify($this->session, 'id')->wasNeverCalled();
    }

    /**
     * @test
     */
    public function writesSessionDataToOutputIfCookiesDisabled()
    {
        $this->request->mapCalls(
                ['userAgent'          => new UserAgent('foo', false),
                 'hasSessionAttached' => true,
                 'attachedSession'    => $this->session
                ]
        );
        assertEquals(
                'this is foo.html',
                $this->sessionBasedHtmlFilePassThrough->resolve(
                        $this->request,
                        $this->response,
                        new UriPath('/', '/foo.html')
                )
        );
        verify($this->session, 'name')->wasCalledOnce();
        verify($this->session, 'id')->wasCalledOnce();
    }
}
