<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace stubbles\webapp\response;

use bovigo\callmap\ClassProxy;
use bovigo\callmap\NewInstance;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use stubbles\peer\http\{Http, HttpVersion};
use stubbles\streams\OutputStream;
use stubbles\streams\memory\MemoryOutputStream;
use stubbles\webapp\Request;
use stubbles\webapp\response\mimetypes\PassThrough;

use function bovigo\assert\{
    assertThat,
    assertFalse,
    assertNull,
    assertTrue,
    predicate\equals
};
use function bovigo\callmap\verify;
/**
 * Tests for stubbles\webapp\response\WebResponse.
 */
#[Group('response')]
class WebResponseTest extends TestCase
{
    private WebResponse&ClassProxy $response;
    private MemoryOutputStream $memory;

    protected function setUp(): void
    {
        $this->response = $this->createResponse();
        $this->memory   = new MemoryOutputStream();
    }

    private function createResponse(
        string|HttpVersion $httpVersion = HttpVersion::HTTP_1_1,
        string $requestMethod = Http::GET,
        string $sapi = PHP_SAPI
    ): WebResponse&ClassProxy {
        $request = NewInstance::of(Request::class)->returns([
            'id'              => 'example-request-id-foo',
            'protocolVersion' => HttpVersion::castFrom($httpVersion),
            'method'          => $requestMethod
        ]);
        return NewInstance::of(
            WebResponse::class,
            [$request, new PassThrough(), $sapi]
        )->stub('header'); // prevent call to original method
    }

    #[Test]
    public function versionIs1_1ByDefault(): void
    {
        $this->response->send($this->memory);
        verify($this->response, 'header')->received('HTTP/1.1 200 OK');
    }

    #[Test]
    public function versionCanBeSetOnConstruction(): void
    {
        $response = $this->createResponse(HttpVersion::HTTP_1_0);
        $response->send($this->memory);
        verify($response, 'header')->received('HTTP/1.0 200 OK');
    }

    /**
     * @since  4.0.0
     */
    #[Test]
    public function statusCodeIs200ByDefault(): void
    {
        assertThat($this->response->statusCode(), equals(200));
    }

    /**
     * @since  4.0.0
     */
    #[Test]
    public function statusCodeCanBeChanged(): void
    {
        assertThat($this->response->setStatusCode(404)->statusCode(), equals(404));
    }

    #[Test]
    public function statusCodeInCgiSapi(): void
    {
        $this->response = $this->createResponse(HttpVersion::HTTP_1_1, Http::GET, 'cgi');
        $this->response->send($this->memory);
        verify($this->response, 'header')->received('Status: 200 OK');
    }

    #[Test]
    public function addedHeadersAreSend(): void
    {
        $this->response->addHeader('name', 'value1')->send($this->memory);
        verify($this->response, 'header')->receivedOn(2, 'name: value1');
    }

    #[Test]
    public function addingHeaderWithSameNameReplacesExistingHeader(): void
    {
        $this->response->addHeader('name', 'value1')
            ->addHeader('name', 'value2')
            ->send();
        assertThat($this->response->headers()['name'], equals('value2'));
    }

    /**
     * @since  4.0.0
     */
    #[Test]
    public function doesNotContainsNonAddedHeader(): void
    {
        assertFalse($this->response->containsHeader('X-Foo'));
    }

    /**
     * @since  4.0.0
     */
    #[Test]
    public function doesNotContainsAddedHeaderWithDifferentValue(): void
    {
        assertFalse(
            $this->response->addHeader('X-Foo', 'bar')
                ->containsHeader('X-Foo', 'baz')
        );
    }

    /**
     * @since  4.0.0
     */
    #[Test]
    public function containsAddedHeader(): void
    {
        assertTrue(
            $this->response->addHeader('X-Foo', 'bar')->containsHeader('X-Foo')
        );
    }

    /**
     * @since  4.0.0
     */
    #[Test]
    public function containsAddedHeaderWithValue(): void
    {
        assertTrue(
            $this->response->addHeader('X-Foo', 'bar')->containsHeader('X-Foo', 'bar')
        );
    }

