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
use stubbles\ioc\Injector;
use stubbles\webapp\RoutingConfigurator;
use stubbles\webapp\auth\ProtectedResource;
use stubbles\webapp\interceptor\PreInterceptor;
use stubbles\webapp\interceptor\PostInterceptor;
/**
 * Contains routing information and decides which route is applicable for given request.
 *
 * @since  2.0.0
 */
class Routing implements RoutingConfigurator
{
    /**
     * list of routes for the web app
     *
     * @type  \stubbles\webapp\routing\Routes
     */
    private $routes;
    /**
     * list of global pre interceptors and to which request method they respond
     *
     * @type  array
     */
    private $preInterceptors          = [];
    /**
     * list of global post interceptors and to which request method they respond
     *
     * @type  array
     */
    private $postInterceptors         = [];
    /**
     * list of route-independent supported mime types
     *
     * @type  string[]
     */
    private $mimeTypes                = [];
    /**
     * map of additional mime tyoe classes for this route
     *
     * @type  string[]
     */
    private $mimeTypeClasses                = [];
    /**
     * whether content negotation is disabled or not
     *
     * @type  bool
     */
    private $disableContentNegotation = false;
    /**
     * injector instance
     *
     * @type  \stubbles\ioc\Injector
     */
    private $injector;

    /**
     * constructor
     *
     * @param  \stubbles\ioc\Injector  $injector
     * @Inject
     */
    public function __construct(Injector $injector)
    {
        $this->injector  = $injector;
        $this->routes    = new Routes();
    }

    /**
     * reply with given class or callable for GET request on given path
     *
     * @param   string                                   $path    path this route is applicable for
     * @param   string|callable|\stubbles\webapp\Target  $target  code to be executed when the route is active
     * @return  \stubbles\webapp\routing\ConfigurableRoute
     */
    public function onGet($path, $target)
    {
        return $this->addRoute(new Route($path, $target, 'GET'));
    }

    /**
     * reply with HTML file stored in pages path
     *
     * @param   string                                   $path    optional  path this route is applicable for
     * @param   string|callable|\stubbles\webapp\Target  $target  optional  code to be executed when the route is active
     * @return  \stubbles\webapp\routing\ConfigurableRoute
     * @since   4.0.0
     */
    public function passThroughOnGet($path = '/[a-zA-Z0-9-_]+.html$', $target = 'stubbles\webapp\htmlpassthrough\HtmlFilePassThrough')
    {
        return $this->onGet($path, $target);
    }

    /**
     * reply with given class or callable for HEAD request on given path
     *
     * @param   string                                   $path    path this route is applicable for
     * @param   string|callable|\stubbles\webapp\Target  $target  code to be executed when the route is active
     * @return  \stubbles\webapp\routing\ConfigurableRoute
     */
    public function onHead($path, $target)
    {
        return $this->addRoute(new Route($path, $target, 'HEAD'));
    }

    /**
     * reply with given class or callable for POST request on given path
     *
     * @param   string                                   $path    path this route is applicable for
     * @param   string|callable|\stubbles\webapp\Target  $target  code to be executed when the route is active
     * @return  \stubbles\webapp\routing\ConfigurableRoute
     */
    public function onPost($path, $target)
    {
        return $this->addRoute(new Route($path, $target, 'POST'));
    }

    /**
     * reply with given class or callable for PUT request on given path
     *
     * @param   string                                   $path    path this route is applicable for
     * @param   string|callable|\stubbles\webapp\Target  $target  code to be executed when the route is active
     * @return  \stubbles\webapp\routing\ConfigurableRoute
     */
    public function onPut($path, $target)
    {
        return $this->addRoute(new Route($path, $target, 'PUT'));
    }

    /**
     * reply with given class or callable for DELETE request on given path
     *
     * @param   string                                   $path    path this route is applicable for
     * @param   string|callable|\stubbles\webapp\Target  $target  code to be executed when the route is active
     * @return  \stubbles\webapp\routing\ConfigurableRoute
     */
    public function onDelete($path, $target)
    {
        return $this->addRoute(new Route($path, $target, 'DELETE'));
    }

