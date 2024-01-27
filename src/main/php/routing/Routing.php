<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace stubbles\webapp\routing;

use InvalidArgumentException;
use stubbles\ioc\Injector;
use stubbles\peer\http\HttpUri;
use stubbles\webapp\RoutingConfigurator;
use stubbles\webapp\auth\ProtectedResource;
use stubbles\webapp\htmlpassthrough\HtmlFilePassThrough;
use stubbles\webapp\interceptor\PreInterceptor;
use stubbles\webapp\interceptor\PostInterceptor;
use stubbles\webapp\response\mimetypes\MimeType;
use stubbles\webapp\routing\api\Index;
use stubbles\webapp\Target;

/**
 * Contains routing information and decides which route is applicable for given request.
 *
 * @since  2.0.0
 */
class Routing implements RoutingConfigurator
{
    /**
     * list of routes for the web app
     */
    private Routes $routes;
    /**
     * list of global pre interceptors and to which request method they respond
     *
     * @var  array<array<string,mixed>>
     */
    private array $preInterceptors = [];
    /**
     * list of global post interceptors and to which request method they respond
     *
     * @var  array<array<string,mixed>>
     */
    private array $postInterceptors = [];
    /**
     * list of route-independent supported mime types
     *
     * @var  string[]
     */
    private array $mimeTypes = [];
    /**
     * map of additional mime tyoe classes for this route
     *
     * @var  array<string,class-string<MimeType>>
     */
    private array $mimeTypeClasses = [];
    /**
     * whether content negotation is disabled or not
     */
    private bool $disableContentNegotation = false;

    /**
     * @Inject
     */
    public function __construct(private Injector $injector)
    {
        $this->routes = new Routes();
    }

    /**
     * reply with given class or callable for GET request on given path
     *
     * @param  class-string<Target>|callable|Target  $target  code to be executed when the route is active
     */
    public function onGet(string $path, string|callable|Target $target): ConfigurableRoute
    {
        return $this->addRoute(new Route($path, $target, 'GET'));
    }

    /**
     * reply with HTML file stored in pages path
     *
     * @param  class-string<Target>|callable|Target  $target  optional  code to be executed when the route is active
     * @since  4.0.0
     */
    public function passThroughOnGet(
        string $path = '/[a-zA-Z0-9-_]+.html$',
        string|callable|Target $target = HtmlFilePassThrough::class
    ): ConfigurableRoute {
        return $this->onGet($path, $target);
    }

    /**
     * reply with API index overview
     *
     * @since  6.1.0
     */
    public function apiIndexOnGet(string $path): ConfigurableRoute
    {
        return $this->onGet($path, new Index($this->routes, $this->mimeTypes));
    }

    /**
     * reply with a redirect
     *
     * If the given $target is a string it is used in different ways:
     * - if the string starts with http it is assumed to be a complete uri
     * - else it is assumed to be a path within the application
     *
     * @since  6.1.0
     */
    public function redirectOnGet(
        string $path,
        string|HttpUri $target,
        int $statusCode = 302
    ): ConfigurableRoute {
        return $this->onGet($path, new Redirect($target, $statusCode));
    }

    /**
     * reply with given class or callable for HEAD request on given path
     *
     * @param  class-string<Target>|callable|Target  $target  code to be executed when the route is active
     */
    public function onHead(string $path, string|callable|Target $target): ConfigurableRoute
    {
        return $this->addRoute(new Route($path, $target, 'HEAD'));
    }

    /**
     * reply with given class or callable for POST request on given path
     *
     * @param  class-string<Target>|callable|Target  $target  code to be executed when the route is active
     */
    public function onPost(string $path, string|callable|Target $target): ConfigurableRoute
    {
        return $this->addRoute(new Route($path, $target, 'POST'));
    }

    /**
     * reply with given class or callable for PUT request on given path
     *
     * @param  class-string<Target>|callable|Target  $target  code to be executed when the route is active
     */
    public function onPut(string $path, string|callable|Target $target): ConfigurableRoute
    {
        return $this->addRoute(new Route($path, $target, 'PUT'));
    }