    private function createCookie(?string $value = null): Cookie&ClassProxy
    {
        return NewInstance::of(Cookie::class, ['foo', $value])
                ->stub('send'); // disable actual sending of cookie
    }

    #[Test]
    public function cookiesAreSend(): void
    {
        $cookie = $this->createCookie();
        $this->response->addCookie($cookie)->send($this->memory);
        assertTrue(verify($cookie, 'send')->wasCalledOnce());
    }

    #[Test]
    public function addingCookieWithSameNameReplacesExistingCookie(): void
    {
        $cookie = $this->createCookie();
        $this->response->addCookie($cookie)
            ->addCookie($cookie)
            ->send($this->memory);
        assertTrue(verify($cookie, 'send')->wasCalledOnce());
    }

    /**
     * @since  4.0.0
     */
    #[Test]
    public function doesNotContainsNonAddedCookie(): void
    {
        assertFalse($this->response->containsCookie('foo'));
    }

    /**
     * @since  4.0.0
     */
    #[Test]
    public function doesNotContainsAddedCookieWithDifferentValue(): void
    {
        assertFalse(
            $this->response->addCookie($this->createCookie('bar'))
                ->containsCookie('foo', 'baz')
        );
    }

    /**
     * @since  4.0.0
     */
    #[Test]
    public function containsAddedCookie(): void
    {
        assertTrue(
            $this->response->addCookie($this->createCookie('bar'))
                ->containsCookie('foo')
        );
    }

    /**
     * @since  4.0.0
     */
    #[Test]
    public function containsAddedCookieWithValue(): void
    {
        assertTrue(
            $this->response->addCookie($this->createCookie('bar'))
                ->containsCookie('foo', 'bar')
        );
    }

    #[Test]
    public function hasNoBodyByDefault(): void
    {
        $outputStream = NewInstance::of(OutputStream::class);
        $this->response->send($outputStream);
        assertTrue(verify($outputStream, 'write')->wasNeverCalled());
    }

    #[Test]
    public function doesNotReturnOutputStreamWhenNonePassedAndNoResourceGiven(): void
    {
        assertNull($this->response->send());
    }

    #[Test]
    public function bodyIsSend(): void
    {
        $this->response->write('foo')->send($this->memory);
        assertThat($this->memory->buffer(), equals('foo'));
    }

    /**
     * @since  4.0.0
     */
    #[Test]
    public function bodyIsNotSendWhenRequestMethodIsHead(): void
    {
        $this->response = $this->createResponse(HttpVersion::HTTP_1_1, Http::HEAD);
        $outputStream = NewInstance::of(OutputStream::class);
        $this->response->write('foo')->send($outputStream);
        assertTrue(verify($outputStream, 'write')->wasNeverCalled());
    }

    #[Test]
    #[Group('final_response')]
    public function isNotFixedByDefault(): void
    {
        assertFalse($this->response->isFixed());
    }

    /**
     * @since  1.3.0
     */
    #[Test]
    public function redirectAddsLocationHeaderAndStatusCode(): void
    {
        $this->response->redirect('http://example.com/', 301);
        $this->response->send();
        verify($this->response, 'header')
            ->receivedOn(1, 'HTTP/1.1 301 Moved Permanently');
        verify($this->response, 'header')
            ->receivedOn(2, 'Location: http://example.com/');
    }

    /**
     * @since  1.5.0
     */
    #[Test]
    #[Group('bug251')]
    public function redirectWithoutStatusCodeAndReasonPhraseAddsLocationHeaderAndStatusCode302(): void
    {
        $this->response->redirect('http://example.com/');
        $this->response->send();
        verify($this->response, 'header')
            ->receivedOn(1, 'HTTP/1.1 302 Found');
        verify($this->response, 'header')
            ->receivedOn(2, 'Location: http://example.com/');
    }