    /**
     * reply with given class or callable for request method(s) on given path
     *
     * If no request method(s) specified it replies to request methods GET, HEAD,
     * POST, PUT and DELETE.
     *
     * @param   string                                   $path           path this route is applicable for
     * @param   string|callable|\stubbles\webapp\Target  $target         code to be executed when the route is active
     * @param   string|string[]                          $requestMethod  optional  request method(s) this route is applicable for
     * @return  \stubbles\webapp\routing\ConfigurableRoute
     * @since   4.0.0
     */
    public function onAll($path, $target, $requestMethod = null)
    {
        return $this->addRoute(new Route($path, $target, $requestMethod));
    }

    /**
     * add a route definition
     *
     * @param   \stubbles\webapp\routing\Route  $route
     * @return  \stubbles\webapp\routing\Route
     */
    public function addRoute(Route $route)
    {
        return $this->routes->add($route);
    }

    /**
     * returns resource which is applicable for given request
     *
     * @param   string|\stubbles\webapp\routing\CalledUri  $uri            actually called uri
     * @param   string                                     $requestMethod  optional when $calledUri is an instance of stubbles\webapp\routing\CalledUri
     * @return  \stubbles\webapp\routing\UriResource
     */
    public function findResource($uri, $requestMethod = null)
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
     *
     * @param   \stubbles\webapp\routing\CalledUri  $calledUri
     * @param   \stubbles\webapp\routing\Route      $route
     * @return  \stubbles\webapp\routing\UriResource
     */
    private function handleMatchingRoute(CalledUri $calledUri, Route $route)
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
     *
     * @param   \stubbles\webapp\routing\CalledUri  $calledUri
     * @param   \stubbles\webapp\routing\Route      $route
     * @return  \stubbles\webapp\routing\ResolvingResource
     */
    private function resolveResource(CalledUri $calledUri, Route $route)
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
     *
     * @param   \stubbles\webapp\routing\CalledUri       $calledUri
     * @param   \stubbles\webapp\routing\MatchingRoutes  $matchingRoutes
     * @return  \stubbles\webapp\routing\UriResource
     */
    private function handleNonMethodMatchingRoutes(CalledUri $calledUri, MatchingRoutes $matchingRoutes)
    {
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
     * @param   string|callable|\stubbles\webapp\intercepto\PreInterceptor  $preInterceptor  pre interceptor to add
     * @param   string                                                      $path            optional  path for which pre interceptor should be executed
     * @return  \stubbles\webapp\RoutingConfigurator
     */
    public function preInterceptOnGet($preInterceptor, $path = null)
    {
        return $this->preIntercept($preInterceptor, $path, 'GET');
    }

    /**
     * pre intercept with given class or callable on all HEAD requests
     *
     * @param   string|callable|\stubbles\webapp\interceptor\PreInterceptor  $preInterceptor  pre interceptor to add
     * @param   string                                                       $path            optional  path for which pre interceptor should be executed
     * @return  \stubbles\webapp\RoutingConfigurator
     */
    public function preInterceptOnHead($preInterceptor, $path = null)
    {
        return $this->preIntercept($preInterceptor, $path, 'HEAD');
    }

    /**
     * pre intercept with given class or callable on all POST requests
     *
     * @param   string|callable|\stubbles\webapp\interceptor\PreInterceptor  $preInterceptor  pre interceptor to add
     * @param   string                                                       $path            optional  path for which pre interceptor should be executed
     * @return  \stubbles\webapp\RoutingConfigurator
     */
    public function preInterceptOnPost($preInterceptor, $path = null)
    {
        return $this->preIntercept($preInterceptor, $path, 'POST');
    }

    /**
     * pre intercept with given class or callable on all PUT requests
     *
     * @param   string|callable|\stubbles\webapp\interceptor\PreInterceptor  $preInterceptor  pre interceptor to add
     * @param   string                                                       $path            optional  path for which pre interceptor should be executed
     * @return  \stubbles\webapp\RoutingConfigurator
     */
    public function preInterceptOnPut($preInterceptor, $path = null)
    {
        return $this->preIntercept($preInterceptor, $path, 'PUT');
    }

    /**
     * pre intercept with given class or callable on all DELETE requests
     *
     * @param   string|callable|\stubbles\webapp\interceptor\PreInterceptor  $preInterceptor  pre interceptor to add
     * @param   string                                                       $path            optional  path for which pre interceptor should be executed
     * @return  \stubbles\webapp\RoutingConfigurator
     */
    public function preInterceptOnDelete($preInterceptor, $path = null)
    {
        return $this->preIntercept($preInterceptor, $path, 'DELETE');
    }

    /**
     * pre intercept with given class or callable on all requests
     *
     * @param   string|callable|\stubbles\webapp\interceptor\PreInterceptor  $preInterceptor  pre interceptor to add
     * @param   string                                                       $path            optional  path for which pre interceptor should be executed
     * @param   string                                                       $requestMethod   request method for which interceptor should be executed
     * @return  \stubbles\webapp\RoutingConfigurator
     * @throws  \InvalidArgumentException
     */
    public function preIntercept($preInterceptor, $path = null, $requestMethod = null)
    {
        if (!is_callable($preInterceptor) && !($preInterceptor instanceof PreInterceptor) && !class_exists($preInterceptor)) {
            throw new \InvalidArgumentException('Given pre interceptor must be a callable, an instance of stubbles\webapp\interceptor\PreInterceptor or a class name of an existing pre interceptor class');
        }

        $this->preInterceptors[] = ['interceptor'   => $preInterceptor,
                                    'requestMethod' => $requestMethod,
                                    'path'          => $path
                                   ];
        return $this;
    }

    /**
     * post intercept with given class or callable on all GET requests
     *
     * @param   string|callable|\stubbles\webapp\interceptor\PostInterceptor  $postInterceptor  post interceptor to add
     * @param   string                                                        $path             optional  path for which post interceptor should be executed
     * @return  \stubbles\webapp\RoutingConfigurator
     */
    public function postInterceptOnGet($postInterceptor, $path = null)
    {
        return $this->postIntercept($postInterceptor, $path, 'GET');
    }

    /**
     * post intercept with given class or callable on all HEAD requests
     *
     * @param   string|callable|\stubbles\webapp\interceptor\PostInterceptor  $postInterceptor  post interceptor to add
     * @param   string                                                        $path             optional  path for which post interceptor should be executed
     * @return  \stubbles\webapp\RoutingConfigurator
     */
    public function postInterceptOnHead($postInterceptor, $path = null)
    {
        return $this->postIntercept($postInterceptor, $path, 'HEAD');
    }

    /**
     * post intercept with given class or callable on all POST requests
     *
     * @param   string|callable|\stubbles\webapp\interceptor\PostInterceptor  $postInterceptor  post interceptor to add
     * @param   string                                                        $path             optional  path for which post interceptor should be executed
     * @return  \stubbles\webapp\RoutingConfigurator
     */
    public function postInterceptOnPost($postInterceptor, $path = null)
    {
        return $this->postIntercept($postInterceptor, $path, 'POST');
    }

    /**
     * post intercept with given class or callable on all PUT requests
     *
     * @param   string|callable|\stubbles\webapp\interceptor\PostInterceptor  $postInterceptor  post interceptor to add
     * @param   string                                                        $path             optional  path for which post interceptor should be executed
     * @return  \stubbles\webapp\RoutingConfigurator
     */
    public function postInterceptOnPut($postInterceptor, $path = null)
    {
        return $this->postIntercept($postInterceptor, $path, 'PUT');
    }

    /**
     * post intercept with given class or callable on all DELETE requests
     *
     * @param   string|callable|\stubbles\webapp\interceptor\PostInterceptor  $postInterceptor  post interceptor to add
     * @param   string                                                        $path             optional  path for which post interceptor should be executed
     * @return  \stubbles\webapp\RoutingConfigurator
     */
    public function postInterceptOnDelete($postInterceptor, $path = null)
    {
        return $this->postIntercept($postInterceptor, $path, 'DELETE');
    }

    /**
     * post intercept with given class or callable on all requests
     *
     * @param   string|callable|\stubbles\webapp\interceptor\PostInterceptor  $postInterceptor  post interceptor to add
     * @param   string                                                        $path             optional  path for which post interceptor should be executed
     * @param   string                                                        $requestMethod    optional  request method for which interceptor should be executed
     * @return  \stubbles\webapp\RoutingConfigurator
     * @throws  \InvalidArgumentException
     */
    public function postIntercept($postInterceptor, $path = null, $requestMethod = null)
    {
        if (!is_callable($postInterceptor) && !($postInterceptor instanceof PostInterceptor) && !class_exists($postInterceptor)) {
            throw new \InvalidArgumentException('Given pre interceptor must be a callable, an instance of stubbles\webapp\interceptor\PostInterceptor or a class name of an existing post interceptor class');
        }

        $this->postInterceptors[] = ['interceptor'   => $postInterceptor,
                                     'requestMethod' => $requestMethod,
                                     'path'          => $path
                                    ];
        return $this;
    }

    /**
     * collects interceptors
     *
     * @param   \stubbles\webapp\routing\CalledUri  $calledUri
     * @param   \stubbles\webapp\routing\Route      $route
     * @return  \stubbles\webapp\interceptor\Interceptors
     */
    private function collectInterceptors(CalledUri $calledUri, Route $route = null)
    {
        return new Interceptors(
                $this->injector,
                $this->getPreInterceptors($calledUri, $route),
                $this->getPostInterceptors($calledUri, $route)
        );
    }

    /**
     * returns list of applicable pre interceptors for this request
     *
     * @param   \stubbles\webapp\routing\CalledUri  $calledUri
     * @param   \stubbles\webapp\routing\Route      $route
     * @return  array
     */
    private function getPreInterceptors(CalledUri $calledUri, Route $route = null)
    {
        $global = $this->getApplicable($calledUri, $this->preInterceptors);
        if (null === $route) {
            return $global;
        }

        return array_merge($global, $route->preInterceptors());
    }

    /**
     * returns list of applicable post interceptors for this request
     *
     * @param   \stubbles\webapp\routing\CalledUri  $calledUri
     * @param   \stubbles\webapp\routing\Route      $route
     * @return  array
     */
    private function getPostInterceptors(CalledUri $calledUri, Route $route = null)
    {
        $global = $this->getApplicable($calledUri, $this->postInterceptors);
        if (null === $route) {
            return $global;
        }

        return array_merge($route->postInterceptors(), $global);
    }

    /**
     * calculates which interceptors are applicable for given request method
     *
     * @param   \stubbles\webapp\routing\CalledUri  $calledUri
     * @param   array[]                             $interceptors   list of interceptors to check
     * @return  array
     */
    private function getApplicable(CalledUri $calledUri, array $interceptors)
    {
        $applicable = [];
        foreach ($interceptors as  $interceptor) {
            if ($calledUri->satisfies($interceptor['requestMethod'], $interceptor['path'])) {
                $applicable[] = $interceptor['interceptor'];
            }
        }

        return $applicable;
    }

    /**
     * sets a default mime type class for given mime type, but doesn't mark the mime type as supported for all routes
     *
     * @param   string  $mimeType       mime type to set default class for
     * @param   string  $mimeTypeClass  class to use
     * @return  \stubbles\webapp\routing\Routing
     * @since   5.1.0
     */
    public function setDefaultMimeTypeClass($mimeType, $mimeTypeClass)
    {
        SupportedMimeTypes::setDefaultMimeTypeClass($mimeType, $mimeTypeClass);
        return $this;
    }

    /**
     * add a supported mime type
     *
     * @param   string  $mimeType
     * @param   string  $mimeTypeClass  optional  special class to be used for given mime type on this route
     * @return  \stubbles\webapp\routing\Routing
     * @throws  \InvalidArgumentException
     */
    public function supportsMimeType($mimeType, $mimeTypeClass = null)
    {
        if (null === $mimeTypeClass && !SupportedMimeTypes::provideDefaultClassFor($mimeType)) {
            throw new \InvalidArgumentException('No default class known for mime type ' . $mimeType . ', please provide a class');
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
     * @return  \stubbles\webapp\routing\Routing
     * @since   2.1.1
     */
    public function disableContentNegotiation()
    {
        $this->disableContentNegotation = true;
        return $this;
    }

    /**
     * retrieves list of supported mime types
     *
     * @param   \stubbles\webapp\routing\Route  $route
     * @return  \stubbles\webapp\routing\SupportedMimeTypes
     */
    private function supportedMimeTypes(Route $route = null)
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
