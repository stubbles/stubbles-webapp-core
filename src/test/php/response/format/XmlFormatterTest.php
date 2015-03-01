<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  stubbles\webapp\rest
 */
namespace stubbles\webapp\response\format;
use stubbles\lang\reflect;
use stubbles\webapp\response\Headers;
/**
 * Tests for stubbles\webapp\response\format\XmlFormatter.
 *
 * @since  1.1.0
 * @group  format
 */
class XmlFormatterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * instance to test
     *
     * @type  XmlFormatter
     */
    private $xmlFormatter;
    /**
     * mocked xml serializer facade
     *
     * @type  \PHPUnit_Framework_MockObject_MockObject
     */
    private $mockXmlSerializerFacade;

    /**
     * set up test environment
     */
    public function setUp()
    {
        $this->mockXmlSerializerFacade = $this->getMockBuilder('stubbles\xml\serializer\XmlSerializerFacade')
                                              ->disableOriginalConstructor()
                                              ->getMock();
        $this->xmlFormatter            = new XmlFormatter($this->mockXmlSerializerFacade);
    }

    /**
     * @test
     */
    public function annotationsPresent()
    {
        $this->assertTrue(
                reflect\constructorAnnotationsOf($this->xmlFormatter)
                        ->contain('Inject')
        );
    }

    /**
     * @test
     */
    public function formatsXml()
    {
        $this->mockXmlSerializerFacade->expects($this->once())
                                      ->method('serializeToXml')
                                      ->with($this->equalTo('value'))
                                      ->will($this->returnValue('<xml/>'));
        $this->assertEquals('<xml/>',
                            $this->xmlFormatter->format('value', new Headers())
        );
    }

    /**
     * @test
     */
    public function formatForbiddenErrorReturnsXml()
    {
        $this->mockXmlSerializerFacade->expects($this->once())
                                      ->method('serializeToXml')
                                      ->with($this->equalTo(['error' => 'You are not allowed to access this resource.']))
                                      ->will($this->returnValue('<xml/>'));
        $this->assertEquals('<xml/>',
                            $this->xmlFormatter->formatForbiddenError()
        );
    }

    /**
     * @test
     */
    public function formatNotFoundErrorReturnsXml()
    {
        $this->mockXmlSerializerFacade->expects($this->once())
                                      ->method('serializeToXml')
                                      ->with($this->equalTo(['error' => 'Given resource could not be found.']))
                                      ->will($this->returnValue('<xml/>'));
        $this->assertEquals('<xml/>',
                            $this->xmlFormatter->formatNotFoundError()
        );
    }

    /**
     * @test
     */
    public function formatMethodNotAllowedErrorReturnsXml()
    {
        $this->mockXmlSerializerFacade->expects($this->once())
                                      ->method('serializeToXml')
                                      ->with($this->equalTo(['error' => 'The given request method PUT is not valid. Please use one of GET, POST, DELETE.']))
                                      ->will($this->returnValue('<xml/>'));
        $this->assertEquals('<xml/>',
                            $this->xmlFormatter->formatMethodNotAllowedError('put', ['GET', 'POST', 'DELETE'])
        );
    }

    /**
     * @test
     */
    public function formatInternalServerErrorReturnsXml()
    {
        $this->mockXmlSerializerFacade->expects($this->once())
                                      ->method('serializeToXml')
                                      ->with($this->equalTo(['error' => 'Internal Server Error: Error message']))
                                      ->will($this->returnValue('<xml/>'));
        $this->assertEquals('<xml/>',
                            $this->xmlFormatter->formatInternalServerError('Error message')
        );
    }
}