    /**
     * @since  8.0.0
     */
    #[Test]
    #[Group('issue_73')]
    public function unauthorizedSetsStatusCodeTo401(): void
    {
        $this->response->unauthorized(['Basic realm="simple"']);
        $this->response->send();
        verify($this->response, 'header')->received('HTTP/1.1 401 Unauthorized');
    }

    /**
     * @since  8.0.0
     */
    #[Test]
    #[Group('issue_73')]
    public function unauthorizedAddsWwwAuthenticateHeaderWithChallenges(): void
    {
        $this->response->unauthorized([
            'Newauth realm="apps", type=1, title="Login to \"apps\""',
            'Basic realm="simple"'
        ]);
        assertTrue($this->response->containsHeader(
            'WWW-Authenticate',
            'Newauth realm="apps", type=1, title="Login to \"apps\"", Basic realm="simple"'
        ));
    }

    /**
     * @since  8.0.0
     */
    #[Test]
    #[Group('issue_73')]
    public function unauthorizedReturnsErrorInstance(): void
    {
        assertThat(
            $this->response->unauthorized(['Basic realm="simple"']),
            equals(Error::unauthorized())
        );
    }

    /**
     * @since  8.0.0
     */
    #[Test]
    #[Group('issue_73')]
    public function unauthorizedFixatesResponse(): void
    {
        $this->response->unauthorized(['Basic realm="simple"']);
        assertTrue($this->response->isFixed());
    }

    /**
     * @since  2.0.0
     */
    #[Test]
    public function forbiddenSetsStatusCodeTo403(): void
    {
        $this->response->forbidden();
        $this->response->send();
        verify($this->response, 'header')->received('HTTP/1.1 403 Forbidden');
    }

    /**
     * @since  6.0.0
     */
    #[Test]
    public function forbiddenReturnsErrorInstance(): void
    {
        assertThat($this->response->forbidden(), equals(Error::forbidden()));
    }

    /**
     * @since  3.1.0
     */
    #[Test]
    #[Group('final_response')]
    public function forbiddenFixatesResponse(): void
    {
        $this->response->forbidden();
        assertTrue($this->response->isFixed());
    }

    /**
     * @since  2.0.0
     */
    #[Test]
    public function notFoundSetsStatusCodeTo404(): void
    {
        $this->response->notFound();
        $this->response->send();
        verify($this->response, 'header')->received('HTTP/1.1 404 Not Found');
    }

    /**
     * @since  6.0.0
     */
    #[Test]
    public function notFoundReturnsErrorInstance(): void
    {
        assertThat($this->response->notFound(), equals(Error::notFound()));
    }

    /**
     * @since  3.1.0
     */
    #[Test]
    #[Group('final_response')]
    public function notFoundFixatesResponse(): void
    {
        $this->response->notFound();
        assertTrue($this->response->isFixed());
    }

    /**
     * @since  2.0.0
     */
    #[Test]
    public function methodNotAllowedSetsStatusCodeTo405(): void
    {
        $this->response->methodNotAllowed('POST', ['GET', 'HEAD']);
        $this->response->send();
        verify($this->response, 'header')
            ->receivedOn(1, 'HTTP/1.1 405 Method Not Allowed');
        verify($this->response, 'header')
            ->receivedOn(2, 'Allow: GET, HEAD');
    }

    /**
     * @since  6.0.0
     */
    #[Test]
    public function methodNotAllowedReturnsErrorInstance(): void
    {
        assertThat(
            $this->response->methodNotAllowed('POST', ['GET', 'HEAD']),
            equals(Error::methodNotAllowed('POST', ['GET', 'HEAD']))
        );
    }

    /**
     * @since  3.1.0
     */
    #[Test]
    #[Group('final_response')]
    public function methodNotAllowedFixatesResponse(): void
    {
        $this->response->methodNotAllowed('POST', ['GET', 'HEAD']);
        assertTrue($this->response->isFixed());
    }

    /**
     * @since  2.0.0
     */
    #[Test]
    public function notAcceptableSetsStatusCodeTo406(): void
    {
        $this->response->notAcceptable();
        $this->response->send();
        verify($this->response, 'header')->received('HTTP/1.1 406 Not Acceptable');
    }

