<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace stubbles\webapp\htmlpassthrough;

use bovigo\callmap\ClassProxy;
use bovigo\callmap\NewInstance;
use PHPUnit\Framework\TestCase;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use stubbles\webapp\Request;
use stubbles\webapp\Response;
use stubbles\webapp\UriPath;
use stubbles\webapp\request\UserAgent;
use stubbles\webapp\response\Error;
use stubbles\webapp\session\Session;

use function bovigo\assert\assertThat;
use function bovigo\assert\predicate\equals;
use function bovigo\assert\predicate\isSameAs;
use function bovigo\callmap\verify;
/**
 * Test for stubbles\webapp\htmlpassthrough\SessionBasedHtmlFilePassThrough.
 */
#[Group('htmlpassthrough')]
class SessionBasedHtmlFilePassThroughTest extends TestCase
{
    private SessionBasedHtmlFilePassThrough $sessionBasedHtmlFilePassThrough;
    private Session&ClassProxy $session;
    private Request&ClassProxy $request;
    private Response&ClassProxy $response;

    protected function setUp(): void
    {
        $root = vfsStream::setup();
        vfsStream::newFile('index.html')->withContent('this is index.html')->at($root);
        vfsStream::newFile('foo.html')->withContent('this is foo.html')->at($root);
        $this->session  = NewInstance::of(Session::class)->returns([
            'name' => 'psessid', 'id' => '313'
        ]);
        $this->request  = NewInstance::of(Request::class);
        $this->response = NewInstance::of(Response::class);
        $this->sessionBasedHtmlFilePassThrough = new SessionBasedHtmlFilePassThrough(
            vfsStream::url('root')
        );
    }

    #[Test]
    public function functionReturnsClassName(): void
    {
        assertThat(
            \stubbles\webapp\sessionBasedHtmlPassThrough(),
            equals(SessionBasedHtmlFilePassThrough::class)
        );
    }

    #[Test]
    public function requestForNonExistingFileWritesNotFoundResponse(): void
    {
        $error = Error::notFound();
        $this->response->returns(['notFound' => $error]);
        assertThat(
            $this->sessionBasedHtmlFilePassThrough->resolve(
                $this->request,
                $this->response,
                new UriPath('/', '/doesNotExist.html')
            ),
            isSameAs($error)
        );
    }

    #[Test]
    public function selectsAvailableRoute(): void
    {
        $this->request->returns([
            'userAgent'          => new UserAgent('foo', true),
            'hasSessionAttached' => false
        ]);
        assertThat(
            $this->sessionBasedHtmlFilePassThrough->resolve(
                $this->request,
                $this->response,
                new UriPath('/', '/foo.html')
            ),
            equals('this is foo.html')
        );
    }

    #[Test]
    public function fallsBackToIndexFileIfRequestForSlashOnly(): void
    {
        $this->request->returns([
            'userAgent'          => new UserAgent('foo', true),
            'hasSessionAttached' => true,
            'attachedSession'    => $this->session

        ]);
        assertThat(
            $this->sessionBasedHtmlFilePassThrough->resolve(
                $this->request,
                $this->response,
                new UriPath('/', '/')
            ),
            equals('this is index.html')
        );
        verify($this->session, 'putValue')
                ->received('stubbles.webapp.lastPage', 'index.html');
    }

    #[Test]
    public function writesNoSessionDataToOutputIfCookiesEnabled(): void
    {
        $this->request->returns([
            'userAgent'          => new UserAgent('foo', true),
            'hasSessionAttached' => true,
            'attachedSession'    => $this->session

        ]);
        assertThat(
            $this->sessionBasedHtmlFilePassThrough->resolve(
                $this->request,
                $this->response,
                new UriPath('/', '/foo.html')
            ),
            equals('this is foo.html')
        );
        verify($this->session, 'name')->wasNeverCalled();
        verify($this->session, 'id')->wasNeverCalled();
    }

    #[Test]
    public function writesSessionDataToOutputIfCookiesDisabled(): void
    {
        $this->request->returns([
            'userAgent'          => new UserAgent('foo', false),
            'hasSessionAttached' => true,
            'attachedSession'    => $this->session

        ]);
        try {
            assertThat(
                $this->sessionBasedHtmlFilePassThrough->resolve(
                    $this->request,
                    $this->response,
                    new UriPath('/', '/foo.html')
                ),
                equals('this is foo.html')
            );
            verify($this->session, 'name')->wasCalledOnce();
            verify($this->session, 'id')->wasCalledOnce();
        } finally {
            // need to end output buffer opened by SessionBasedHtmlFilePassThrough
            ob_end_clean();
        }
    }
}
