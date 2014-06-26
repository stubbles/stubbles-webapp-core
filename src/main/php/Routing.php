<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  stubbles\webapp
 */
namespace stubbles\webapp;
use stubbles\ioc\Injector;
use stubbles\lang\exception\IllegalArgumentException;
use stubbles\webapp\auth\AuthHandler;
use stubbles\webapp\auth\AuthorizingRoute;
use stubbles\webapp\interceptor\Interceptors;
use stubbles\webapp\interceptor\PreInterceptor;
use stubbles\webapp\interceptor\PostInterceptor;
use stubbles\webapp\response\SupportedMimeTypes;
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
     * @type  Route[]
     */
    private $routes                   = [];
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
     * whether content negotation is disabled or not
     *
     * @type  bool
     */
    private $disableContentNegotation = false;
    /**
     * injector instance
     *
     * @type  Injector
     */
    private $injector;
    /**
     * auth handler to handle authorization requests
     *
     * @type  AuthHandler
     */
    private $authHandler;

    /**
     * constructor
     *
     * @param  Injector  $injector
     * @Inject
     */
    public function __construct(Injector $injector)
    {
        $this->injector  = $injector;
    }

    /**
     * sets auth handler
     *
     * @param   AuthHandler  $authHandler
     * @return  Routing
     * @Inject(optional=true)
     */
    public function setAuthHandler(AuthHandler $authHandler)
    {
        $this->authHandler = $authHandler;
        return $this;
    }

    /**
     * reply with given class or callable for GET request on given path
     *
     * @param   string                     $path      path this route is applicable for
     * @param   string|callable|Processor  $callback  code to be executed when the route is active
     * @return  \stubbles\webapp\ConfigurableRoute
     */
    public function onGet($path, $callback)
    {
        return $this->addRoute(new Route($path, $callback, 'GET'));
    }

    /**
     * reply with HTML file stored in pages path
     *
     * @param   string                     $path      optional  path this route is applicable for
     * @param   string|callable|Processor  $callback  optional  code to be executed when the route is active
     * @return  \stubbles\webapp\ConfigurableRoute
     * @since   4.0.0
     */
    public function passThroughOnGet($path = '/[a-zA-Z0-9-_]+.html$', $callback = 'stubbles\webapp\processor\HtmlFilePassThrough')
    {
        return $this->onGet($path, $callback);
    }

    /**
     * reply with given class or callable for HEAD request on given path
     *
     * @param   string                     $path      path this route is applicable for
     * @param   string|callable|Processor  $callback  code to be executed when the route is active
     * @return  \stubbles\webapp\ConfigurableRoute
     */
    public function onHead($path, $callback)
    {
        return $this->addRoute(new Route($path, $callback, 'HEAD'));
    }

    /**
     * reply with given class or callable for POST request on given path
     *
     * @param   string                     $path      path this route is applicable for
     * @param   string|callable|Processor  $callback  code to be executed when the route is active
     * @return  \stubbles\webapp\ConfigurableRoute
     */
    public function onPost($path, $callback)
    {
        return $this->addRoute(new Route($path, $callback, 'POST'));
    }

    /**
     * reply with given class or callable for PUT request on given path
     *
     * @param   string                     $path      path this route is applicable for
     * @param   string|callable|Processor  $callback  code to be executed when the route is active
     * @return  \stubbles\webapp\ConfigurableRoute
     */
    public function onPut($path, $callback)
    {
        return $this->addRoute(new Route($path, $callback, 'PUT'));
    }

    /**
     * reply with given class or callable for DELETE request on given path
     *
     * @param   string                     $path      path this route is applicable for
     * @param   string|callable|Processor  $callback  code to be executed when the route is active
     * @return  \stubbles\webapp\ConfigurableRoute
     */
    public function onDelete($path, $callback)
    {
        return $this->addRoute(new Route($path, $callback, 'DELETE'));
    }

    /**
     * reply with given class or callable for request method(s) on given path
     *
     * If no request method(s) specified it replies to request methods GET, HEAD,
     * POST, PUT and DELETE.
     *
     * @param   string                     $path           path this route is applicable for
     * @param   string|callable|Processor  $callback       code to be executed when the route is active
     * @param   string|string[]            $requestMethod  optional  request method(s) this route is applicable for
     * @return  \stubbles\webapp\ConfigurableRoute
     * @since   4.0.0
     */
    public function onAll($path, $callback, $requestMethod = null)
    {
        return $this->addRoute(new Route($path, $callback, $requestMethod));
    }

    /**
     * add a route definition
     *
     * @param   Route  $route
     * @return  Route
     */
    public function addRoute(Route $route)
    {
        $this->routes[] = $route;
        return $route;
    }

    /**
     * returns route which is applicable for given request
     *
     * @param   string|UriRequest  $calledUri      actually called uri
     * @param   string             $requestMethod  optional when $calledUri is an instance of UriRequest
     * @return  ProcessableRoute
     */
    public function findRoute($calledUri, $requestMethod = null)
    {
        $uriRequest  = UriRequest::castFrom($calledUri, $requestMethod);
        $routeConfig = $this->findRouteConfig($uriRequest);
        if (null !== $routeConfig) {
            return $this->handleMatchingRoute($uriRequest, $routeConfig);
        }

        if ($this->canFindRouteWithAnyMethod($uriRequest)) {
            return $this->handleNonMethodMatchingRoute($uriRequest);
        }

        return new MissingRoute($uriRequest,
                                $this->collectInterceptors($uriRequest),
                                SupportedMimeTypes::createWithDisabledContentNegotation()
        );
    }

    /**
     * creates a processable route for given route
     *
     * @param   UriRequest  $calledUri
     * @param   Route       $routeConfig
     * @return  ProcessableRoute
     */
    private function handleMatchingRoute(UriRequest $calledUri, Route $routeConfig)
    {
        if ($routeConfig->requiresAuth()) {
            if (null === $this->authHandler) {
                return new InternalServerErrorRoute('Requested route requires authorization, but no auth handler defined for application',
                                                    $calledUri,
                                                    $this->getSupportedMimeTypes($routeConfig)
                );
            }

            return new AuthorizingRoute($this->authHandler,
                                        $routeConfig,
                                        $this->createMatchingRoute($calledUri, $routeConfig)
            );
        }

        return $this->createMatchingRoute($calledUri, $routeConfig);
    }

    /**
     * creates matching route
     *
     * @param   UriRequest  $calledUri
     * @param   Route       $routeConfig
     * @return  MatchingRoute
     */
    private function createMatchingRoute(UriRequest $calledUri, Route $routeConfig)
    {
        return new MatchingRoute($calledUri,
                                 $this->collectInterceptors($calledUri, $routeConfig),
                                 $this->getSupportedMimeTypes($routeConfig),
                                 $routeConfig,
                                 $this->injector
        );
    }

    /**
     * creates a processable route when a route can be found regardless of request method
     *
     * @param   UriRequest  $calledUri
     * @return  ProcessableRoute
     */
    private function handleNonMethodMatchingRoute(UriRequest $calledUri)
    {
        if ($calledUri->methodEquals('OPTIONS')) {
            return new OptionsRoute($calledUri,
                                    $this->collectInterceptors($calledUri),
                                    SupportedMimeTypes::createWithDisabledContentNegotation(),
                                    $this->getAllowedMethods($calledUri)

            );
        }

        return new MethodNotAllowedRoute($calledUri,
                                         $this->collectInterceptors($calledUri),
                                         SupportedMimeTypes::createWithDisabledContentNegotation(),
                                         $this->getAllowedMethods($calledUri)
        );
    }

    /**
     * finds route based on called uri
     *
     * @param   UriRequest  $calledUri
     * @return  Route
     */
    private function findRouteConfig(UriRequest $calledUri)
    {
        foreach ($this->routes as $route) {
            if ($route->matches($calledUri)) {
                return $route;
            }
        }

        return null;
    }

    /**
     * returns list of allowed method for called uri
     *
     * @param   UriRequest  $calledUri
     * @return  string[]
     */
    private function getAllowedMethods(UriRequest $calledUri)
    {
        $allowedMethods = [];
        foreach ($this->routes as $route) {
            if ($route->matchesPath($calledUri)) {
                $allowedMethods = array_merge($allowedMethods, $route->allowedRequestMethods());
            }
        }

        if (in_array('GET', $allowedMethods) && !in_array('HEAD', $allowedMethods)) {
            $allowedMethods[] = 'HEAD';
        }

        return $allowedMethods;
    }

    /**
     * checks whether there is a route at all
     *
     * @param   UriRequest  $calledUri
     * @return  bool
     */
    private function canFindRouteWithAnyMethod(UriRequest $calledUri)
    {
        return count($this->getAllowedMethods($calledUri)) > 0;
    }

    /**
     * pre intercept with given class or callable on all GET requests
     *
     * @param   string|callable|PreInterceptor  $preInterceptor  pre interceptor to add
     * @param   string                          $path            optional  path for which pre interceptor should be executed
     * @return  RoutingConfigurator
     */
    public function preInterceptOnGet($preInterceptor, $path = null)
    {
        return $this->preIntercept($preInterceptor, $path, 'GET');
    }

    /**
     * pre intercept with given class or callable on all HEAD requests
     *
     * @param   string|callable|PreInterceptor  $preInterceptor  pre interceptor to add
     * @param   string                          $path            optional  path for which pre interceptor should be executed
     * @return  RoutingConfigurator
     */
    public function preInterceptOnHead($preInterceptor, $path = null)
    {
        return $this->preIntercept($preInterceptor, $path, 'HEAD');
    }

    /**
     * pre intercept with given class or callable on all POST requests
     *
     * @param   string|callable|PreInterceptor  $preInterceptor  pre interceptor to add
     * @param   string                          $path            optional  path for which pre interceptor should be executed
     * @return  RoutingConfigurator
     */
    public function preInterceptOnPost($preInterceptor, $path = null)
    {
        return $this->preIntercept($preInterceptor, $path, 'POST');
    }

    /**
     * pre intercept with given class or callable on all PUT requests
     *
     * @param   string|callable|PreInterceptor  $preInterceptor  pre interceptor to add
     * @param   string                          $path            optional  path for which pre interceptor should be executed
     * @return  RoutingConfigurator
     */
    public function preInterceptOnPut($preInterceptor, $path = null)
    {
        return $this->preIntercept($preInterceptor, $path, 'PUT');
    }

    /**
     * pre intercept with given class or callable on all DELETE requests
     *
     * @param   string|callable|PreInterceptor  $preInterceptor  pre interceptor to add
     * @param   string                          $path            optional  path for which pre interceptor should be executed
     * @return  RoutingConfigurator
     */
    public function preInterceptOnDelete($preInterceptor, $path = null)
    {
        return $this->preIntercept($preInterceptor, $path, 'DELETE');
    }

    /**
     * pre intercept with given class or callable on all requests
     *
     * @param   string|callable|PreInterceptor  $preInterceptor  pre interceptor to add
     * @param   string                          $path            optional  path for which pre interceptor should be executed
     * @param   string                          $requestMethod   request method for which interceptor should be executed
     * @return  RoutingConfigurator
     * @throws  IllegalArgumentException
     */
    public function preIntercept($preInterceptor, $path = null, $requestMethod = null)
    {
        if (!is_callable($preInterceptor) && !($preInterceptor instanceof PreInterceptor) && !class_exists($preInterceptor)) {
            throw new IllegalArgumentException('Given pre interceptor must be a callable, an instance of stubbles\webapp\interceptor\PreInterceptor or a class name of an existing pre interceptor class');
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
     * @param   string|callable|PostInterceptor  $postInterceptor  post interceptor to add
     * @param   string                           $path             optional  path for which post interceptor should be executed
     * @return  RoutingConfigurator
     */
    public function postInterceptOnGet($postInterceptor, $path = null)
    {
        return $this->postIntercept($postInterceptor, $path, 'GET');
    }

    /**
     * post intercept with given class or callable on all HEAD requests
     *
     * @param   string|callable|PostInterceptor  $postInterceptor  post interceptor to add
     * @param   string                           $path             optional  path for which post interceptor should be executed
     * @return  RoutingConfigurator
     */
    public function postInterceptOnHead($postInterceptor, $path = null)
    {
        return $this->postIntercept($postInterceptor, $path, 'HEAD');
    }

    /**
     * post intercept with given class or callable on all POST requests
     *
     * @param   string|callable|PostInterceptor  $postInterceptor  post interceptor to add
     * @param   string                           $path             optional  path for which post interceptor should be executed
     * @return  RoutingConfigurator
     */
    public function postInterceptOnPost($postInterceptor, $path = null)
    {
        return $this->postIntercept($postInterceptor, $path, 'POST');
    }

    /**
     * post intercept with given class or callable on all PUT requests
     *
     * @param   string|callable|PostInterceptor  $postInterceptor  post interceptor to add
     * @param   string                           $path             optional  path for which post interceptor should be executed
     * @return  RoutingConfigurator
     */
    public function postInterceptOnPut($postInterceptor, $path = null)
    {
        return $this->postIntercept($postInterceptor, $path, 'PUT');
    }

    /**
     * post intercept with given class or callable on all DELETE requests
     *
     * @param   string|callable|PostInterceptor  $postInterceptor  post interceptor to add
     * @param   string                           $path             optional  path for which post interceptor should be executed
     * @return  RoutingConfigurator
     */
    public function postInterceptOnDelete($postInterceptor, $path = null)
    {
        return $this->postIntercept($postInterceptor, $path, 'DELETE');
    }

    /**
     * post intercept with given class or callable on all requests
     *
     * @param   string|callable|PostInterceptor  $postInterceptor  post interceptor to add
     * @param   string                           $path             optional  path for which post interceptor should be executed
     * @param   string                           $requestMethod    optional  request method for which interceptor should be executed
     * @return  RoutingConfigurator
     * @throws  IllegalArgumentException
     */
    public function postIntercept($postInterceptor, $path = null, $requestMethod = null)
    {
        if (!is_callable($postInterceptor) && !($postInterceptor instanceof PostInterceptor) && !class_exists($postInterceptor)) {
            throw new IllegalArgumentException('Given pre interceptor must be a callable, an instance of stubbles\webapp\interceptor\PostInterceptor or a class name of an existing post interceptor class');
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
     * @param   UriRequest  $calledUri
     * @param   Route       $routeConfig
     * @return  Interceptors
     */
    private function collectInterceptors(UriRequest $calledUri, Route $routeConfig = null)
    {
        return new Interceptors($this->injector,
                                $this->getPreInterceptors($calledUri, $routeConfig),
                                $this->getPostInterceptors($calledUri, $routeConfig)
        );
    }

    /**
     * returns list of applicable pre interceptors for this request
     *
     * @param   UriRequest  $calledUri
     * @param   Route       $routeConfig
     * @return  array
     */
    private function getPreInterceptors(UriRequest $calledUri, Route $routeConfig = null)
    {
        $global = $this->getApplicable($calledUri, $this->preInterceptors);
        if (null === $routeConfig) {
            return $global;
        }

        return array_merge($global, $routeConfig->preInterceptors());
    }

    /**
     * returns list of applicable post interceptors for this request
     *
     * @param   UriRequest  $calledUri
     * @param   Route       $routeConfig
     * @return  array
     */
    private function getPostInterceptors(UriRequest $calledUri, Route $routeConfig = null)
    {
        $global = $this->getApplicable($calledUri, $this->postInterceptors);
        if (null === $routeConfig) {
            return $global;
        }

        return array_merge($routeConfig->postInterceptors(), $global);
    }

    /**
     * calculates which interceptors are applicable for given request method
     *
     * @param   UriRequest  $calledUri
     * @param   array[]     $interceptors   list of interceptors to check
     * @return  array
     */
    private function getApplicable(UriRequest $calledUri, array $interceptors)
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
     * add a supported mime type
     *
     * @param   string  $mimeType
     * @return  Routing
     */
    public function supportsMimeType($mimeType)
    {
        $this->mimeTypes[] = $mimeType;
        return $this;
    }

    /**
     * disables content negotation
     *
     * @return  Routing
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
     * @param   Route  $routeConfig
     * @return  SupportedMimeTypes
     */
    private function getSupportedMimeTypes(Route $routeConfig = null)
    {
        if ($this->disableContentNegotation) {
            return SupportedMimeTypes::createWithDisabledContentNegotation();
        }

        if (null !== $routeConfig) {
            return $routeConfig->supportedMimeTypes($this->mimeTypes);
        }

        return new SupportedMimeTypes($this->mimeTypes);
    }
}
