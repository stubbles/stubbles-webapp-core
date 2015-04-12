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
use bovigo\callmap;
use bovigo\callmap\NewInstance;
use stubbles\webapp\Request;
use stubbles\webapp\Response;
use stubbles\webapp\UriPath;
use stubbles\webapp\response\Error;
/**
 * Tests for stubbles\webapp\routing\ResolvingResource.
 *
 * @since  2.0.0
 * @group  routing
 */
class ResolvingResourceTest extends \PHPUnit_Framework_TestCase
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
     * @type  \bovigo\callmap\Proxy
     */
    private $response;
    /**
     * mocked injector instance
     *
     * @type  \bovigo\callmap\Proxy
     */
    private $injector;

    /**
     * set up test environment
     */
    public function setUp()
    {
        $this->request  = NewInstance::of('stubbles\webapp\Request');
        $this->response = NewInstance::of('stubbles\webapp\Response');
        $this->injector = NewInstance::stub('stubbles\ioc\Injector');
    }

    /**
     * creates instance to test
     *
     * @param   \stubbles\webapp\routing\Route  $route
     * @return  \stubbles\webapp\routing\ResolvingResource
     */
    private function createResolvingResource(Route $route, $uri = 'http://example.com/hello/world')
    {
        return new ResolvingResource(
                $this->injector,
                new CalledUri($uri, 'GET'),
                NewInstance::stub('stubbles\webapp\routing\Interceptors'),
                new SupportedMimeTypes([]),
                $route
        );
    }

    /**
     * @test
     */
    public function requiresSwitchToHttpsIfCalledUriIsNotHttpsButRouteRequiresHttps()
    {
        $route = new Route('/hello/{name}', function() {}, 'GET');
        $processableRoute = $this->createResolvingResource($route->httpsOnly());
        assertTrue($processableRoute->requiresHttps());
    }

    /**
     * @test
     */
    public function doesNotrequireSwitchToHttpsIfCalledUriIsNotHttpsAndRouteDoesNotRequireHttps()
    {
        assertFalse(
                $this->createResolvingResourceWithTarget(function() {})
                        ->requiresHttps()
        );
    }

    /**
     * @test
     */
    public function doesNotrequireSwitchToHttpsIfCalledUriIsHttps()
    {
        $route = new Route('/hello/{name}', function() {}, 'GET');
        $processableRoute = $this->createResolvingResource(
                $route->httpsOnly(),
                'https://example.com/hello/world'
        );
        assertFalse($processableRoute->requiresHttps());
    }

    /**
     * @test
     */
    public function returnsHttpsUriFromCalledUri()
    {
        assertEquals(
                'https://example.com/hello/world',
                (string) $this->createResolvingResourceWithTarget(function() {})
                        ->httpsUri()
        );
    }

    /**
     * creates instance to test
     *
     * @param   callable  $target
     * @return  \stubbles\webapp\routing\ResolvingResource
     */
    private function createResolvingResourceWithTarget($target)
    {
        return $this->createResolvingResource(
                new Route('/hello/{name}', $target, 'GET')
        );
    }

    /**
     * @test
     */
    public function processCallsClosureGivenAsCallback()
    {
        assertEquals(
                'Hello world',
                $this->createResolvingResourceWithTarget(
                        function(Request $request, Response $response, UriPath $uriPath)
                        {
                            $response->setStatusCode(418);
                            return 'Hello world';
                        }
                )->resolve($this->request, $this->response)
        );
        callmap\verify($this->response, 'setStatusCode')->received(418);
    }

    /**
     * helper method for the test
     *
     * @param  \stubbles\webapp\Request   $request
     * @param  \stubbles\webapp\Response  $response
     */
    public function theCallable(Request $request, Response $response, UriPath $uriPath)
    {
        $response->setStatusCode(418);
        return 'Hello world';
    }

    /**
     * @test
     */
    public function processCallsGivenCallback()
    {
        assertEquals(
                'Hello world',
                $this->createResolvingResourceWithTarget([$this, 'theCallable'])
                        ->resolve($this->request, $this->response)
        );
        callmap\verify($this->response, 'setStatusCode')->received(418);
    }

    /**
     * helper method for the test
     *
     * @param   \stubbles\webapp\Request   $request
     * @param   \stubbles\webapp\respone\Response  $response
     * @throws  \Exception
     */
    public function failingCallable(Request $request, Response $response, UriPath $uriPath)
    {
        throw new \Exception('some error occurred');
    }

    /**
     * @test
     */
    public function processCallsGivenProcessorInstance()
    {
        $target = NewInstance::of('stubbles\webapp\Target');
        $target->mapCalls(['resolve' => 'Hello world']);
        assertEquals(
                'Hello world',
                $this->createResolvingResourceWithTarget($target)
                        ->resolve($this->request, $this->response)
        );
        callmap\verify($target, 'resolve')
                ->received(
                        $this->request,
                        $this->response,
                        new UriPath('/hello/{name}', '/hello/world')
        );
    }

    /**
     * @test
     */
    public function respondsWithInternalServerErrorIfProcessorDoesNotImplementInterface()
    {
        $this->injector->mapCalls(['getInstance' => new \stdClass()]);
        $error = new Error('error');
        $this->response->mapCalls(['internalServerError' => $error]);
        assertSame(
                $error,
                $this->createResolvingResourceWithTarget('\stdClass')
                        ->resolve($this->request, $this->response)
        );
    }

    /**
     * @test
     */
    public function processCreatesAndCallsGivenProcessorClass()
    {
        $target = NewInstance::of('stubbles\webapp\Target');
        $target->mapCalls(['resolve' => 'Hello world']);
        $this->injector->mapCalls(['getInstance' => $target]);
        assertEquals(
                'Hello world',
                $this->createResolvingResourceWithTarget(get_class($target))
                        ->resolve($this->request, $this->response)
        );
    }
}
