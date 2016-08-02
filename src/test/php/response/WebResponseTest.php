<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  stubbles\webapp
 */
namespace stubbles\webapp\response;
use bovigo\callmap\NewInstance;
use stubbles\peer\http\Http;
use stubbles\peer\http\HttpVersion;
use stubbles\streams\OutputStream;
use stubbles\streams\memory\MemoryOutputStream;
use stubbles\webapp\Request;
use stubbles\webapp\response\mimetypes\PassThrough;

use function bovigo\assert\assert;
use function bovigo\assert\assertFalse;
use function bovigo\assert\assertNull;
use function bovigo\assert\assertTrue;
use function bovigo\assert\predicate\equals;
use function bovigo\callmap\verify;
/**
 * Tests for stubbles\webapp\response\WebResponse.
 *
 * @group  response
 */
class WebResponseTest extends \PHPUnit_Framework_TestCase
{
    /**
     * instance to test
     *
     * @type  \stubbles\webapp\response\WebResponse
     */
    private $response;
    /**
     * @type  \stubbles\streams\memory\MemoryOutputStream
     */
    private $memory;

    /**
     * set up test environment
     */
    public function setUp()
    {
        $this->response = $this->createResponse();
        $this->memory   = new MemoryOutputStream();
    }

    /**
     * creates response where output facing methods are mocked
     *
     * @param   string|HttpVersion  $httpVersion    optional  http version to use for response, defaults to HTTP/1.1
     * @param   string              $requestMethod  optional  http request method to use, defaults to GET
     * @param   string              $sapi           optional  current php sapi, defaults to value of PHP_SAPI constant
     * @return  WebResponse
     */
    private function createResponse(
            $httpVersion = HttpVersion::HTTP_1_1,
            $requestMethod = Http::GET,
            string $sapi = PHP_SAPI
    ): WebResponse {
        $request = NewInstance::of(Request::class)
                ->mapCalls(
                        ['id'              => 'example-request-id-foo',
                         'protocolVersion' => HttpVersion::castFrom($httpVersion),
                         'method'          => $requestMethod
                        ]
        );
        return NewInstance::of(
                WebResponse::class,
                [$request, new PassThrough(), $sapi]
        )->mapCalls(['header' => false]); // prevent call to original method
    }

    /**
     * @test
     */
    public function versionIs1_1ByDefault()
    {
        $this->response->send($this->memory);
        verify($this->response, 'header')->received('HTTP/1.1 200 OK');
    }

    /**
     * @test
     */
    public function versionCanBeSetOnConstruction()
    {
        $response = $this->createResponse(HttpVersion::HTTP_1_0);
        $response->send($this->memory);
        verify($response, 'header')->received('HTTP/1.0 200 OK');
    }

    /**
     * @test
     * @since  4.0.0
     */
    public function statusCodeIs200ByDefault()
    {
        assert($this->response->statusCode(), equals(200));
    }

    /**
     * @test
     * @since  4.0.0
     */
    public function statusCodeCanBeChanged()
    {
        assert($this->response->setStatusCode(404)->statusCode(), equals(404));
    }

    /**
     * @test
     */
    public function statusCodeInCgiSapi()
    {
        $this->response = $this->createResponse(HttpVersion::HTTP_1_1, Http::GET, 'cgi');
        $this->response->send($this->memory);
        verify($this->response, 'header')->received('Status: 200 OK');
    }

    /**
     * @test
     */
    public function addedHeadersAreSend()
    {
        $this->response->addHeader('name', 'value1')->send($this->memory);
        verify($this->response, 'header')->receivedOn(2, 'name: value1');
    }

    /**
     * @test
     */
    public function addingHeaderWithSameNameReplacesExistingHeader()
    {
        $this->response->addHeader('name', 'value1')
                ->addHeader('name', 'value2')
                ->send();
        assert($this->response->headers()['name'], equals('value2'));
    }

    /**
     * @test
     * @since  4.0.0
     */
    public function doesNotContainsNonAddedHeader()
    {
        assertFalse($this->response->containsHeader('X-Foo'));
    }

    /**
     * @test
     * @since  4.0.0
     */
    public function doesNotContainsAddedHeaderWithDifferentValue()
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
    public function containsAddedHeader()
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
    public function containsAddedHeaderWithValue()
    {
        assertTrue(
                $this->response->addHeader('X-Foo', 'bar')
                        ->containsHeader('X-Foo', 'bar')
        );
    }

    protected function createCookie($value = null): Cookie
    {
        return NewInstance::of(Cookie::class, ['foo', $value])
                ->mapCalls(['send' => false]); // disable actual sending of cookie
    }

    /**
     * @test
     */
    public function cookiesAreSend()
    {
        $cookie = $this->createCookie();
        $this->response->addCookie($cookie)
                ->send($this->memory);
        verify($cookie, 'send')->wasCalledOnce();
    }

    /**
     * @test
     */
    public function addingCookieWithSameNameReplacesExistingCookie()
    {
        $cookie = $this->createCookie();
        $this->response->addCookie($cookie)
                ->addCookie($cookie)
                ->send($this->memory);
        verify($cookie, 'send')->wasCalledOnce();
    }

    /**
     * @test
     * @since  4.0.0
     */
    public function doesNotContainsNonAddedCookie()
    {
        assertFalse($this->response->containsCookie('foo'));
    }

    /**
     * @test
     * @since  4.0.0
     */
    public function doesNotContainsAddedCookieWithDifferentValue()
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
    public function containsAddedCookie()
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
    public function containsAddedCookieWithValue()
    {
        assertTrue(
                $this->response->addCookie($this->createCookie('bar'))
                               ->containsCookie('foo', 'bar')
        );
    }

