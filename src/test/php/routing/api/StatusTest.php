<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  stubbles\webapp
 */
namespace stubbles\webapp\routing\api;
use stubbles\ioc\Binder;
use stubbles\xml\serializer\XmlSerializerFacade;
/**
 * Test for stubbles\webapp\routing\api\Status.
 *
 * @since  6.1.0
 * @group  routing
 * @group  routing_api
 */
class StatusTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @type  \stubbles\webapp\routing\api\Status
     */
    private $status;

    /**
     * set up test environment
     */
    public function setUp()
    {
        $this->status = new Status(200, 'Default <b>response</b> code');
    }

    /**
     * @test
     */
    public function returnsProvidedStatusCode()
    {
        assertEquals(200, $this->status->code());
    }

    /**
     * @test
     */
    public function returnsProvidedDescription()
    {
        assertEquals('Default <b>response</b> code', $this->status->description());
    }

    /**
     * @test
     */
    public function canBeSerializedToJson()
    {
        assertEquals(
                '{"code":200,"description":"Default <b>response<\/b> code"}',
                json_encode($this->status)
        );
    }

    /**
     * @test
     */
    public function canBeSerializedToXml()
    {
        $binder = new Binder();
        assertEquals(
                '<?xml version="1.0" encoding="UTF-8"?>
<status code="200"><description>Default <b>response</b> code</description></status>',
                $binder->getInjector()
                        ->getInstance(XmlSerializerFacade::class)
                        ->serializeToXml($this->status)
        );
    }
}
