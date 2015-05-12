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
use bovigo\callmap\NewInstance;
use stubbles\peer\http\HttpUri;
use stubbles\webapp\auth\AuthConstraint;
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
     *
     * @type  \stubbles\webapp\routing\RoutingAnnotations
     */
    private $routingAnnotations;

    /**
     * set up test environment
     */
    public function setUp()
    {
        $this->routingAnnotations = NewInstance::stub('stubbles\webapp\routing\RoutingAnnotations');
        $this->resource = new Resource(
                'Orders',
                HttpUri::fromString('http://example.com/orders'),
                ['application/xml'],
                $this->routingAnnotations,
                new AuthConstraint($this->routingAnnotations)
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
        $this->routingAnnotations->mapCalls(
                ['description' => 'Endpoint for handling orders.']
        );
        assertTrue($this->resource->hasDescription());
    }

    /**
     * @test
     */
    public function hasNoDescriptionWhenNoDescriptionAnnotationPresent()
    {
        assertFalse($this->resource->hasDescription());
    }

    /**
     * @test
     */
    public function returnsProvidedDescription()
    {
        $this->routingAnnotations->mapCalls(
                ['description' => 'Endpoint for handling orders.']
        );
        assertEquals(
                'Endpoint for handling orders.',
                $this->resource->description()
        );
    }

    /**
     * @test
     */
    public function hasNoMimeTypesWhenEmptyListProvided()
    {
        $resource = new Resource(
                'Orders',
                HttpUri::fromString('http://example.com/orders'),
                [],
                $this->routingAnnotations,
                new AuthConstraint($this->routingAnnotations)
        );
        assertFalse($resource->hasMimeTypes());
    }

    /**
     * @test
     */
    public function hasMimeTypesWhenListProvided()
    {
        assertTrue($this->resource->hasMimeTypes());
    }

    /**
     * @test
     */
    public function returnsProvidedListOfMimeTypes()
    {
        assertEquals(
                ['application/xml'],
                $this->resource->mimeTypes()
        );
    }

    /**
     * @test
     */
    public function returnsProvidedListOfAnnotatedStatusCodes()
    {
        $this->routingAnnotations->mapCalls(
                ['statusCodes' => [new Status(200, 'Default response code')]]
        );
        assertEquals(
                [new Status(200, 'Default response code')],
                $this->resource->statusCodes()
        );
    }

    /**
     * @test
     */
    public function returnsProvidedAuthConstraint()
    {
        assertEquals(
                new AuthConstraint($this->routingAnnotations),
                $this->resource->authConstraint()
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