    /**
     * @test
     */
    public function hasNoBodyByDefault()
    {
        $outputStream = NewInstance::of(OutputStream::class);
        $this->response->send($outputStream);
        verify($outputStream, 'write')->wasNeverCalled();
    }

    /**
     * @test
     */
    public function doesNotReturnOutputStreamWhenNonePassedAndNoResourceGiven()
    {
        assertNull($this->response->send());
    }

    /**
     * @test
     */
    public function bodyIsSend()
    {
        assert(
                $this->response->write('foo')->send($this->memory)->buffer(),
                equals('foo')
        );
    }

    /**
     * @test
     * @since  4.0.0
     */
    public function bodyIsNotSendWhenRequestMethodIsHead()
    {
        $this->response = $this->createResponse(HttpVersion::HTTP_1_1, Http::HEAD);
        $outputStream = NewInstance::of(OutputStream::class);
        $this->response->write('foo')->send($outputStream);
        verify($outputStream, 'write')->wasNeverCalled();
    }

    /**
     * @test
     * @since  3.1.0
     * @group  final_response
     */
    public function isNotFixedByDefault()
    {
        assertFalse($this->response->isFixed());
    }

    /**
     * @since  1.3.0
     * @test
     */
    public function redirectAddsLocationHeaderAndStatusCode()
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
    public function redirectWithoutStatusCodeAndReasonPhraseAddsLocationHeaderAndStatusCode302()
    {
        $this->response->redirect('http://example.com/');
        $this->response->send();
        verify($this->response, 'header')
                ->receivedOn(1, 'HTTP/1.1 302 Found');
        verify($this->response, 'header')
                ->receivedOn(2, 'Location: http://example.com/');
    }

    /**
     * @since  2.0.0
     * @test
     */
    public function forbiddenSetsStatusCodeTo403()
    {
        $this->response->forbidden();
        $this->response->send();
        verify($this->response, 'header')->received('HTTP/1.1 403 Forbidden');
    }

    /**
     * @since  6.0.0
     * @test
     */
    public function forbiddenReturnsErrorInstance()
    {
        assert($this->response->forbidden(), equals(Error::forbidden()));
    }

    /**
     * @test
     * @since  3.1.0
     * @group  final_response
     */
    public function forbiddenFixatesResponse()
    {
        $this->response->forbidden();
        assertTrue($this->response->isFixed());
    }

    /**
     * @since  2.0.0
     * @test
     */
    public function notFoundSetsStatusCodeTo404()
    {
        $this->response->notFound();
        $this->response->send();
        verify($this->response, 'header')->received('HTTP/1.1 404 Not Found');
    }

    /**
     * @since  6.0.0
     * @test
     */
    public function notFoundReturnsErrorInstance()
    {
        assert($this->response->notFound(), equals(Error::notFound()));
    }

    /**
     * @test
     * @since  3.1.0
     * @group  final_response
     */
    public function notFoundFixatesResponse()
    {
        $this->response->notFound();
        assertTrue($this->response->isFixed());
    }

    /**
     * @since  2.0.0
     * @test
     */
    public function methodNotAllowedSetsStatusCodeTo405()
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
    public function methodNotAllowedReturnsErrorInstance()
    {
        assert(
                $this->response->methodNotAllowed('POST', ['GET', 'HEAD']),
                equals(Error::methodNotAllowed('POST', ['GET', 'HEAD']))
        );
    }

    /**
     * @test
     * @since  3.1.0
     * @group  final_response
     */
    public function methodNotAllowedFixatesResponse()
    {
        $this->response->methodNotAllowed('POST', ['GET', 'HEAD']);
        assertTrue($this->response->isFixed());
    }

    /**
     * @since  2.0.0
     * @test
     */
    public function notAcceptableSetsStatusCodeTo406()
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
    public function notAcceptableFixatesResponse()
    {
        $this->response->notAcceptable();
        assertTrue($this->response->isFixed());
    }

    /**
     * @since  2.0.0
     * @test
     */
    public function notAcceptableWithSupportedMimeTypesSetsStatusCodeTo406()
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
    public function internalServerErrorSetsStatusCodeTo500()
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
    public function internalServerErrorReturnsErrorInstance()
    {
        assert(
                $this->response->internalServerError('ups!'),
                equals(Error::internalServerError('ups!'))
        );
    }

    /**
     * @test
     * @since  3.1.0
     * @group  final_response
     */
    public function internalServerErrorFixatesResponse()
    {
        $this->response->internalServerError('ups');
        assertTrue($this->response->isFixed());
    }

    /**
     * @since  2.0.0
     * @test
     */
    public function httpVersionNotSupportedSetsStatusCodeTo505()
    {
        $this->response->httpVersionNotSupported();
        assert(
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
    public function httpVersionNotSupportedFixatesResponse()
    {
        $this->response->httpVersionNotSupported();
        assertTrue($this->response->isFixed());
    }

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
    public function createInstanceWithHttpMajorVersionOtherThanOneFixatesResponseToHttpVersionNotSupported(HttpVersion $unsupportedHttpVersion)
    {
        $response = $this->createResponse($unsupportedHttpVersion);
        assertTrue($response->isFixed());
        assert(
                $response->send($this->memory)->buffer(),
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
    public function requestIdAddedByDefault()
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
    public function requestIdCanBeChanged()
    {
        $this->response->headers()->requestId('another-request-id-bar');
        $this->response->send($this->memory);
        verify($this->response, 'header')
                ->receivedOn(2, 'X-Request-ID: another-request-id-bar');
    }
}
