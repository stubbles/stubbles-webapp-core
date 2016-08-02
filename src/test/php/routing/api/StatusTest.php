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
namespace stubbles\webapp\routing\api;
use stubbles\ioc\Binder;
use stubbles\xml\serializer\XmlSerializerFacade;

use function bovigo\assert\assert;
use function bovigo\assert\predicate\equals;
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
        assert($this->status->code(), equals(200));
    }

    /**
     * @test
     */
    public function returnsProvidedDescription()
    {
        assert($this->status->description(), equals('Default <b>response</b> code'));
    }

    /**
     * @test
     */
    public function canBeSerializedToJson()
    {
        assert(
                json_encode($this->status),
                equals('{"code":200,"description":"Default <b>response<\/b> code"}')
        );
    }

    /**
     * @test
     */
    public function canBeSerializedToXml()
    {
        $binder = new Binder();
        assert(
                $binder->getInjector()
                        ->getInstance(XmlSerializerFacade::class)
                        ->serializeToXml($this->status),
                equals('<?xml version="1.0" encoding="UTF-8"?>
<status code="200"><description>Default <b>response</b> code</description></status>')
        );
    }
}
