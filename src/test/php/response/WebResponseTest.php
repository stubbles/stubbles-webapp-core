<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace stubbles\webapp\response;
use bovigo\callmap\NewInstance;
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
 *
 * @group  response
 */
class WebResponseTest extends TestCase
{
    /**
     * @var  \stubbles\webapp\response\WebResponse
     */
    private $response;
    /**
     * @var  \stubbles\streams\memory\MemoryOutputStream
     */
    private $memory;

    protected function setUp(): void
    {
        $this->response = $this->createResponse();
        $this->memory   = new MemoryOutputStream();
    }

    private function createResponse(
            $httpVersion   = HttpVersion::HTTP_1_1,
            $requestMethod = Http::GET,
            string $sapi   = PHP_SAPI
    ): WebResponse {
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

    /**
     * @test
     */
    public function versionIs1_1ByDefault(): void
    {
        $this->response->send($this->memory);
        verify($this->response, 'header')->received('HTTP/1.1 200 OK');
    }

    /**
     * @test
     */
    public function versionCanBeSetOnConstruction(): void
    {
        $response = $this->createResponse(HttpVersion::HTTP_1_0);
        $response->send($this->memory);
        verify($response, 'header')->received('HTTP/1.0 200 OK');
    }

    /**
     * @test
     * @since  4.0.0
     */
    public function statusCodeIs200ByDefault(): void
    {
        assertThat($this->response->statusCode(), equals(200));
    }

    /**
     * @test
     * @since  4.0.0
     */
    public function statusCodeCanBeChanged(): void
    {
        assertThat($this->response->setStatusCode(404)->statusCode(), equals(404));
    }

    /**
     * @test
     */
    public function statusCodeInCgiSapi(): void
    {
        $this->response = $this->createResponse(HttpVersion::HTTP_1_1, Http::GET, 'cgi');
        $this->response->send($this->memory);
        verify($this->response, 'header')->received('Status: 200 OK');
    }

    /**
     * @test
     */
    public function addedHeadersAreSend(): void
    {
        $this->response->addHeader('name', 'value1')->send($this->memory);
        verify($this->response, 'header')->receivedOn(2, 'name: value1');
    }

    /**
     * @test
     */
    public function addingHeaderWithSameNameReplacesExistingHeader(): void
    {
        $this->response->addHeader('name', 'value1')
                ->addHeader('name', 'value2')
                ->send();
        assertThat($this->response->headers()['name'], equals('value2'));
    }

    /**
     * @test
     * @since  4.0.0
     */
    public function doesNotContainsNonAddedHeader(): void
    {
        assertFalse($this->response->containsHeader('X-Foo'));
    }

    /**
     * @test
     * @since  4.0.0
     */
    public function doesNotContainsAddedHeaderWithDifferentValue(): void
    {
        assertFalse(
                $this->response->addHeader('X-Foo', 'bar')
                        ->containsHeader('X-Foo', 'baz')
        );
    }

    /**
     * @test
     * @since  4.0.0
     */
    public function containsAddedHeader(): void
    {
        assertTrue(
                $this->response->addHeader('X-Foo', 'bar')
                        ->containsHeader('X-Foo')
        );
    }

    /**
     * @test
     * @since  4.0.0
     */
    public function containsAddedHeaderWithValue(): void
    {
        assertTrue(
                $this->response->addHeader('X-Foo', 'bar')
                        ->containsHeader('X-Foo', 'bar')
        );
    }

    protected function createCookie(?string $value = null): Cookie
    {
        return NewInstance::of(Cookie::class, ['foo', $value])
                ->stub('send'); // disable actual sending of cookie
    }

    /**
     * @test
     */
    public function cookiesAreSend(): void
    {
        $cookie = $this->createCookie();
        $this->response->addCookie($cookie)
                ->send($this->memory);
        assertTrue(verify($cookie, 'send')->wasCalledOnce());
    }

    /**
     * @test
     */
    public function addingCookieWithSameNameReplacesExistingCookie(): void
    {
        $cookie = $this->createCookie();
        $this->response->addCookie($cookie)
                ->addCookie($cookie)
                ->send($this->memory);
        assertTrue(verify($cookie, 'send')->wasCalledOnce());
    }

    /**
     * @test
     * @since  4.0.0
     */
    public function doesNotContainsNonAddedCookie(): void
    {
        assertFalse($this->response->containsCookie('foo'));
    }

    /**
     * @test
     * @since  4.0.0
     */
    public function doesNotContainsAddedCookieWithDifferentValue(): void
    {
        assertFalse(
                $this->response->addCookie($this->createCookie('bar'))
                               ->containsCookie('foo', 'baz')
        );
    }

    /**
     * @test
     * @since  4.0.0
     */
    public function containsAddedCookie(): void
    {
        assertTrue(
                $this->response->addCookie($this->createCookie('bar'))
                               ->containsCookie('foo')
        );
    }

    /**
     * @test
     * @since  4.0.0
     */
    public function containsAddedCookieWithValue(): void
    {
        assertTrue(
                $this->response->addCookie($this->createCookie('bar'))
                               ->containsCookie('foo', 'bar')
        );
    }

    /**
     * @test
     */
    public function hasNoBodyByDefault(): void
    {
        $outputStream = NewInstance::of(OutputStream::class);
        $this->response->send($outputStream);
        assertTrue(verify($outputStream, 'write')->wasNeverCalled());
    }

    /**
     * @test
     */
    public function doesNotReturnOutputStreamWhenNonePassedAndNoResourceGiven(): void
    {
        assertNull($this->response->send());
    }

    /**
     * @test
     */
    public function bodyIsSend(): void
    {
        assertThat(
                $this->response->write('foo')->send($this->memory)->buffer(),
                equals('foo')
        );
    }

    /**
     * @test
     * @since  4.0.0
     */
    public function bodyIsNotSendWhenRequestMethodIsHead(): void
    {
        $this->response = $this->createResponse(HttpVersion::HTTP_1_1, Http::HEAD);
        $outputStream = NewInstance::of(OutputStream::class);
        $this->response->write('foo')->send($outputStream);
        assertTrue(verify($outputStream, 'write')->wasNeverCalled());
    }

    /**
     * @test
     * @since  3.1.0
     * @group  final_response
     */
    public function isNotFixedByDefault(): void
    {
        assertFalse($this->response->isFixed());
    }

    /**
     * @since  1.3.0
     * @test
     */
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
     * @test
     * @group  bug251
     */
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
     * @test
     * @group  issue_73
     */
    public function unauthorizedSetsStatusCodeTo401(): void
    {
        $this->response->unauthorized(['Basic realm="simple"']);
        $this->response->send();
        verify($this->response, 'header')->received('HTTP/1.1 401 Unauthorized');
    }

    /**
     * @since  8.0.0
     * @test
     * @group  issue_73
     */
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
     * @test
     * @group  issue_73
     */
    public function unauthorizedReturnsErrorInstance(): void
    {
        assertThat(
            $this->response->unauthorized(['Basic realm="simple"']),
            equals(Error::unauthorized())
        );
    }

    /**
     * @since  8.0.0
     * @test
     * @group  issue_73
     */
    public function unauthorizedFixatesResponse(): void
    {
        $this->response->unauthorized(['Basic realm="simple"']);
        assertTrue($this->response->isFixed());
    }

    /**
     * @since  2.0.0
     * @test
     */
    public function forbiddenSetsStatusCodeTo403(): void
    {
        $this->response->forbidden();
        $this->response->send();
        verify($this->response, 'header')->received('HTTP/1.1 403 Forbidden');
    }

    /**
     * @since  6.0.0
     * @test
     */
    public function forbiddenReturnsErrorInstance(): void
    {
        assertThat($this->response->forbidden(), equals(Error::forbidden()));
    }

    /**
     * @test
     * @since  3.1.0
     * @group  final_response
     */
    public function forbiddenFixatesResponse(): void
    {
        $this->response->forbidden();
        assertTrue($this->response->isFixed());
    }

    /**
     * @since  2.0.0
     * @test
     */
    public function notFoundSetsStatusCodeTo404(): void
    {
        $this->response->notFound();
        $this->response->send();
        verify($this->response, 'header')->received('HTTP/1.1 404 Not Found');
    }

    /**
     * @since  6.0.0
     * @test
     */
    public function notFoundReturnsErrorInstance(): void
    {
        assertThat($this->response->notFound(), equals(Error::notFound()));
    }

    /**
     * @test
     * @since  3.1.0
     * @group  final_response
     */
    public function notFoundFixatesResponse(): void
    {
        $this->response->notFound();
        assertTrue($this->response->isFixed());
    }

    /**
     * @since  2.0.0
     * @test
     */
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
     * @test
     */
    public function methodNotAllowedReturnsErrorInstance(): void
    {
        assertThat(
                $this->response->methodNotAllowed('POST', ['GET', 'HEAD']),
                equals(Error::methodNotAllowed('POST', ['GET', 'HEAD']))
        );
    }

    /**
     * @test
     * @since  3.1.0
     * @group  final_response
     */
    public function methodNotAllowedFixatesResponse(): void
    {
        $this->response->methodNotAllowed('POST', ['GET', 'HEAD']);
        assertTrue($this->response->isFixed());
    }

    /**
     * @since  2.0.0
     * @test
     */
    public function notAcceptableSetsStatusCodeTo406(): void
    {
        $this->response->notAcceptable();
        $this->response->send();
        verify($this->response, 'header')->received('HTTP/1.1 406 Not Acceptable');
    }

    /**
     * @test
     * @since  3.1.0
     * @group  final_response
     */
    public function notAcceptableFixatesResponse(): void
    {
        $this->response->notAcceptable();
        assertTrue($this->response->isFixed());
    }

    /**
     * @since  2.0.0
     * @test
     */
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
     * @test
     */
    public function internalServerErrorSetsStatusCodeTo500(): void
    {
        $this->response->internalServerError('ups!');
        $this->response->send();
        verify($this->response, 'header')
                ->received('HTTP/1.1 500 Internal Server Error');
    }

    /**
     * @since  6.0.0
     * @test
     */
    public function internalServerErrorReturnsErrorInstance(): void
    {
        assertThat(
                $this->response->internalServerError('ups!'),
                equals(Error::internalServerError('ups!'))
        );
    }

    /**
     * @test
     * @since  3.1.0
     * @group  final_response
     */
    public function internalServerErrorFixatesResponse(): void
    {
        $this->response->internalServerError('ups');
        assertTrue($this->response->isFixed());
    }

    /**
     * @since  2.0.0
     * @test
     */
    public function httpVersionNotSupportedSetsStatusCodeTo505(): void
    {
        $this->response->httpVersionNotSupported();
        assertThat(
                $this->response->send($this->memory)->buffer(),
                equals('Error: Unsupported HTTP protocol version, expected HTTP/1.0 or HTTP/1.1')
        );
        verify($this->response, 'header')
                ->received('HTTP/1.1 505 HTTP Version Not Supported');
    }

    /**
     * @test
     * @since  3.1.0
     * @group  final_response
     */
    public function httpVersionNotSupportedFixatesResponse(): void
    {
        $this->response->httpVersionNotSupported();
        assertTrue($this->response->isFixed());
    }

    /**
     * @return  array<HttpVersion[]>
     */
    public function unsupportedHttpVersions(): array
    {
        return [
            [HttpVersion::fromString('HTTP/0.9')],
            [HttpVersion::fromString('HTTP/2.0')]
        ];
    }

    /**
     * @since  4.0.0
     * @test
     * @dataProvider  unsupportedHttpVersions
     */
    public function createInstanceWithHttpMajorVersionOtherThanOneFixatesResponseToHttpVersionNotSupported(HttpVersion $unsupportedHttpVersion): void
    {
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
     * @test
     * @group  issue_74
     * @since  5.1.0
     */
    public function requestIdAddedByDefault(): void
    {
        $this->response->send($this->memory);
        verify($this->response, 'header')
                ->receivedOn(3, 'X-Request-ID: example-request-id-foo');
    }

    /**
     * @test
     * @group  issue_74
     * @since  5.1.0
     */
    public function requestIdCanBeChanged(): void
    {
        $this->response->headers()->requestId('another-request-id-bar');
        $this->response->send($this->memory);
        verify($this->response, 'header')
                ->receivedOn(2, 'X-Request-ID: another-request-id-bar');
    }
}