    /**
     * @since  3.1.0
     */
    #[Test]
    #[Group('final_response')]
    public function notAcceptableFixatesResponse(): void
    {
        $this->response->notAcceptable();
        assertTrue($this->response->isFixed());
    }

    /**
     * @since  2.0.0
     */
    #[Test]
    public function notAcceptableWithSupportedMimeTypesSetsStatusCodeTo406(): void
    {
        $this->response->notAcceptable(['application/json', 'application/xml']);
        $this->response->send();
        verify($this->response, 'header')
            ->receivedOn(1, 'HTTP/1.1 406 Not Acceptable');
        verify($this->response, 'header')
            ->receivedOn(2, 'X-Acceptable: application/json, application/xml');
    }

    /**
     * @since  2.0.0
     */
    #[Test]
    public function internalServerErrorSetsStatusCodeTo500(): void
    {
        $this->response->internalServerError('ups!');
        $this->response->send();
        verify($this->response, 'header')
            ->received('HTTP/1.1 500 Internal Server Error');
    }

    /**
     * @since  6.0.0
     */
    #[Test]
    public function internalServerErrorReturnsErrorInstance(): void
    {
        assertThat(
            $this->response->internalServerError('ups!'),
            equals(Error::internalServerError('ups!'))
        );
    }

    /**
     * @since  3.1.0
     */
    #[Test]
    #[Group('final_response')]
    public function internalServerErrorFixatesResponse(): void
    {
        $this->response->internalServerError('ups');
        assertTrue($this->response->isFixed());
    }

    /**
     * @since  2.0.0
     */
    #[Test]
    public function httpVersionNotSupportedSetsStatusCodeTo505(): void
    {
        $this->response->httpVersionNotSupported();
        $this->response->send($this->memory);
        assertThat(
            $this->memory->buffer(),
            equals('Error: Unsupported HTTP protocol version, expected HTTP/1.0 or HTTP/1.1')
        );
        verify($this->response, 'header')
            ->received('HTTP/1.1 505 HTTP Version Not Supported');
    }

    /**
     * @since  3.1.0
     */
    #[Test]
    #[Group('final_response')]
    public function httpVersionNotSupportedFixatesResponse(): void
    {
        $this->response->httpVersionNotSupported();
        assertTrue($this->response->isFixed());
    }

    public static function unsupportedHttpVersions(): Generator
    {
        yield [HttpVersion::fromString('HTTP/0.9')];
        yield [HttpVersion::fromString('HTTP/2.0')];
    }

    /**
     * @since  4.0.0
     */
    #[Test]
    #[DataProvider('unsupportedHttpVersions')]
    public function createInstanceWithHttpMajorVersionOtherThanOneFixatesResponseToHttpVersionNotSupported(
        HttpVersion $unsupportedHttpVersion
    ): void {
        $response = $this->createResponse($unsupportedHttpVersion);
        assertTrue($response->isFixed());
        $response->send($this->memory);
        assertThat(
            $this->memory->buffer(),
            equals('Error: Unsupported HTTP protocol version, expected HTTP/1.0 or HTTP/1.1')
        );
        verify($response, 'header')
            ->received('HTTP/1.1 505 HTTP Version Not Supported');
    }

    /**
     * @since  5.1.0
     */
    #[Test]
    #[Group('issue_74')]
    public function requestIdAddedByDefault(): void
    {
        $this->response->send($this->memory);
        verify($this->response, 'header')
            ->receivedOn(3, 'X-Request-ID: example-request-id-foo');
    }

    /**
     * @since  5.1.0
     */
    #[Test]
    #[Group('issue_74')]
    public function requestIdCanBeChanged(): void
    {
        $this->response->headers()->requestId('another-request-id-bar');
        $this->response->send($this->memory);
        verify($this->response, 'header')
            ->receivedOn(2, 'X-Request-ID: another-request-id-bar');
    }
}
