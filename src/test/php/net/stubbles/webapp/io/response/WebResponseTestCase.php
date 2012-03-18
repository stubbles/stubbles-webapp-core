<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  net\stubbles\webapp
 */
namespace net\stubbles\webapp\io\response;
/**
 * Tests for net\stubbles\webapp\io\response\WebResponse.
 *
 * @group  webapp
 * @group  webapp_io
 * @group  webapp_io_response
 */
class WebResponseTestCase extends \PHPUnit_Framework_TestCase
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
        $this->response = $this->getMock('net\\stubbles\\webapp\\io\\response\\WebResponse',
                                         array('header', 'sendBody')
                          );
    }

    /**
     * @test
     */
    public function versionIs1_1ByDefault()
    {
        $this->assertEquals('1.1', $this->response->getVersion());
    }

    /**
     * @test
     */
    public function versionCanBeSetOnConstruction()
    {
        $response = new WebResponse('1.0');
        $this->assertEquals('1.0', $response->getVersion());
    }

    /**
     * @test
     */
    public function clearingResponseDoesNotResetVersion()
    {
        $response = new WebResponse('1.0');
        $this->assertEquals('1.0',
                            $response->clear()
                                     ->getVersion()
        );
    }

    /**
     * @test
     */
    public function hasStatusCode200ByDefault()
    {
        $this->assertEquals(200, $this->response->getStatusCode());
    }

    /**
     * @since  1.5.0
     * @test
     * @expectedException  net\stubbles\lang\exception\IllegalArgumentException
     * @group  bug251
     */
    public function settingStatusCodeToInvalidValueThrowsIllegalArgumentException()
    {
        $this->response->setStatusCode(313);
    }

    /**
     * @test
     */
    public function clearingResponseResetsStatusCodeTo200()
    {
        $this->assertEquals(200,
                            $this->response->setStatusCode(500)
                                           ->clear()
                                           ->getStatusCode()
        );
    }

    /**
     * @test
     */
    public function statusCodeInCgiSapi()
    {
        $this->response = $this->getMock('net\\stubbles\\webapp\\io\\response\\WebResponse',
                                         array('header', 'sendBody'),
                                         array('1.1', 'cgi')
                          );
        $this->assertEquals(200, $this->response->getStatusCode());
        $this->response->expects($this->once())
                       ->method('header')
                       ->with($this->equalTo('Status: 200 OK'));
        $this->response->send();
    }

    /**
     * @test
     */
    public function statusCodeChangedInCgiSapi()
    {
        $this->response = $this->getMock('net\\stubbles\\webapp\\io\\response\\WebResponse',
                                         array('header', 'sendBody'),
                                         array('1.1', 'cgi')
                          );
        $this->response->setStatusCode(404);
        $this->response->expects($this->once())
                       ->method('header')
                       ->with($this->equalTo('Status: 404 Not Found'));
        $this->assertEquals(200,
                            $this->response->send()
                                           ->clear()
                                           ->getStatusCode()
        );
    }

    /**
     * @test
     */
    public function statusCodeInOtherSapi()
    {
        $this->assertEquals(200, $this->response->getStatusCode());
        $this->response->expects($this->once())
                       ->method('header')
                       ->with($this->equalTo('HTTP/1.1 200 OK'));
        $this->response->send();
    }

    /**
     * @test
     */
    public function statusCodeChangedInOtherSapi()
    {
        $this->assertEquals(404,
                            $this->response->setStatusCode(404)
                                           ->getStatusCode()
        );
        $this->response->expects($this->once())
                       ->method('header')
                       ->with($this->equalTo('HTTP/1.1 404 Not Found'));
        $this->assertEquals(200,
                            $this->response->send()
                                           ->clear()
                                           ->getStatusCode()
        );
    }

    /**
     * @test
     */
    public function hasNoHeadersByDefault()
    {
        $this->assertEquals(array(), $this->response->getHeaders());
    }

    /**
     * @since  1.5.0
     * @test
     * @group  bug253
     */
    public function checkForNonExistingHeaderReturnsFalse()
    {
        $this->assertFalse($this->response->hasHeader('doesNotExist'));
    }

    /**
     * @since  1.5.0
     * @test
     * @group  bug253
     */
    public function retrieveNonExistingHeaderReturnsNull()
    {
        $this->assertNull($this->response->getHeader('doesNotExist'));
    }

    /**
     * @since  1.5.0
     * @test
     * @group  bug253
     */
    public function checkForExistingHeaderReturnsTrue()
    {
        $this->assertTrue($this->response->addHeader('name', 'value1')
                                         ->hasHeader('name')
        );
    }

    /**
     * @since  1.5.0
     * @test
     * @group  bug253
     */
    public function retrieveExistingHeaderReturnsValueOfHeader()
    {
        $this->assertEquals('value1',
                            $this->response->addHeader('name', 'value1')
                                           ->getHeader('name')
        );
    }

    /**
     * @test
     */
    public function addedHeadersAreSend()
    {
        $this->assertEquals(array('name' => 'value1'),
                            $this->response->addHeader('name', 'value1')
                                           ->getHeaders()
        );
        $this->response->expects($this->at(1))
                       ->method('header')
                       ->with($this->equalTo('name: value1'));
        $this->response->send();
    }

    /**
     * @test
     */
    public function addingHeaderWithSameNameReplacesExistingHeader()
    {
        $this->assertEquals(array('name' => 'value2'),
                            $this->response->addHeader('name', 'value1')
                                           ->addHeader('name', 'value2')
                                           ->getHeaders()
        );
        $this->response->expects($this->at(1))
                       ->method('header')
                       ->with($this->equalTo('name: value2'));
        $this->response->send();
    }

    /**
     * @test
     */
    public function clearingResponseRemovesAllHeaders()
    {
        $this->assertEquals(array(),
                            $this->response->addHeader('name', 'value1')
                                           ->clear()
                                           ->getHeaders()
        );
    }

    /**
     * @test
     */
    public function hasNoCookiesByDefault()
    {
        $this->assertEquals(array(), $this->response->getCookies());
    }

    /**
     * creates mock cookie
     *
     * @return  \PHPUnit_Framework_MockObject_MockObject
     */
    protected function createMockCookie()
    {
        $mockCookie = $this->getMock('net\\stubbles\\webapp\\io\\response\\Cookie',
                                     array(),
                                     array('foo', 'bar')
                      );
        $mockCookie->expects($this->any())
                   ->method('getName')
                   ->will($this->returnValue('foo'));
        return $mockCookie;
    }

    /**
     * @test
     */
    public function cookiesAreSend()
    {
        $mockCookie = $this->createMockCookie();
        $this->assertEquals(array('foo' => $mockCookie),
                            $this->response->addCookie($mockCookie)
                                           ->getCookies()
        );
        $mockCookie->expects($this->once())->method('send');
        $this->response->send();
    }

    /**
     * @test
     */
    public function addingCookieWithSameNameReplacesExistingCookie()
    {
        $mockCookie = $this->createMockCookie();
        $this->assertEquals(array('foo' => $mockCookie),
                            $this->response->addCookie($mockCookie)
                                           ->addCookie($mockCookie)
                                           ->getCookies()
        );
        $mockCookie->expects($this->once())->method('send');
        $this->response->send();
    }

    /**
     * @since  1.5.0
     * @test
     * @group  bug253
     */
    public function checkForNonExistingCookieReturnsFalse()
    {
        $this->assertFalse($this->response->hasCookie('doesNotExist'));
    }

    /**
     * @since  1.5.0
     * @test
     * @group  bug253
     */
    public function retrieveNonExistingCookieReturnsNull()
    {
        $this->assertNull($this->response->getCookie('doesNotExist'));
    }

    /**
     * @since  1.5.0
     * @test
     * @group  bug253
     */
    public function checkForExistingCookieReturnsTrue()
    {
        $mockCookie = $this->createMockCookie();
        $this->assertTrue($this->response->addCookie($mockCookie)
                                         ->hasCookie('foo')
        );
    }

    /**
     * @since  1.5.0
     * @test
     * @group  bug253
     */
    public function retrieveExistingCookieReturnsCookie()
    {
        $cookie = Cookie::create('foo', 'bar');
        $this->assertSame($cookie,
                          $this->response->addCookie($cookie)
                                         ->getCookie('foo')
        );
    }

    /**
     * @test
     */
    public function clearingResponseRemovesAllCookies()
    {
        $this->assertEquals(array(),
                            $this->response->addCookie(Cookie::create('foo', 'bar'))
                                           ->clear()
                                           ->getCookies()
        );
    }

    /**
     * @test
     */
    public function hasNoBodyByDefault()
    {
        $this->assertNull($this->response->getBody());
    }

    /**
     * @test
     */
    public function replaceBodyRemovesOldBodyCompletely()
    {
        $this->assertEquals('foo',
                            $this->response->write('foo')
                                           ->getBody()
        );
        $this->assertEquals('bar',
                            $this->response->replaceBody('bar')
                                           ->getBody()
        );
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
     */
    public function clearingResponseRemovesBody()
    {
        $this->assertNull($this->response->write('foo')
                                         ->clear()
                                         ->getBody()
        );
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
     * @since  1.7.0
     * @test
     * @group  bug263
     */
    public function mergeDoesNotChangeHttpVersion()
    {
        $this->assertEquals('1.1', $this->response->getVersion());
        $responseToMerge = new WebResponse('1.0');
        $this->assertEquals('1.1',
                            $this->response->merge($responseToMerge)
                                           ->getVersion()
        );
    }

    /**
     * @since  1.7.0
     * @test
     * @group  bug263
     */
    public function mergeSetsStatusCodeToStatusCodeOfResponseToMerge()
    {
        $this->assertEquals(200, $this->response->getStatusCode());
        $responseToMerge = new WebResponse();
        $responseToMerge->setStatusCode(201);
        $this->assertEquals(201,
                            $this->response->merge($responseToMerge)
                                           ->getStatusCode()
        );
    }

    /**
     * @since  1.7.0
     * @test
     * @group  bug263
     */
    public function mergeSetsResponseBodyToBodyIfResponseToMerge()
    {
        $this->assertEquals('foo', $this->response->write('foo')->getBody());
        $responseToMerge = new WebResponse();
        $responseToMerge->write('bar');
        $this->assertEquals('bar',
                            $this->response->merge($responseToMerge)
                                           ->getBody()
        );
    }

    /**
     * @since  1.7.0
     * @test
     * @group  bug263
     */
    public function mergeAddsHeadersFromResponseToMerge()
    {
        $this->assertEquals(array(), $this->response->getHeaders());
        $responseToMerge = new WebResponse();
        $responseToMerge->addHeader('foo', 'bar');
        $this->assertEquals(array('foo' => 'bar'),
                            $this->response->merge($responseToMerge)
                                           ->getHeaders()
        );
    }

    /**
     * @since  1.7.0
     * @test
     * @group  bug263
     */
    public function mergeOverwritesExistingHeadersWithHeadersFromResponseToMerge()
    {
        $this->assertEquals(array('foo' => 'bar'),
                            $this->response->addHeader('foo', 'bar')
                                           ->getHeaders()
        );
        $responseToMerge = new WebResponse();
        $responseToMerge->addHeader('foo', 'baz');
        $this->assertEquals(array('foo' => 'baz'),
                            $this->response->merge($responseToMerge)
                                           ->getHeaders()
        );
    }

    /**
     * @since  1.7.0
     * @test
     * @group  bug263
     */
    public function mergeAddsCookiesFromResponseToMerge()
    {
        $this->assertEquals(array(), $this->response->getCookies());
        $responseToMerge = new WebResponse();
        $cookie = Cookie::create('foo', 'bar');
        $responseToMerge->addCookie($cookie);
        $this->assertEquals(array('foo' => $cookie),
                            $this->response->merge($responseToMerge)
                                           ->getCookies()
        );
    }

    /**
     * @since  1.7.0
     * @test
     * @group  bug263
     */
    public function mergeOverwritesExistingCookiesWithCookiesFromResponseToMerge()
    {
        $cookie1 = Cookie::create('foo', 'bar');
        $this->assertEquals(array('foo' => $cookie1),
                            $this->response->addCookie($cookie1)
                                           ->getCookies()
        );
        $responseToMerge = new WebResponse();
        $cookie2 = Cookie::create('foo', 'baz');
        $responseToMerge->addCookie($cookie2);
        $this->assertEquals(array('foo' => $cookie2),
                            $this->response->merge($responseToMerge)
                                           ->getCookies()
        );
    }
}
?>