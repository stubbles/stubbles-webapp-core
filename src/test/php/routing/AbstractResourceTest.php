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
namespace stubbles\webapp\routing;
use bovigo\callmap\NewInstance;
use stubbles\input\ValueReader;
use stubbles\ioc\Injector;
use stubbles\peer\http\HttpVersion;
use stubbles\streams\memory\MemoryOutputStream;
use stubbles\webapp\{Request, Response};
use stubbles\webapp\auth\AuthHandler;
use stubbles\webapp\response\{WebResponse, mimetypes\Json, mimetypes\PassThrough};

use function bovigo\assert\{
    assert,
    assertEmptyArray,
    assertFalse,
    assertTrue,
    predicate\equals,
    predicate\isInstanceOf,
    predicate\isSameAs
};
/**
 * Tests for stubbles\webapp\routing\AbstractResource.
 *
 * @since  2.0.0
 * @group  routing
 */
class AbstractResourceTest extends \PHPUnit_Framework_TestCase
{
    /**
     * mocked request instance
     *
     * @type  \bovigo\callmap\Proxy
     */
    private $request;
    /**
     * mocked response instance
     *
     * @type  \stubbles\webapp\response\WebResponse
     */
    private $response;
    /**
     * @type  \bovigo\callmap\Proxy
     */
    private $injector;
    /**
     * @type  \bovigo\callmap\Proxy
     */
    private $interceptors;

    /**
     * set up test environment
     */
    public function setUp()
    {
        $this->request = NewInstance::of(Request::class)->mapCalls([
                'id'              => '313',
                'protocolVersion' => new HttpVersion(1, 1),
                'method'          => 'TEST'
        ]);
        $this->response = NewInstance::of(WebResponse::class, [$this->request])
                ->mapCalls(['header' => false]);
        $this->injector     = NewInstance::stub(Injector::class);
        $this->interceptors = NewInstance::stub(Interceptors::class);
    }

    private function createRoute(SupportedMimeTypes $mimeTypes = null): AbstractResource
    {
        return new class(
                $this->injector,
                new CalledUri('http://example.com/hello/world', 'GET'),
                $this->interceptors,
                null === $mimeTypes ? new SupportedMimeTypes([]) : $mimeTypes
        ) extends AbstractResource {

            public function requiresHttps(): bool { return false; }

            public function requiresAuth(): bool { return false;}

            public function isAuthorized(AuthHandler $authHandler): bool { return false; }

            public function requiresLogin(AuthHandler $authHandler): bool { return false; }

            public function resolve(Request $request, Response $response) {}
        };
    }

    /**
     * @test
     */
    public function returnsHttpsUriFromCalledUri()
    {
        assert(
                (string) $this->createRoute()->httpsUri(),
                equals('https://example.com/hello/world')
        );
    }

    /**
     * @test
     * @since  6.0.0
     */
    public function negotiatesPassThroughIfContentNegotiationDisabled()
    {
        assertTrue(
                $this->createRoute(
                        SupportedMimeTypes::createWithDisabledContentNegotation()
                )->negotiateMimeType(
                        NewInstance::of(Request::class),
                        $this->response
                )
        );
        assert($this->response->mimeType(), isInstanceOf(PassThrough::class));
    }

    /**
     * @test
     * @since  6.0.0
     */
    public function negotiatesNothingIfNoMatchCanBeFound()
    {
        $request = NewInstance::of(Request::class)->mapCalls([
                'readHeader' => ValueReader::forValue('text/html')
        ]);
        assertFalse(
                $this->createRoute(
                        new SupportedMimeTypes(
                                ['application/json', 'application/xml']
                        )
                )->negotiateMimeType($request, $this->response)
        );
        assert($this->response->statusCode(), equals(406));
        assertTrue(
                $this->response->containsHeader(
                        'X-Acceptable',
                        'application/json, application/xml'
                )
        );
    }

    /**
     * @test
     * @since  6.0.0
     */
    public function missingMimeTypeClassForNegotiatedMimeTypeTriggersInternalServerError()
    {
        $request = NewInstance::of(Request::class)->mapCalls([
                'readHeader' => ValueReader::forValue('application/foo'),
                'method'     => 'TEST'
        ]);
        assertFalse(
                $this->createRoute(
                        new SupportedMimeTypes(
                                ['application/foo', 'application/xml']
                        )
                )->negotiateMimeType($request, $this->response)
        );
        assert($this->response->statusCode(), equals(500));
        assert(
                $this->response->send(new MemoryOutputStream())->buffer(),
                equals('Internal Server Error: No mime type class defined for negotiated content type application/foo')
        );
    }

    /**
     * @test
     * @since  6.0.0
     */
    public function createsNegotiatedMimeType()
    {
        $request = NewInstance::of(Request::class)->mapCalls([
                'readHeader' => ValueReader::forValue('application/json'),
                'method'     => 'TEST'
        ]);
        $mimeType = new Json();
        $this->injector->mapCalls(['getInstance' => $mimeType]);
        assertTrue(
                $this->createRoute(
                        new SupportedMimeTypes(
                                ['application/json', 'application/xml']
                        )
                )->negotiateMimeType($request, $this->response)
        );
        assert($this->response->mimeType(), isSameAs($mimeType));
    }

    /**
     * @test
     * @since  2.2.0
     */
    public function returnsGivenListOfSupportedMimeTypes()
    {
        assertEmptyArray($this->createRoute()->supportedMimeTypes());
    }

    /**
     * @test
     */
    public function delegatesPreInterceptingToInterceptors()
    {
        $this->interceptors->mapCalls(['preProcess' => true]);
        assertTrue(
                $this->createRoute()
                        ->applyPreInterceptors(
                                $this->request,
                                $this->response
                        )
        );
    }

    /**
     * @test
     */
    public function delegatesPostInterceptingToInterceptors()
    {
        $this->interceptors->mapCalls(['postProcess' => true]);
        assertTrue(
                $this->createRoute()
                        ->applyPostInterceptors(
                                $this->request,
                                $this->response
                        )
        );
    }
}
