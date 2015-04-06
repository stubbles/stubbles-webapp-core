<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  stubbles\webapp
 */
namespace stubbles\webapp\response\mimetypes;
use stubbles\lang\reflect;
use stubbles\streams\memory\MemoryOutputStream;
/**
 * Tests for stubbles\webapp\response\mimetypes\Xml.
 *
 * @group  response
 * @group  mimetypes
 * @since  6.0.0
 */
class XmlTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @type  \stubbles\webapp\response\mimetypes\Xml
     */
    private $xml;
    /**
     * @type  \PHPUnit_Framework_MockObject_MockObject
     */
    private $xmlSerializerFacade;


    /**
     * set up test environment
     */
    public function setUp()
    {
        $this->xmlSerializerFacade = $this->getMockBuilder('stubbles\xml\serializer\XmlSerializerFacade')
                ->disableOriginalConstructor()
                ->getMock();
        $this->xml = new Xml($this->xmlSerializerFacade);
    }

    /**
     * @test
     */
    public function annotationsPresent()
    {
        assertTrue(
                reflect\annotationsOfConstructor($this->xml)
                        ->contain('Inject')
        );
    }

    /**
     * @test
     */
    public function defaultMimeType()
    {
        assertEquals(
                'application/xml',
                (string) $this->xml
        );
    }

    /**
     * @test
     */
    public function mimeTypeCanBeSpecialised()
    {
        assertEquals(
                'text/xml',
                (string) $this->xml->specialise('text/xml')
        );
    }

    /**
     * @test
     */
    public function serializesResourceToXml()
    {
        $this->xmlSerializerFacade->method('serializeToXml')
                ->with(equalTo('value'))
                ->will(returnValue('<xml/>'));
        assertEquals(
                '<xml/>',
                $this->xml->serialize(
                        'value',
                        new MemoryOutputStream()
                )->buffer()
        );
    }
}
