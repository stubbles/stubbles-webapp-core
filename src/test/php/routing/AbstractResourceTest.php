<?php
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
use stubbles\peer\http\HttpVersion;
use stubbles\streams\memory\MemoryOutputStream;
use stubbles\webapp\Request;
use stubbles\webapp\Response;
use stubbles\webapp\auth\AuthHandler;
use stubbles\webapp\response\mimetypes\Json;
/**
 * Helper class for the test.
 */
class TestAbstractResource extends AbstractResource
{
    /**
     * checks whether switch to https is required
     *
     * @return  bool
     */
    public function requiresHttps() {}

    /**
     * checks if access to this route required authorization
     *
     * @return  bool
     */
    public function requiresAuth() {}

    /**
     * checks whether this is an authorized request to this route
     *
     * @param   AuthHandler  $authHandler
     * @return  bool
     */
    public function isAuthorized(AuthHandler $authHandler) {}

    /**
     * checks whether route required login
     *
     * @param   AuthHandler  $authHandler
     * @return  bool
     */
    public function requiresLogin(AuthHandler $authHandler) {}

    /**
     * creates processor instance
     *
     * @param   \stubbles\webapp\Request   $request    current request
     * @param   \stubbles\webapp\Response  $response   response to send
     * @return  bool
     */
    public function resolve(Request $request, Response $response) {}
}
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
        $this->request = NewInstance::of('stubbles\webapp\Request')
                ->mapCalls(['protocolVersion' => new HttpVersion(1, 1)]);
        $this->response = NewInstance::of(
                'stubbles\webapp\response\WebResponse',
                [$this->request]
        )->mapCalls(['header' => false]);
        $this->injector     = NewInstance::stub('stubbles\ioc\Injector');
        $this->interceptors = NewInstance::stub('stubbles\webapp\routing\Interceptors');
    }

    /**
     * creates instance to test
     *
     * @param   array     $preInterceptors
     * @param   array     $postInterceptors
     * @return  \stubbles\webapp\routing\AbstractResource
     */
    private function createRoute(SupportedMimeTypes $mimeTypes = null)
    {
        return new TestAbstractResource(
                $this->injector,
                new CalledUri('http://example.com/hello/world', 'GET'),
                $this->interceptors,
                null === $mimeTypes ? new SupportedMimeTypes([]) : $mimeTypes

        );
    }

    /**
     * @test
     */
    public function returnsHttpsUriFromCalledUri()
    {
        assertEquals(
                'https://example.com/hello/world',
                (string) $this->createRoute()->httpsUri()
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
                        NewInstance::of('stubbles\webapp\Request'),
                        $this->response
                )
        );
        assertInstanceOf(
                'stubbles\webapp\response\mimetypes\PassThrough',
                $this->response->mimeType()
        );
    }

    /**
     * @test
     * @since  6.0.0
     */
    public function negotiatesNothingIfNoMatchCanBeFound()
    {
        $request = NewInstance::of('stubbles\webapp\Request')
                ->mapCalls(['readHeader' => ValueReader::forValue('text/html')]);
        assertFalse(
                $this->createRoute(
                        new SupportedMimeTypes(
                                ['application/json', 'application/xml']
                        )
                )->negotiateMimeType($request, $this->response)
        );
        assertEquals(406, $this->response->statusCode());
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
        $request = NewInstance::of('stubbles\webapp\Request')
                ->mapCalls(['readHeader' => ValueReader::forValue('application/foo')]);
        assertFalse(
                $this->createRoute(
                        new SupportedMimeTypes(
                                ['application/foo', 'application/xml']
                        )
                )->negotiateMimeType($request, $this->response)
        );
        assertEquals(500, $this->response->statusCode());
        assertEquals(
                'Internal Server Error: No mime type class defined for negotiated content type application/foo',
                $this->response->send(new MemoryOutputStream())->buffer()
        );
    }

    /**
     * @test
     * @since  6.0.0
     */
    public function createsNegotiatedMimeType()
    {
        $request = NewInstance::of('stubbles\webapp\Request')
                ->mapCalls(['readHeader' => ValueReader::forValue('application/json')]);
        $mimeType = new Json();
        $this->injector->mapCalls(['getInstance' => $mimeType]);
        assertTrue(
                $this->createRoute(
                        new SupportedMimeTypes(
                                ['application/json', 'application/xml']
                        )
                )->negotiateMimeType($request, $this->response)
        );
        assertSame(
                $mimeType,
                $this->response->mimeType()
        );
    }

    /**
     * @test
     * @since  2.2.0
     */
    public function returnsGivenListOfSupportedMimeTypes()
    {
        assertEquals(
                [],
                $this->createRoute()->supportedMimeTypes()
        );
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