    /**
     * reply with given class or callable for DELETE request on given path
     *
     * @param  class-string<Target>|callable|Target  $target  code to be executed when the route is active
     */
    public function onDelete(string $path, string|callable|Target $target): ConfigurableRoute
    {
        return $this->addRoute(new Route($path, $target, 'DELETE'));
    }

    /**
     * reply with given class or callable for request method(s) on given path
     *
     * If no request method(s) specified it replies to request methods GET, HEAD,
     * POST, PUT and DELETE.
     *
     * @param  class-string<Target>|callable|Target  $target  code to be executed when the route is active
     * @param  string|string[]                       $requestMethod  request method(s) this route is applicable for
     * @since  4.0.0
     */
    public function onAll(
        string $path,
        string|callable|Target $target,
        string|array|null $requestMethod = null
    ): ConfigurableRoute {
        return $this->addRoute(new Route($path, $target, $requestMethod));
    }

    /**
     * add a route definition
     */
    public function addRoute(Route $route): ConfigurableRoute
    {
        return $this->routes->add($route);
    }

    /**
     * returns resource which is applicable for given request
     */
    public function findResource(string|CalledUri $uri, ?string $requestMethod = null): UriResource
    {
        $calledUri      = CalledUri::castFrom($uri, $requestMethod);
        $matchingRoutes = $this->routes->match($calledUri);
        if ($matchingRoutes->hasExactMatch()) {
            return $this->handleMatchingRoute(
                    $calledUri,
                    $matchingRoutes->exactMatch()
            );
        }

        if ($matchingRoutes->exist()) {
            return $this->handleNonMethodMatchingRoutes(
                    $calledUri,
                    $matchingRoutes
            );
        }

        return new NotFound(
            $this->injector,
            $calledUri,
            $this->collectInterceptors($calledUri),
            $this->supportedMimeTypes()
        );
    }

    /**
     * creates a processable route for given route
     */
    private function handleMatchingRoute(CalledUri $calledUri, Route $route): UriResource
    {
        if ($route->requiresAuth()) {
            return new ProtectedResource(
                $route->authConstraint(),
                $this->resolveResource($calledUri, $route),
                $this->injector
            );
        }

        return $this->resolveResource($calledUri, $route);
    }

    /**
     * creates matching route
     */
    private function resolveResource(CalledUri $calledUri, Route $route): ResolvingResource
    {
        return new ResolvingResource(
            $this->injector,
            $calledUri,
            $this->collectInterceptors($calledUri, $route),
            $this->supportedMimeTypes($route),
            $route
        );
    }

    /**
     * creates a processable route when a route can be found regardless of request method
     */
    private function handleNonMethodMatchingRoutes(
        CalledUri $calledUri,
        MatchingRoutes $matchingRoutes
    ): UriResource {
        if ($calledUri->methodEquals('OPTIONS')) {
            return new ResourceOptions(
                $this->injector,
                $calledUri,
                $this->collectInterceptors($calledUri),
                $this->supportedMimeTypes(),
                $matchingRoutes

            );
        }

        return new MethodNotAllowed(
            $this->injector,
            $calledUri,
            $this->collectInterceptors($calledUri),
            $this->supportedMimeTypes(),
            $matchingRoutes->allowedMethods()
        );
    }

    /**
     * pre intercept with given class or callable on all GET requests
     *
     * @param  class-string<PreInterceptor>|callable|PreInterceptor  $preInterceptor  pre interceptor to add
     */
    public function preInterceptOnGet(
        string|callable|PreInterceptor $preInterceptor,
        ?string $path = null
    ): RoutingConfigurator {
        return $this->preIntercept($preInterceptor, $path, 'GET');
    }

    /**
     * pre intercept with given class or callable on all HEAD requests
     *
     * @param  class-string<PreInterceptor>|callable|PreInterceptor  $preInterceptor  pre interceptor to add
     */
    public function preInterceptOnHead(
        string|callable|PreInterceptor $preInterceptor,
        ?string $path = null
    ): RoutingConfigurator {
        return $this->preIntercept($preInterceptor, $path, 'HEAD');
    }

