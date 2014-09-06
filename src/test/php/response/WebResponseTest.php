<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  stubbles\webapp
 */
namespace stubbles\webapp\response;
use stubbles\peer\http\Http;
use stubbles\peer\http\HttpVersion;
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
     * @type  WebResponse
     */
    private $response;

    /**
     * set up test environment
     */
    public function setUp()
    {
        $this->response = $this->createResponse();
    }

    /**
     * creates response where output facing methods are mocked
     *
     * @param   string|HttpVersion  $httpVersion    optional  http version to use for response, defaults to HTTP/1.1
     * @param   string              $requestMethod  optional  http request method to use, defaults to GET
     * @param   string              $sapi           optional  current php sapi, defaults to value of PHP_SAPI constant
     * @return  WebResponse
     */
    private function createResponse($httpVersion = HttpVersion::HTTP_1_1, $requestMethod = Http::GET, $sapi = null)
    {
        $mockRequest = $this->getMock('stubbles\input\web\WebRequest');
        $mockRequest->expects($this->any())
                    ->method('id')
                    ->will($this->returnValue('example-request-id-foo'));
        $mockRequest->expects($this->once())
                    ->method('protocolVersion')
                    ->will($this->returnValue(HttpVersion::castFrom($httpVersion)));
        $mockRequest->expects($this->any())
                    ->method('method')
                    ->will($this->returnValue($requestMethod));
        return $this->getMock(
                'stubbles\webapp\response\WebResponse',
                ['header', 'sendBody'],
                [$mockRequest, $sapi]
        );
    }

    /**
     * @test
     */
    public function versionIs1_1ByDefault()
    {
        $this->response->expects($this->at(0))
                       ->method('header')
                       ->with($this->equalTo('HTTP/1.1 200 OK'));
        $this->response->send();
    }

    /**
     * @test
     */
    public function versionCanBeSetOnConstruction()
    {
        $response = $this->createResponse(HttpVersion::HTTP_1_0);
        $response->expects($this->at(0))
                 ->method('header')
                 ->with($this->equalTo('HTTP/1.0 200 OK'));
        $response->send();
    }

    /**
     * @test
     */
    public function clearingResponseDoesNotResetVersion()
    {
        $response = $this->createResponse(HttpVersion::HTTP_1_0);
        $response->expects($this->at(0))
                 ->method('header')
                 ->with($this->equalTo('HTTP/1.0 200 OK'));
        $response->clear()
                 ->send();
    }

    /**
     * @since  1.5.0
     * @test
     * @expectedException  stubbles\lang\exception\IllegalArgumentException
     * @group  bug251
     */
    public function settingStatusCodeToInvalidValueThrowsIllegalArgumentException()
    {
        $this->response->setStatusCode(313);
    }

    /**
     * @test
     */
    public function clearResetsStatusCodeTo200()
    {
        $this->response->expects($this->at(0))
                       ->method('header')
                       ->with($this->equalTo('HTTP/1.1 404 Not Found'));
        $this->response->expects($this->at(2))
                       ->method('header')
                       ->with($this->equalTo('HTTP/1.1 200 OK'));
        $this->response->setStatusCode(404)
                       ->send()
                       ->clear()
                       ->send();
    }

    /**
     * @test
     * @since  4.0.0
     */
    public function statusCodeIs200ByDefault()
    {
        $this->assertEquals(200, $this->response->statusCode());
    }

    /**
     * @test
     * @since  4.0.0
     */
    public function statusCodeCanBeChanged()
    {
        $this->assertEquals(404, $this->response->setStatusCode(404)->statusCode());
    }

    /**
     * @test
     */
    public function statusCodeInCgiSapi()
    {
        $this->response = $this->createResponse(HttpVersion::HTTP_1_1, Http::GET, 'cgi');
        $this->response->expects($this->at(0))
                       ->method('header')
                       ->with($this->equalTo('Status: 200 OK'));
        $this->response->send();
    }

    /**
     * @test
     */
    public function addedHeadersAreSend()
    {
        $this->response->expects($this->at(1))
                       ->method('header')
                       ->with($this->equalTo('name: value1'));
        $this->response->addHeader('name', 'value1')
                       ->send();
    }

    /**
     * @test
     */
    public function addingHeaderWithSameNameReplacesExistingHeader()
    {
        $this->response->addHeader('name', 'value1')
                       ->addHeader('name', 'value2')
                       ->send();
        $this->assertEquals('value2', $this->response->headers()['name']);
    }

    /**
     * @test
     * @since  4.0.0
     */
    public function doesNotContainsNonAddedHeader()
    {
        $this->assertFalse($this->response->containsHeader('X-Foo'));
    }

    /**
     * @test
     * @since  4.0.0
     */
    public function doesNotContainsAddedHeaderWithDifferentValue()
    {
        $this->assertFalse(
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
        $this->assertTrue(
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
        $this->assertTrue(
                $this->response->addHeader('X-Foo', 'bar')
                               ->containsHeader('X-Foo', 'bar')
        );
    }

    /**
     * @test
     */
    public function clearingResponseRemovesAllHeadersExceptRequestId()
    {
        $this->response->expects($this->exactly(2))
                       ->method('header');
        $this->response->addHeader('name', 'value1')
                       ->clear()
                       ->send();
    }

    /**
     * creates mock cookie
     *
     * @param   string  $value  optional  cookie value
     * @return  \PHPUnit_Framework_MockObject_MockObject
     */
    protected function createMockCookie($value = null)
    {
        $mockCookie = $this->getMockBuilder('stubbles\webapp\response\Cookie')
                           ->disableOriginalConstructor()
                           ->getMock();
        $mockCookie->expects($this->any())
                   ->method('name')
                   ->will($this->returnValue('foo'));
        if (null !== $value) {
            $mockCookie->expects($this->any())
                       ->method('value')
                       ->will($this->returnValue($value));
        }
        return $mockCookie;
    }

    /**
     * @test
     */
    public function cookiesAreSend()
    {
        $mockCookie = $this->createMockCookie();
        $mockCookie->expects($this->once())->method('send');
        $this->response->addCookie($mockCookie)
                       ->send();
    }

    /**
     * @test
     */
    public function addingCookieWithSameNameReplacesExistingCookie()
    {
        $mockCookie = $this->createMockCookie();
        $mockCookie->expects($this->once())->method('send');
        $this->response->addCookie($mockCookie)
                       ->addCookie($mockCookie)
                       ->send();
    }

    /**
     * @test
     * @since  4.0.0
     */
    public function doesNotContainsNonAddedCookie()
    {
        $this->assertFalse($this->response->containsCookie('foo'));
    }

    /**
     * @test
     * @since  4.0.0
     */
    public function doesNotContainsAddedCookieWithDifferentValue()
    {
        $this->assertFalse(
                $this->response->addCookie($this->createMockCookie('bar'))
                               ->containsCookie('foo', 'baz')
        );
    }

    /**
     * @test
     * @since  4.0.0
     */
    public function containsAddedCookie()
    {
        $this->assertTrue(
                $this->response->addCookie($this->createMockCookie('bar'))
                               ->containsCookie('foo')
        );
    }

    /**
     * @test
     * @since  4.0.0
     */
    public function containsAddedCookieWithValue()
    {
        $this->assertTrue(
                $this->response->addCookie($this->createMockCookie('bar'))
                               ->containsCookie('foo', 'bar')
        );
    }

    /**
     * @test
     */
    public function clearingResponseRemovesAllCookies()
    {
        $this->response->expects($this->exactly(2))
                       ->method('header');
        $this->response->addCookie(Cookie::create('foo', 'bar'))
                       ->clear()
                       ->send();
    }

    /**
     * @test
     */
    public function hasNoBodyByDefault()
    {
        $this->response->expects($this->never())
                       ->method('sendBody');
        $this->response->send();
    }

    /**
     * @test
     */
    public function doesNotSendContentLengthHeaderWhenNoBodyPresent()
    {
        $this->response->expects($this->exactly(2))
                       ->method('header')
                       ->withConsecutive(
                               $this->equalTo(HttpVersion::HTTP_1_1 . ' 200 OK'),
                               $this->equalTo('X-Request-Id: example-request-id-foo')
                         );
        $this->response->send();
    }

    /**
     * @test
     */
    public function sendsContentLengthHeaderWhenBodyIsPresent()
    {
        $this->response->expects($this->at(1))
                       ->method('header')
                       ->with($this->equalTo('Content-Length: 3'));
        $this->response->write('foo')
                       ->send();
    }

    /**
     * @test
     */
    public function bodyIsSend()
    {
        $this->response->expects($this->once())
                       ->method('sendBody')
                       ->with($this->equalTo('foo'));
        $this->response->write('foo')
                       ->send();
    }

    /**
     * @test
     * @since  4.0.0
     */
    public function bodyIsNotSendWhenRequestMethodIsHead()
    {
        $this->response = $this->createResponse(HttpVersion::HTTP_1_1, Http::HEAD);
        $this->response->expects($this->never())
                       ->method('sendBody');
        $this->response->write('foo')
                       ->send();
    }

    /**
     * @test
     * @since  4.0.0
     */
    public function contentLengthHeaderIsSendWhenRequestMethodIsHeadAndBodyIsSet()
    {
        $this->response = $this->createResponse(HttpVersion::HTTP_1_1, Http::HEAD);
        $this->response->expects($this->at(1))
                       ->method('header')
                       ->with($this->equalTo('Content-Length: 3'));
        $this->response->write('foo')
                       ->send();
    }

    /**
     * @test
     */
    public function sendBodyDoesNotAddContentLengthHeaderWhenAlreadyInHeadersSetBefore()
    {
        // used wrong content length on purpose to distinguish between
        // internally added header which has correct length
        $this->response->addHeader('Content-Length', 10);
        $this->response->expects($this->at(0))
                       ->method('header')
                       ->with($this->equalTo('HTTP/1.1 200 OK'));
        $this->response->expects($this->at(1))
                       ->method('header')
                       ->with($this->equalTo('Content-Length: 10'));
        $this->response->expects($this->exactly(3))
                       ->method('header');
        $this->response->expects($this->once())
                       ->method('sendBody')
                       ->with($this->equalTo('foo'));
        $this->response->write('foo')
                       ->send();
    }

    /**
     * @test
     */
    public function doesNotWriteBodyIfNoBodyPresent()
    {
        $this->response->expects($this->never())
                       ->method('sendBody');
        $this->response->send();
    }

    /**
     * @test
     */
    public function clearingResponseRemovesBody()
    {
        $this->response->expects($this->never())
                       ->method('sendBody');
        $this->response->write('foo')
                       ->clear()
                       ->send();
    }

    /**
     * @test
     * @since  3.1.0
     * @group  final_response
     */
    public function isNotFixedByDefault()
    {
        $this->assertFalse($this->response->isFixed());
    }

    /**
     * @test
     * @since  3.1.0
     * @group  final_response
     */
    public function clearUnfixesResponse()
    {
        $this->assertFalse($this->response->forbidden()->clear()->isFixed());
    }

    /**
     * @since  1.3.0
     * @test
     */
    public function redirectAddsLocationHeaderAndStatusCode()
    {
        $this->response->expects($this->at(0))
                       ->method('header')
                       ->with($this->equalTo('HTTP/1.1 301 Moved Permanently'));
        $this->response->expects($this->at(1))
                       ->method('header')
                       ->with($this->equalTo('Location: http://example.com/'));
        $this->response->redirect('http://example.com/', 301)
                       ->send();
    }

    /**
     * @since  1.5.0
     * @test
     * @group  bug251
     */
    public function redirectWithoutStatusCodeAndReasonPhraseAddsLocationHeaderAndStatusCode302()
    {
        $this->response->expects($this->at(0))
                       ->method('header')
                       ->with($this->equalTo('HTTP/1.1 302 Found'));
        $this->response->expects($this->at(1))
                       ->method('header')
                       ->with($this->equalTo('Location: http://example.com/'));
        $this->response->redirect('http://example.com/')
                       ->send();
    }

    /**
     * @since  1.5.0
     * @test
     */
    public function sendReturnsItself()
    {
        $this->assertSame($this->response, $this->response->send());
    }

    /**
     * @since  2.0.0
     * @test
     */
    public function forbiddenSetsStatusCodeTo403()
    {
        $this->response->expects($this->at(0))
                       ->method('header')
                       ->with($this->equalTo('HTTP/1.1 403 Forbidden'));
        $this->response->forbidden()
                       ->send();
    }

    /**
     * @test
     * @since  3.1.0
     * @group  final_response
     */
    public function forbiddenFixatesResponse()
    {
        $this->assertTrue($this->response->forbidden()->isFixed());
    }

    /**
     * @since  2.0.0
     * @test
     */
    public function notFoundSetsStatusCodeTo404()
    {
        $this->response->expects($this->at(0))
                       ->method('header')
                       ->with($this->equalTo('HTTP/1.1 404 Not Found'));
        $this->response->notFound()
                       ->send();
    }

    /**
     * @test
     * @since  3.1.0
     * @group  final_response
     */
    public function notFoundFixatesResponse()
    {
        $this->assertTrue($this->response->notFound()->isFixed());
    }

    /**
     * @since  2.0.0
     * @test
     */
    public function methodNotAllowedSetsStatusCodeTo405()
    {
        $this->response->expects($this->at(0))
                       ->method('header')
                       ->with($this->equalTo('HTTP/1.1 405 Method Not Allowed'));
        $this->response->expects($this->at(1))
                       ->method('header')
                       ->with($this->equalTo('Allow: GET, HEAD'));
        $this->response->methodNotAllowed('POST', ['GET', 'HEAD'])
                       ->send();
    }

    /**
     * @test
     * @since  3.1.0
     * @group  final_response
     */
    public function methodNotAllowedFixatesResponse()
    {
        $this->assertTrue($this->response->methodNotAllowed('POST', ['GET', 'HEAD'])->isFixed());
    }

    /**
     * @since  2.0.0
     * @test
     */
    public function notAcceptableSetsStatusCodeTo406()
    {
        $this->response->expects($this->at(0))
                       ->method('header')
                       ->with($this->equalTo('HTTP/1.1 406 Not Acceptable'));
        $this->response->notAcceptable()
                       ->send();
    }

    /**
     * @test
     * @since  3.1.0
     * @group  final_response
     */
    public function notAcceptableFixatesResponse()
    {
        $this->assertTrue($this->response->notAcceptable()->isFixed());
    }

    /**
     * @since  2.0.0
     * @test
     */
    public function notAcceptableWithSupportedMimeTypesSetsStatusCodeTo406()
    {
        $this->response->expects($this->at(0))
                       ->method('header')
                       ->with($this->equalTo('HTTP/1.1 406 Not Acceptable'));
        $this->response->expects($this->at(1))
                       ->method('header')
                       ->with($this->equalTo('X-Acceptable: application/json, application/xml'));
        $this->response->notAcceptable(['application/json', 'application/xml'])
                       ->send();
    }

    /**
     * @since  2.0.0
     * @test
     */
    public function internalServerErrorSetsStatusCodeTo500()
    {
        $this->response->expects($this->at(0))
                       ->method('header')
                       ->with($this->equalTo('HTTP/1.1 500 Internal Server Error'));
        $this->response->expects($this->once())
                       ->method('sendBody')
                       ->with($this->equalTo('ups!'));
        $this->response->internalServerError('ups!')
                       ->send();
    }

    /**
     * @test
     * @since  3.1.0
     * @group  final_response
     */
    public function internalServerErrorFixatesResponse()
    {
        $this->assertTrue($this->response->internalServerError('ups')->isFixed());
    }

    /**
     * @since  2.0.0
     * @test
     */
    public function httpVersionNotSupportedSetsStatusCodeTo505()
    {
        $this->response->expects($this->at(0))
                       ->method('header')
                       ->with($this->equalTo('HTTP/1.1 505 HTTP Version Not Supported'));
        $this->response->expects($this->once())
                       ->method('sendBody')
                       ->with($this->equalTo('Unsupported HTTP protocol version, expected HTTP/1.0 or HTTP/1.1'));
        $this->response->httpVersionNotSupported()
                       ->send();
    }

    /**
     * @test
     * @since  3.1.0
     * @group  final_response
     */
    public function httpVersionNotSupportedFixatesResponse()
    {
        $this->assertTrue($this->response->httpVersionNotSupported()->isFixed());
    }

    /**
     * @return  array
     */
    public function unsupportedHttpVersions()
    {
        return [
            [HttpVersion::fromString('HTTP/0.9')],
            [HttpVersion::fromString('HTTP/2.0')]
        ];
    }

    /**
     * @since  4.0.0
     * @param  HttpVersion  $unsupportedHttpVersion
     * @test
     * @dataProvider  unsupportedHttpVersions
     */
    public function createInstanceWithHttpMajorVersionOtherThanOneFixatesResponseToHttpVersionNotSupported(HttpVersion $unsupportedHttpVersion)
    {
        $response = $this->createResponse($unsupportedHttpVersion);
        $this->assertTrue($response->isFixed());
        $response->expects($this->at(0))
                 ->method('header')
                 ->with($this->equalTo('HTTP/1.1 505 HTTP Version Not Supported'));
        $response->expects($this->once())
                 ->method('sendBody')
                 ->with($this->equalTo('Unsupported HTTP protocol version, expected HTTP/1.0 or HTTP/1.1'));
        $response->send();
    }

    /**
     * @test
     * @group  issue_74
     * @since  5.1.0
     */
    public function requestIdAddedByDefault()
    {
        $this->response->expects($this->at(1))
                       ->method('header')
                       ->with($this->equalTo('X-Request-ID: example-request-id-foo'));
        $this->response->send();
    }

    /**
     * @test
     * @group  issue_74
     * @since  5.1.0
     */
    public function requestIdCanBeChanged()
    {
        $this->response->headers()->requestId('another-request-id-bar');
        $this->response->expects($this->at(1))
                       ->method('header')
                       ->with($this->equalTo('X-Request-ID: another-request-id-bar'));
        $this->response->send();
    }

    /**
     * @test
     * @group  issue_74
     * @since  5.1.0
     */
    public function requestIdIsResetToOriginalValueOnClear()
    {
        $this->response->headers()->requestId('another-request-id-bar');
        $this->response->expects($this->at(1))
                       ->method('header')
                       ->with($this->equalTo('X-Request-ID: example-request-id-foo'));
        $this->response->clear()->send();
    }
}
