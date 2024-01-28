<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace stubbles\webapp\routing\api;

use bovigo\callmap\ClassProxy;
use bovigo\callmap\NewInstance;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use stubbles\peer\http\HttpUri;
use stubbles\webapp\auth\AuthConstraint;
use stubbles\webapp\routing\RoutingAnnotations;

use function bovigo\assert\assertThat;
use function bovigo\assert\assertFalse;
use function bovigo\assert\assertTrue;
use function bovigo\assert\predicate\equals;
/**
 * Test for stubbles\webapp\routing\api\Resource.
 *
 * @since  6.1.0
 */
#[Group('routing')]
#[Group('routing_api')]
class ResourceTest extends TestCase
{
    private Resource $resource;
    private RoutingAnnotations&ClassProxy $routingAnnotations;

    protected function setUp(): void
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

    #[Test]
    public function returnsProvidedName(): void
    {
        assertThat($this->resource->name(), equals('Orders'));
    }

    #[Test]
    public function returnsProvidedRequestMethods(): void
    {
        assertThat($this->resource->requestMethods(), equals(['GET']));
    }

    #[Test]
    public function hasDescriptionWhenNotNull(): void
    {
        $this->routingAnnotations->returns(
            ['description' => 'Endpoint for handling orders.']
        );
        assertTrue($this->resource->hasDescription());
    }

    #[Test]
    public function hasNoDescriptionWhenNoDescriptionAnnotationPresent(): void
    {
        assertFalse($this->resource->hasDescription());
    }

    #[Test]
    public function returnsProvidedDescription(): void
    {
        $this->routingAnnotations->returns(
            ['description' => 'Endpoint for handling orders.']
        );
        assertThat(
            $this->resource->description(),
            equals('Endpoint for handling orders.')
        );
    }

    #[Test]
    public function hasNoMimeTypesWhenEmptyListProvided(): void
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

    #[Test]
    public function hasMimeTypesWhenListProvided(): void
    {
        assertTrue($this->resource->hasMimeTypes());
    }

    #[Test]
    public function returnsProvidedListOfMimeTypes(): void
    {
        assertThat($this->resource->mimeTypes(), equals(['application/xml']));
    }

    #[Test]
    public function providesNoStatusCodesWhenNoStatusAnnotationPresent(): void
    {
        $this->routingAnnotations->returns(
            ['containStatusCodes' => false]
        );

        assertFalse($this->resource->providesStatusCodes());
    }

    #[Test]
    public function providesStatusCodesWhenStatusAnnotationPresent(): void
    {
        $this->routingAnnotations->returns(
                ['containStatusCodes' => true]
        );

        assertTrue($this->resource->providesStatusCodes());
    }

    #[Test]
    public function returnsProvidedListOfAnnotatedStatusCodes(): void
    {
        $this->routingAnnotations->returns(
            ['statusCodes' => [new Status(200, 'Default response code')]]
        );

        assertThat(
            $this->resource->statusCodes(),
            equals([new Status(200, 'Default response code')])
        );
    }

    #[Test]
    public function providesNoHeadersWhenNoHeaderAnnotationPresent(): void
    {
        $this->routingAnnotations->returns(
            ['containHeaders' => false]
        );

        assertFalse($this->resource->hasHeaders());
    }

    #[Test]
    public function providesHeadersWhenHeaderAnnotationPresent(): void
    {
        $this->routingAnnotations->returns(
                ['containHeaders' => true]
        );

        assertTrue($this->resource->hasHeaders());
    }

    #[Test]
    public function returnsProvidedListOfAnnotatedHeaders(): void
    {
        $this->routingAnnotations->returns(
            ['headers' => [new Header('X-Binford', 'More power!')]]
        );

        assertThat(
            $this->resource->headers(),
            equals([new Header('X-Binford', 'More power!')])
        );
    }

    #[Test]
    public function providesNoParametersWhenNoParameterAnnotationPresent(): void
    {
        $this->routingAnnotations->returns(
            ['containParameters' => false]
        );

        assertFalse($this->resource->hasParameters());
    }

    #[Test]
    public function providesParametersWhenParameterAnnotationPresent(): void
    {
        $this->routingAnnotations->returns(
            ['containParameters' => true]
        );

        assertTrue($this->resource->hasParameters());
    }

    #[Test]
    public function returnsProvidedListOfAnnotatedParameters(): void
    {
        $this->routingAnnotations->returns(
            ['parameters' => [new Parameter('binford', 'More power!', 'query')]]
        );
        assertThat(
            $this->resource->parameters(),
            equals([new Parameter('binford', 'More power!', 'query')])
        );
    }

    #[Test]
    public function returnsProvidedAuthConstraint(): void
    {
        assertThat(
            $this->resource->authConstraint(),
            equals(new AuthConstraint($this->routingAnnotations))
        );
    }

    #[Test]
    public function returnsLinksWithProvidedLinkAsSelf(): void
    {
        assertThat(
            $this->resource->links()->with('self'),
            equals(['http://example.com/orders'])
        );
    }
}
