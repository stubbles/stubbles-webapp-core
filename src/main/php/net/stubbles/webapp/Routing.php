<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  net\stubbles\webapp
 */
namespace net\stubbles\webapp;
use net\stubbles\ioc\Injector;
use net\stubbles\lang\exception\IllegalArgumentException;
use net\stubbles\webapp\interceptor\Interceptors;
use net\stubbles\webapp\interceptor\PreInterceptor;
use net\stubbles\webapp\interceptor\PostInterceptor;
use net\stubbles\webapp\response\SupportedMimeTypes;
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
    private $routes           = array();
    /**
     * list of global pre interceptors and to which request method they respond
     *
     * @type  array
     */
    private $preInterceptors  = array();
    /**
     * list of global post interceptors and to which request method they respond
     *
     * @type  array
     */
    private $postInterceptors = array();
    /**
     * list of route-independent supported mime types
     *
     * @type  string[]
     */
    private $mimeTypes        = array();
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
     * reply with given class or callable for GET request on given path
     *
     * @param   string           $path
     * @param   string|callable  $callback
     * @return  ConfigurableRoute
     */
    public function onGet($path, $callback)
    {
        return $this->addRoute(new Route($path, $callback, 'GET'));
    }

    /**
     * reply with given class or callable for HEAD request on given path
     *
     * @param   string           $path
     * @param   string|callable  $callback
     * @return  ConfigurableRoute
     */
    public function onHead($path, $callback)
    {
        return $this->addRoute(new Route($path, $callback, 'HEAD'));
    }

    /**
     * reply with given class or callable for POST request on given path
     *
     * @param   string           $path
     * @param   string|callable  $callback
     * @return  ConfigurableRoute
     */
    public function onPost($path, $callback)
    {
        return $this->addRoute(new Route($path, $callback, 'POST'));
    }

    /**
     * reply with given class or callable for PUT request on given path
     *
     * @param   string           $path
     * @param   string|callable  $callback
     * @return  ConfigurableRoute
     */
    public function onPut($path, $callback)
    {
        return $this->addRoute(new Route($path, $callback, 'PUT'));
    }

    /**
     * reply with given class or callable for DELETE request on given path
     *
     * @param   string           $path
     * @param   string|callable  $callback
     * @return  ConfigurableRoute
     */
    public function onDelete($path, $callback)
    {
        return $this->addRoute(new Route($path, $callback, 'DELETE'));
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
     * @param   UriRequest  $calledUri
     * @return  ProcessableRoute
     */
    public function findRoute(UriRequest $calledUri)
    {
        $routeConfig = $this->findRouteConfig($calledUri);
        if (null !== $routeConfig) {
            return new MatchingRoute($calledUri,
                                     $this->collectInterceptors($calledUri, $routeConfig),
                                     $this->getSupportedMimeTypes($routeConfig),
                                     $routeConfig,
                                     $this->injector
                   );
        }

        if ($this->canFindRouteWithAnyMethod($calledUri)) {
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

        return new MissingRoute($calledUri,
                                $this->collectInterceptors($calledUri),
                                SupportedMimeTypes::createWithDisabledContentNegotation()
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
        $allowedMethods = array();
        foreach ($this->routes as $route) {
            if ($route->matchesPath($calledUri)) {
                $allowedMethods[] = $route->getMethod();
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
     * @param   string|callable  $preInterceptor
     * @return  RoutingConfigurator
     */
    public function preInterceptOnGet($preInterceptor)
    {
        return $this->preIntercept($preInterceptor, 'GET');
    }

    /**
     * pre intercept with given class or callable on all HEAD requests
     *
     * @param   string|callable  $preInterceptor
     * @return  RoutingConfigurator
     */
    public function preInterceptOnHead($preInterceptor)
    {
        return $this->preIntercept($preInterceptor, 'HEAD');
    }

    /**
     * pre intercept with given class or callable on all POST requests
     *
     * @param   string|callable  $preInterceptor
     * @return  RoutingConfigurator
     */
    public function preInterceptOnPost($preInterceptor)
    {
        return $this->preIntercept($preInterceptor, 'POST');
    }

    /**
     * pre intercept with given class or callable on all PUT requests
     *
     * @param   string|callable  $preInterceptor
     * @return  RoutingConfigurator
     */
    public function preInterceptOnPut($preInterceptor)
    {
        return $this->preIntercept($preInterceptor, 'PUT');
    }

    /**
     * pre intercept with given class or callable on all DELETE requests
     *
     * @param   string|callable  $preInterceptor
     * @return  RoutingConfigurator
     */
    public function preInterceptOnDelete($preInterceptor)
    {
        return $this->preIntercept($preInterceptor, 'DELETE');
    }

    /**
     * pre intercept with given class or callable on all requests
     *
     * @param   string|callable|PreInterceptor  $preInterceptor  pre interceptor to add
     * @param   string                          $requestMethod   request method for which interceptor should be executed
     * @return  RoutingConfigurator
     * @throws  IllegalArgumentException
     */
    public function preIntercept($preInterceptor, $requestMethod = null)
    {
        if (!is_callable($preInterceptor) && !($preInterceptor instanceof PreInterceptor) && !class_exists($preInterceptor)) {
            throw new IllegalArgumentException('Given pre interceptor must be a callable, an instance of net\stubbles\webapp\interceptor\PreInterceptor or a class name of an existing pre interceptor class');
        }

        $this->preInterceptors[] = array('interceptor'   => $preInterceptor,
                                         'requestMethod' => $requestMethod
                                   );
        return $this;
    }

    /**
     * post intercept with given class or callable on all GET requests
     *
     * @param   string|callable  $postInterceptor
     * @return  RoutingConfigurator
     */
    public function postInterceptOnGet($postInterceptor)
    {
        return $this->postIntercept($postInterceptor, 'GET');
    }

    /**
     * post intercept with given class or callable on all HEAD requests
     *
     * @param   string|callable  $postInterceptor
     * @return  RoutingConfigurator
     */
    public function postInterceptOnHead($postInterceptor)
    {
        return $this->postIntercept($postInterceptor, 'HEAD');
    }

    /**
     * post intercept with given class or callable on all POST requests
     *
     * @param   string|callable  $postInterceptor
     * @return  RoutingConfigurator
     */
    public function postInterceptOnPost($postInterceptor)
    {
        return $this->postIntercept($postInterceptor, 'POST');
    }

    /**
     * post intercept with given class or callable on all PUT requests
     *
     * @param   string|callable  $postInterceptor
     * @return  RoutingConfigurator
     */
    public function postInterceptOnPut($postInterceptor)
    {
        return $this->postIntercept($postInterceptor, 'PUT');
    }

    /**
     * post intercept with given class or callable on all DELETE requests
     *
     * @param   string|callable  $postInterceptor
     * @return  RoutingConfigurator
     */
    public function postInterceptOnDelete($postInterceptor)
    {
        return $this->postIntercept($postInterceptor, 'DELETE');
    }

    /**
     * post intercept with given class or callable on all requests
     *
     * @param   string|callable|PostInterceptor  $postInterceptor  post interceptor to add
     * @param   string                           $requestMethod    request method for which interceptor should be executed
     * @return  RoutingConfigurator
     * @throws  IllegalArgumentException
     */
    public function postIntercept($postInterceptor, $requestMethod = null)
    {
        if (!is_callable($postInterceptor) && !($postInterceptor instanceof PostInterceptor) && !class_exists($postInterceptor)) {
            throw new IllegalArgumentException('Given pre interceptor must be a callable, an instance of net\stubbles\webapp\interceptor\PostInterceptor or a class name of an existing post interceptor class');
        }

        $this->postInterceptors[] = array('interceptor'   => $postInterceptor,
                                          'requestMethod' => $requestMethod
                                   );
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

        return array_merge($global, $routeConfig->getPreInterceptors());
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

        return array_merge($routeConfig->getPostInterceptors(), $global);
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
        $applicable = array();
        foreach ($interceptors as  $interceptor) {
            if ($calledUri->methodEquals($interceptor['requestMethod'])) {
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

        if (null !== $routeConfig && $routeConfig->isContentNegotationDisabled()) {
            return SupportedMimeTypes::createWithDisabledContentNegotation();
        }

        $routeMimeTypes = (($routeConfig !== null) ? ($routeConfig->getSupportedMimeTypes()) : (array()));
        return new SupportedMimeTypes(array_merge($routeMimeTypes,
                                                  $this->mimeTypes
                                      )
        );
    }
}
