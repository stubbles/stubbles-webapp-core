<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace stubbles\webapp\routing;
use bovigo\callmap\NewInstance;
use PHPUnit\Framework\TestCase;
use stubbles\ioc\Injector;
use stubbles\webapp\{Request, Response, Target, UriPath};
use stubbles\webapp\response\Error;

use function bovigo\assert\{
    assertThat,
    assertFalse,
    assertTrue,
    predicate\equals,
    predicate\isSameAs
};
use function bovigo\callmap\verify;
/**
 * Tests for stubbles\webapp\routing\ResolvingResource.
 *
 * @since  2.0.0
 * @group  routing
 */
class ResolvingResourceTest extends TestCase
{
    /**
     * @var  Request&\bovigo\callmap\ClassProxy
     */
    private $request;
    /**
     * @var  Response&\bovigo\callmap\ClassProxy
     */
    private $response;
    /**
     * @var  Injector&\bovigo\callmap\ClassProxy
     */
    private $injector;

    protected function setUp(): void
    {
        $this->request  = NewInstance::of(Request::class);
        $this->response = NewInstance::of(Response::class);
        $this->injector = NewInstance::stub(Injector::class);
    }

    private function createResolvingResource(
            Route $route,
            string $uri = 'http://example.com/hello/world'
    ): ResolvingResource {
        return new ResolvingResource(
                $this->injector,
                new CalledUri($uri, 'GET'),
                NewInstance::stub(Interceptors::class),
                new SupportedMimeTypes([]),
                $route
        );
    }

    /**
     * @test
     */
    public function requiresSwitchToHttpsIfCalledUriIsNotHttpsButRouteRequiresHttps(): void
    {
        $route = new Route('/hello/{name}', function() {}, 'GET');
        $processableRoute = $this->createResolvingResource($route->httpsOnly());
        assertTrue($processableRoute->requiresHttps());
    }

    /**
     * @test
     */
    public function doesNotrequireSwitchToHttpsIfCalledUriIsNotHttpsAndRouteDoesNotRequireHttps(): void
    {
        assertFalse(
                $this->createResolvingResourceWithTarget(function() {})
                        ->requiresHttps()
        );
    }

    /**
     * @test
     */
    public function doesNotrequireSwitchToHttpsIfCalledUriIsHttps(): void
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
    public function returnsHttpsUriFromCalledUri(): void
    {
        assertThat(
                (string) $this->createResolvingResourceWithTarget(function() {})
                        ->httpsUri(),
                equals('https://example.com/hello/world')
        );
    }

    /**
     * @param   callable|Target|class-string<Target>  $target
     * @return  ResolvingResource
     */
    private function createResolvingResourceWithTarget($target): ResolvingResource
    {
        return $this->createResolvingResource(
                new Route('/hello/{name}', $target, 'GET')
        );
    }

    /**
     * @test
     */
    public function processCallsClosureGivenAsCallback(): void
    {
        assertThat(
                $this->createResolvingResourceWithTarget(
                        function(Request $request, Response $response, UriPath $uriPath)
                        {
                            $response->setStatusCode(418);
                            return 'Hello world';
                        }
                )->resolve($this->request, $this->response),
                equals('Hello world')
        );
        verify($this->response, 'setStatusCode')->received(418);
    }

    /**
     * helper method for the test
     */
    public function theCallable(Request $request, Response $response, UriPath $uriPath): string
    {
        $response->setStatusCode(418);
        return 'Hello world';
    }

    /**
     * @test
     */
    public function processCallsGivenCallback(): void
    {
        assertThat(
                $this->createResolvingResourceWithTarget([$this, 'theCallable'])
                        ->resolve($this->request, $this->response),
                equals('Hello world')
        );
        verify($this->response, 'setStatusCode')->received(418);
    }

    /**
     * helper method for the test
     *
     * @param   \stubbles\webapp\Request   $request
     * @param   \stubbles\webapp\Response  $response
     * @throws  \Exception
     */
    public function failingCallable(Request $request, Response $response, UriPath $uriPath): void
    {
        throw new \Exception('some error occurred');
    }

    /**
     * @test
     */
    public function processCallsGivenProcessorInstance(): void
    {
        $target = NewInstance::of(Target::class);
        $target->returns(['resolve' => 'Hello world']);
        assertThat(
                $this->createResolvingResourceWithTarget($target)
                        ->resolve($this->request, $this->response),
                equals('Hello world')
        );
        verify($target, 'resolve')->received(
                $this->request,
                $this->response,
                new UriPath('/hello/{name}', '/hello/world')
        );
    }

    /**
     * @test
     */
    public function respondsWithInternalServerErrorIfTargetDoesNotImplementInterface(): void
    {
        $this->injector->returns(['getInstance' => new \stdClass()]);
        $error = new Error('error');
        $this->response->returns(['internalServerError' => $error]);
        assertThat(
                $this->createResolvingResourceWithTarget('\stdClass')
                        ->resolve($this->request, $this->response),
                isSameAs($error)
        );
    }

    /**
     * @test
     */
    public function resolveCreatesAndCallsGivenTargetClass(): void
    {
        $target = NewInstance::of(Target::class);
        $target->returns(['resolve' => 'Hello world']);
        $this->injector->returns(['getInstance' => $target]);
        assertThat(
                $this->createResolvingResourceWithTarget(get_class($target))
                        ->resolve($this->request, $this->response),
                equals('Hello world')
        );
    }
}
