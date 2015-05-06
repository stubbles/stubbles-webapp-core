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
use stubbles\peer\http\HttpUri;
/**
 * Test for stubbles\webapp\routing\api\Link.
 *
 * @since  6.1.0
 * @group  routing
 * @group  routing_api
 */
class LinkTest extends \PHPUnit_Framework_TestCase
{
    /**
     * instance to test
     *
     * @type  \stubbles\webapp\routing\api\Link
     */
    private $link;

    /**
     * set up test environment
     */
    public function setUp()
    {
        $this->link = new Link(
                'self',
                HttpUri::fromString('http://example.com/foo')
        );
    }

    /**
     * @test
     */
    public function returnsProvidedRel()
    {
        assertEquals('self', $this->link->rel());
    }

    /**
     * @test
     */
    public function returnsProvidedUri()
    {
        assertEquals('http://example.com/foo', $this->link->uri());
    }

    /**
     * @test
     */
    public function stringRepresentationIsUri()
    {
        assertEquals('http://example.com/foo', $this->link);
    }

    /**
     * @test
     */
    public function canBeSerializedToJson()
    {
        assertEquals(
                '{"href":"http:\/\/example.com\/foo"}',
                json_encode($this->link)
        );
    }
}
