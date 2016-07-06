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
use stubbles\webapp\routing\RoutingAnnotations;

use function bovigo\assert\assert;
use function bovigo\assert\assertFalse;
use function bovigo\assert\assertTrue;
use function bovigo\assert\predicate\equals;
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
        $this->routingAnnotations = NewInstance::stub(RoutingAnnotations::class);
        $this->resource = new Resource(
                'Orders',
                ['GET'],
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
        assert($this->resource->name(), equals('Orders'));
    }

    /**
     * @test
     */
    public function returnsProvidedRequestMethods()
    {
        assert($this->resource->requestMethods(), equals(['GET']));
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
        assert(
                $this->resource->description(),
                equals('Endpoint for handling orders.')
        );
    }

    /**
     * @test
     */
    public function hasNoMimeTypesWhenEmptyListProvided()
    {
        $resource = new Resource(
                'Orders',
                ['GET'],
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
        assert($this->resource->mimeTypes(), equals(['application/xml']));
    }

    /**
     * @test
     */
    public function providesNoStatusCodesWhenNoStatusAnnotationPresent()
    {
        $this->routingAnnotations->mapCalls(
                ['containStatusCodes' => false]
        );

        assertFalse($this->resource->providesStatusCodes());
    }

    /**
     * @test
     */
    public function providesStatusCodesWhenStatusAnnotationPresent()
    {
        $this->routingAnnotations->mapCalls(
                ['containStatusCodes' => true]
        );

        assertTrue($this->resource->providesStatusCodes());
    }

    /**
     * @test
     */
    public function returnsProvidedListOfAnnotatedStatusCodes()
    {
        $this->routingAnnotations->mapCalls(
                ['statusCodes' => [new Status(200, 'Default response code')]]
        );
        assert(
                $this->resource->statusCodes(),
                equals([new Status(200, 'Default response code')])
        );
    }

    /**
     * @test
     */
    public function providesNoHeadersWhenNoHeaderAnnotationPresent()
    {
        $this->routingAnnotations->mapCalls(
                ['containHeaders' => false]
        );

        assertFalse($this->resource->hasHeaders());
    }

    /**
     * @test
     */
    public function providesHeadersWhenHeaderAnnotationPresent()
    {
        $this->routingAnnotations->mapCalls(
                ['containHeaders' => true]
        );

        assertTrue($this->resource->hasHeaders());
    }

    /**
     * @test
     */
    public function returnsProvidedListOfAnnotatedHeaders()
    {
        $this->routingAnnotations->mapCalls(
                ['headers' => [new Header('X-Binford', 'More power!')]]
        );
        assert(
                $this->resource->headers(),
                equals([new Header('X-Binford', 'More power!')])
        );
    }

    /**
     * @test
     */
    public function providesNoParametersWhenNoParameterAnnotationPresent()
    {
        $this->routingAnnotations->mapCalls(
                ['containParameters' => false]
        );

        assertFalse($this->resource->hasParameters());
    }

    /**
     * @test
     */
    public function providesParametersWhenParameterAnnotationPresent()
    {
        $this->routingAnnotations->mapCalls(
                ['containParameters' => true]
        );

        assertTrue($this->resource->hasParameters());
    }

    /**
     * @test
     */
    public function returnsProvidedListOfAnnotatedParameters()
    {
        $this->routingAnnotations->mapCalls(
                ['parameters' => [new Parameter('binford', 'More power!', 'query')]]
        );
        assert(
                $this->resource->parameters(),
                equals([new Parameter('binford', 'More power!', 'query')])
        );
    }

    /**
     * @test
     */
    public function returnsProvidedAuthConstraint()
    {
        assert(
                $this->resource->authConstraint(),
                equals(new AuthConstraint($this->routingAnnotations))
        );
    }

    /**
     * @test
     */
    public function returnsLinksWithProvidedLinkAsSelf()
    {
        assert(
                $this->resource->links()->with('self'),
                equals(['http://example.com/orders'])
        );
    }
}
