<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  net\stubbles\webapp
 */
namespace net\stubbles\webapp\response;
/**
 * Tests for net\stubbles\webapp\response\FormatterResponse.
 *
 * @group  response
 */
class FormatterResponseTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * instance to test
     *
     * @type  FormatterResponse
     */
    private $formatterResponse;
    /**
     * decorated response
     *
     * @type  \PHPUnit_Framework_MockObject_MockObject
     */
    private $decoratedResponse;
    /**
     * mocked formatter
     *
     * @type  \PHPUnit_Framework_MockObject_MockObject
     */
    private $mockFormatter;

    /**
     * set up test environment
     */
    public function setUp()
    {
        $this->decoratedResponse = $this->getMock('net\stubbles\webapp\response\WebResponse');
        $this->mockFormatter     = $this->getMock('net\stubbles\webapp\response\format\Formatter');
        $this->formatterResponse = new FormatterResponse($this->decoratedResponse,
                                                         $this->mockFormatter,
                                                         'text/plain'
                                   );
    }

    /**
     * @test
     */
    public function clearsDataFromDecoratedResponse()
    {
        $this->decoratedResponse->expects($this->once())
                                ->method('clear');
        $this->assertSame($this->formatterResponse,
                          $this->formatterResponse->clear()
        );
    }

    /**
     * @test
     */
    public function setsStatusCodeOnDecoratedResponse()
    {
        $this->decoratedResponse->expects($this->once())
                                ->method('setStatusCode')
                                ->with($this->equalTo(418));
        $this->assertSame($this->formatterResponse,
                          $this->formatterResponse->setStatusCode(418)
        );
    }

    /**
     * @test
     */
    public function addsHeaderOnDecoratedResponse()
    {
        $this->decoratedResponse->expects($this->once())
                                ->method('addHeader')
                                ->with($this->equalTo('X-Binford'), $this->equalTo('6100'));
        $this->assertSame($this->formatterResponse,
                          $this->formatterResponse->addHeader('X-Binford', '6100')
        );
    }

    /**
     * @test
     */
    public function addsCookieOnDecoratedResponse()
    {
        $cookie = Cookie::create('foo', 'bar');
        $this->decoratedResponse->expects($this->once())
                                ->method('addCookie')
                                ->with($this->equalTo($cookie));
        $this->assertSame($this->formatterResponse,
                          $this->formatterResponse->addCookie($cookie)
        );
    }

    /**
     * @test
     */
    public function removesCookieOnDecoratedResponse()
    {
        $this->decoratedResponse->expects($this->once())
                                ->method('removeCookie')
                                ->with($this->equalTo('foo'));
        $this->assertSame($this->formatterResponse,
                          $this->formatterResponse->removeCookie('foo')
        );
    }

    /**
     * @test
     */
    public function writesOnDecoratedResponse()
    {
        $this->mockFormatter->expects($this->never())
                            ->method('format');
        $this->decoratedResponse->expects($this->once())
                                ->method('write')
                                ->with($this->equalTo('foo'));
        $this->assertSame($this->formatterResponse,
                          $this->formatterResponse->write('foo')
        );
    }

    /**
     * @test
     */
    public function writesUsingFormatterIfBodyNoStringOnDecoratedResponse()
    {
        $this->mockFormatter->expects($this->once())
                            ->method('format')
                            ->with($this->equalTo(array('foo' => 'bar')))
                            ->will($this->returnValue('foo: bar'));
        $this->decoratedResponse->expects($this->once())
                                ->method('write')
                                ->with($this->equalTo('foo: bar'));
        $this->assertSame($this->formatterResponse,
                          $this->formatterResponse->write(array('foo' => 'bar'))
        );
    }

    /**
     * @test
     */
    public function writeForbiddenErrorUsingFormatterOnDecoratedResponse()
    {
        $this->mockFormatter->expects($this->once())
                            ->method('formatForbiddenError')
                            ->will($this->returnValue('No access granted here'));
        $this->decoratedResponse->expects($this->once())
                                ->method('setStatusCode')
                                ->with($this->equalTo(403))
                                ->will($this->returnSelf());
        $this->decoratedResponse->expects($this->once())
                                ->method('write')
                                ->with($this->equalTo('No access granted here'));
        $this->assertSame($this->formatterResponse,
                          $this->formatterResponse->forbidden()
        );
    }

    /**
     * @test
     */
    public function writeNotFoundErrorUsingFormatterOnDecoratedResponse()
    {
        $this->mockFormatter->expects($this->once())
                            ->method('formatNotFoundError')
                            ->will($this->returnValue('Not found'));
        $this->decoratedResponse->expects($this->once())
                                ->method('setStatusCode')
                                ->with($this->equalTo(404))
                                ->will($this->returnSelf());
        $this->decoratedResponse->expects($this->once())
                                ->method('write')
                                ->with($this->equalTo('Not found'));
        $this->assertSame($this->formatterResponse,
                          $this->formatterResponse->notFound()
        );
    }

    /**
     * @test
     */
    public function writeMethodNotAllowedErrorUsingFormatterOnDecoratedResponse()
    {
        $this->decoratedResponse->expects($this->once())
                                ->method('setStatusCode')
                                ->with($this->equalTo(405))
                                ->will($this->returnSelf());
        $this->decoratedResponse->expects($this->once())
                                ->method('addHeader')
                                ->with($this->equalTo('Allow'), $this->equalTo('GET, HEAD'))
                                ->will($this->returnSelf());
        $this->mockFormatter->expects($this->once())
                            ->method('formatMethodNotAllowedError')
                            ->with($this->equalTo('POST'), $this->equalTo(array('GET', 'HEAD')))
                            ->will($this->returnValue('No way to POST here, use GET or HEAD'));
        $this->decoratedResponse->expects($this->once())
                                ->method('write')
                                ->with($this->equalTo('No way to POST here, use GET or HEAD'));
        $this->assertSame($this->formatterResponse,
                          $this->formatterResponse->methodNotAllowed('POST', array('GET', 'HEAD'))
        );
    }

    /**
     * @test
     */
    public function writeInternalServerErrorUsingFormatterOnDecoratedResponse()
    {
        $this->decoratedResponse->expects($this->once())
                                ->method('setStatusCode')
                                ->with($this->equalTo(500))
                                ->will($this->returnSelf());
        $this->mockFormatter->expects($this->once())
                            ->method('formatInternalServerError')
                            ->with($this->equalTo('Ups!'))
                            ->will($this->returnValue('Something wrent wrong: Ups!'));
        $this->decoratedResponse->expects($this->once())
                                ->method('write')
                                ->with($this->equalTo('Something wrent wrong: Ups!'));
        $this->assertSame($this->formatterResponse,
                          $this->formatterResponse->internalServerError('Ups!')
        );
    }

    /**
     * @test
     */
    public function addsRedirectOnDecoratedResponseWithDefaultStatusCode()
    {
        $this->decoratedResponse->expects($this->once())
                                ->method('redirect')
                                ->with($this->equalTo('http://example.net/'), $this->equalTo(302));
        $this->assertSame($this->formatterResponse,
                          $this->formatterResponse->redirect('http://example.net/')
        );
    }

    /**
     * @test
     */
    public function addsRedirectOnDecoratedResponseWithGivenStatusCode()
    {
        $this->decoratedResponse->expects($this->once())
                                ->method('redirect')
                                ->with($this->equalTo('http://example.net/'), $this->equalTo(301));
        $this->assertSame($this->formatterResponse,
                          $this->formatterResponse->redirect('http://example.net/', 301)
        );
    }

    /**
     * @test
     */
    public function sendsAddsContentTypeHeaderToDecoratedResponseWhenMimeTypeNotNull()
    {
        $this->decoratedResponse->expects($this->once())
                                ->method('addHeader')
                                ->with($this->equalTo('Content-type'), $this->equalTo('text/plain'));
        $this->decoratedResponse->expects($this->once())
                                ->method('send');
        $this->assertSame($this->formatterResponse,
                          $this->formatterResponse->send()
        );
    }

    /**
     * @test
     */
    public function sendsDoesNotAddContentTypeHeaderToDecoratedResponseWhenMimeTypeIsNull()
    {
        $this->formatterResponse = new FormatterResponse($this->decoratedResponse,
                                                         $this->mockFormatter,
                                                         null
                                   );
        $this->decoratedResponse->expects($this->never())
                                ->method('addHeader');
        $this->decoratedResponse->expects($this->once())
                                ->method('send');
        $this->assertSame($this->formatterResponse,
                          $this->formatterResponse->send()
        );
    }
}
?>