    /**
     * pre intercept with given class or callable on all POST requests
     *
     * @param  class-string<PreInterceptor>|callable|PreInterceptor  $preInterceptor  pre interceptor to add
     */
    public function preInterceptOnPost(
        string|callable|PreInterceptor $preInterceptor,
        ?string $path = null
    ): RoutingConfigurator {
        return $this->preIntercept($preInterceptor, $path, 'POST');
    }

    /**
     * pre intercept with given class or callable on all PUT requests
     *
     * @param  class-string<PreInterceptor>|callable|PreInterceptor  $preInterceptor  pre interceptor to add
     */
    public function preInterceptOnPut(
        string|callable|PreInterceptor $preInterceptor,
       ? string $path = null
    ): RoutingConfigurator {
        return $this->preIntercept($preInterceptor, $path, 'PUT');
    }

    /**
     * pre intercept with given class or callable on all DELETE requests
     *
     * @param  class-string<PreInterceptor>|callable|PreInterceptor  $preInterceptor  pre interceptor to add
     */
    public function preInterceptOnDelete(
        string|callable|PreInterceptor $preInterceptor,
        ?string $path = null
    ): RoutingConfigurator {
        return $this->preIntercept($preInterceptor, $path, 'DELETE');
    }

    /**
     * pre intercept with given class or callable on all requests
     *
     * @param   class-string<PreInterceptor>|callable|PreInterceptor  $preInterceptor  pre interceptor to add
     */
    public function preIntercept(
        string|callable|PreInterceptor $preInterceptor,
        ?string $path = null,
        ?string $requestMethod = null
    ): RoutingConfigurator {
        $this->preInterceptors[] = [
            'interceptor'   => $preInterceptor,
            'requestMethod' => $requestMethod,
            'path'          => $path
        ];
        return $this;
    }

    /**
     * post intercept with given class or callable on all GET requests
     *
     * @param  class-string<PostInterceptor>|callable|PostInterceptor  $postInterceptor  post interceptor to add
     */
    public function postInterceptOnGet(
        string|callable|PostInterceptor $postInterceptor,
        ?string $path = null
    ): RoutingConfigurator {
        return $this->postIntercept($postInterceptor, $path, 'GET');
    }

    /**
     * post intercept with given class or callable on all HEAD requests
     *
     * @param  class-string<PostInterceptor>|callable|PostInterceptor  $postInterceptor  post interceptor to add
     */
    public function postInterceptOnHead(
        string|callable|PostInterceptor $postInterceptor,
        ?string $path = null
    ): RoutingConfigurator {
        return $this->postIntercept($postInterceptor, $path, 'HEAD');
    }

    /**
     * post intercept with given class or callable on all POST requests
     *
     * @param  class-string<PostInterceptor>|callable|PostInterceptor  $postInterceptor  post interceptor to add
     */
    public function postInterceptOnPost(
        string|callable|PostInterceptor $postInterceptor,
        ?string $path = null
    ): RoutingConfigurator {
        return $this->postIntercept($postInterceptor, $path, 'POST');
    }

    /**
     * post intercept with given class or callable on all PUT requests
     *
     * @param  class-string<PostInterceptor>|callable|PostInterceptor  $postInterceptor  post interceptor to add
     */
    public function postInterceptOnPut(
        string|callable|PostInterceptor $postInterceptor,
        ?string $path = null
    ): RoutingConfigurator {
        return $this->postIntercept($postInterceptor, $path, 'PUT');
    }

    /**
     * post intercept with given class or callable on all DELETE requests
     *
     * @param  class-string<PostInterceptor>|callable|PostInterceptor  $postInterceptor  post interceptor to add
     */
    public function postInterceptOnDelete(
        string|callable|PostInterceptor $postInterceptor,
        ?string $path = null
    ): RoutingConfigurator {
        return $this->postIntercept($postInterceptor, $path, 'DELETE');
    }

    /**
     * post intercept with given class or callable on all requests
     *
     * @param  class-string<PostInterceptor>|callable|PostInterceptor  $postInterceptor  post interceptor to add
     */
    public function postIntercept(
        string|callable|PostInterceptor $postInterceptor,
        ?string $path = null,
        ?string $requestMethod = null
    ): RoutingConfigurator {
        $this->postInterceptors[] = [
            'interceptor'   => $postInterceptor,
            'requestMethod' => $requestMethod,
            'path'          => $path
        ];
        return $this;
    }

