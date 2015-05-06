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
 * Test for stubbles\webapp\routing\api\Resource.
 *
 * @since  6.1.0
 * @group  routing
 * @group  routing_api
 */
class ResourceTest extends \PHPUnit_Framework_TestCase
{
    /**
     * instance to test
     *
     * @type  \stubbles\webapp\routing\api\Resource
     */
    private $resource;

    /**
     * set up test environment
     */
    public function setUp()
    {
        $this->resource = new Resource(
                'Orders',
                'Endpoint for handling orders.',
                HttpUri::fromString('http://example.com/orders')
        );
    }

    /**
     * @test
     */
    public function returnsProvidedName()
    {
        assertEquals('Orders', $this->resource->name());
    }

    /**
     * @test
     */
    public function hasDescriptionWhenNotNull()
    {
        assertTrue($this->resource->hasDescription());
    }

    /**
     * @test
     */
    public function hasNoDescriptionWhenNull()
    {
        $resource = new Resource(
                'Orders',
                null,
                HttpUri::fromString('http://example.com/orders')
        );
        assertFalse($resource->hasDescription());
    }

    /**
     * @test
     */
    public function returnsProvidedDescription()
    {
        assertEquals(
                'Endpoint for handling orders.',
                $this->resource->description()
        );
    }

    /**
     * @test
     */
    public function returnsLinksWithProvidedLinkAsSelf()
    {
        foreach ($this->resource->links() as $rel => $link) {
            assertEquals('self', $rel);
            assertEquals('http://example.com/orders', $link);
        }   
    }
}
