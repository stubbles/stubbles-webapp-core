<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  net\stubbles\webapp\rest
 */
namespace net\stubbles\webapp\response\format;
/**
 * Tests for net\stubbles\webapp\response\format\XmlFormatter.
 *
 * @since  1.1.0
 * @group  format
 */
class XmlFormatterTestCase extends \PHPUnit_Framework_TestCase
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
        $this->mockXmlSerializerFacade = $this->getMockBuilder('net\stubbles\xml\serializer\XmlSerializerFacade')
                                              ->disableOriginalConstructor()
                                              ->getMock();
        $this->xmlFormatter            = new XmlFormatter($this->mockXmlSerializerFacade);
    }

    /**
     * @test
     */
    public function annotationsPresent()
    {
        $this->assertTrue($this->xmlFormatter->getClass()
                                             ->getConstructor()
                                             ->hasAnnotation('Inject')
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
                            $this->xmlFormatter->format('value')
        );
    }

    /**
     * @test
     */
    public function formatForbiddenErrorReturnsXml()
    {
        $this->mockXmlSerializerFacade->expects($this->once())
                                      ->method('serializeToXml')
                                      ->with($this->equalTo(array('error' => 'You are not allowed to access this resource.')))
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
                                      ->with($this->equalTo(array('error' => 'Given resource could not be found.')))
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
                                      ->with($this->equalTo(array('error' => 'The given request method PUT is not valid. Please use one of GET, POST, DELETE.')))
                                      ->will($this->returnValue('<xml/>'));
        $this->assertEquals('<xml/>',
                            $this->xmlFormatter->formatMethodNotAllowedError('put', array('GET', 'POST', 'DELETE'))
        );
    }

    /**
     * @test
     */
    public function formatInternalServerErrorReturnsXml()
    {
        $this->mockXmlSerializerFacade->expects($this->once())
                                      ->method('serializeToXml')
                                      ->with($this->equalTo(array('error' => 'Internal Server Error: Error message')))
                                      ->will($this->returnValue('<xml/>'));
        $this->assertEquals('<xml/>',
                            $this->xmlFormatter->formatInternalServerError('Error message')
        );
    }
}
?>