    /**
     * collects interceptors
     */
    private function collectInterceptors(CalledUri $calledUri, ?Route $route = null): Interceptors
    {
        return new Interceptors(
            $this->injector,
            $this->preInterceptors($calledUri, $route),
            $this->postInterceptors($calledUri, $route)
        );
    }

    /**
     * returns list of applicable pre interceptors for this request
     *
     * @return  array<class-string<PreInterceptor>|callable|PreInterceptor>
     */
    private function preInterceptors(CalledUri $calledUri, ?Route $route = null): array
    {
        $global = $this->applicablePreInterceptors($calledUri);
        if (null === $route) {
            return $global;
        }

        return array_merge($global, $route->preInterceptors());
    }

    /**
     * calculates which pre interceptors are applicable for given request method
     *
     * @return  array<class-string<PreInterceptor>|callable|PreInterceptor>
     */
    private function applicablePreInterceptors(CalledUri $calledUri): array
    {
        $applicable = [];
        foreach ($this->preInterceptors as  $interceptor) {
            if ($calledUri->satisfies($interceptor['requestMethod'], $interceptor['path'])) {
                $applicable[] = $interceptor['interceptor'];
            }
        }

        return $applicable;
    }

    /**
     * returns list of applicable post interceptors for this request
     *
     * @return  array<class-string<PostInterceptor>|callable|PostInterceptor>
     */
    private function postInterceptors(CalledUri $calledUri, ?Route $route = null): array
    {
        $global = $this->applicablePostInterceptors($calledUri);
        if (null === $route) {
            return $global;
        }

        return array_merge($route->postInterceptors(), $global);
    }

    /**
     * calculates which post interceptors are applicable for given request method
     *
     * @return  array<class-string<PostInterceptor>|callable|PostInterceptor>
     */
    private function applicablePostInterceptors(CalledUri $calledUri): array
    {
        $applicable = [];
        foreach ($this->postInterceptors as  $interceptor) {
            if ($calledUri->satisfies($interceptor['requestMethod'], $interceptor['path'])) {
                $applicable[] = $interceptor['interceptor'];
            }
        }

        return $applicable;
    }

    /**
     * sets a default mime type class for given mime type, but doesn't mark the mime type as supported for all routes
     *
     * @param  string                  $mimeType       mime type to set default class for
     * @param  class-string<MimeType>  $mimeTypeClass  class to use
     * @since  5.1.0
     */
    public function setDefaultMimeTypeClass(
        string $mimeType,
        string $mimeTypeClass
    ): RoutingConfigurator {
        SupportedMimeTypes::setDefaultMimeTypeClass($mimeType, $mimeTypeClass);
        return $this;
    }

    /**
     * add a supported mime type
     *
     * @param   class-string<MimeType>  $mimeTypeClass  special class to be used for given mime type on this route
     * @throws  InvalidArgumentException
     */
    public function supportsMimeType(
        string $mimeType,
        ?string $mimeTypeClass = null
    ): RoutingConfigurator {
        if (null === $mimeTypeClass && !SupportedMimeTypes::provideDefaultClassFor($mimeType)) {
            throw new InvalidArgumentException(
                sprintf(
                    'No default class known for mime type %s, please provide a class',
                    $mimeType
                )
            );
        }

        $this->mimeTypes[] = $mimeType;
        if (null !== $mimeTypeClass) {
            $this->mimeTypeClasses[$mimeType] = $mimeTypeClass;
        }

        return $this;
    }

    /**
     * disables content negotation
     *
     * @since  2.1.1
     */
    public function disableContentNegotiation(): RoutingConfigurator
    {
        $this->disableContentNegotation = true;
        return $this;
    }

    /**
     * retrieves list of supported mime types
     */
    private function supportedMimeTypes(?Route $route = null): SupportedMimeTypes
    {
        if ($this->disableContentNegotation) {
            return SupportedMimeTypes::createWithDisabledContentNegotation();
        }

        if (null !== $route) {
            return $route->supportedMimeTypes(
                $this->mimeTypes,
                $this->mimeTypeClasses
            );
        }

        return new SupportedMimeTypes($this->mimeTypes, $this->mimeTypeClasses);
    }
}
