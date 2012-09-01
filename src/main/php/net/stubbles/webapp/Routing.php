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
use net\stubbles\lang\BaseObject;
use net\stubbles\peer\http\AcceptHeader;
/**
 * Contains routing information and decides which route is applicable for given request.
 *
 * @since  2.0.0
 */
class Routing extends BaseObject implements RoutingConfigurator
{
    /**
     * current request
     *
     * @type  UriRequest
     */
    private $calledUri;
    /**
     * list of routes for the web app
     *
     * @type  Route[]
     */
    private $routes           = array();
    /**
     * selected route
     *
     * @type  Route
     */
    private $selectedRoute;
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
     * constructor
     *
     * @param  UriRequest  $calledUri
     */
    public function __construct(UriRequest $calledUri)
    {
        $this->calledUri = $calledUri;
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
     * returns list of allowed method for called uri
     *
     * @return  string[]
     */
    public function getAllowedMethods()
    {
        $allowedMethods = array();
        foreach ($this->routes as $route) {
            if ($route->matchesPath($this->calledUri)) {
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
     * @return  bool
     */
    public function canFindRouteWithAnyMethod()
    {
        return count($this->getAllowedMethods()) > 0;
    }

    /**
     * checks whether there is a route
     *
     * @return  bool
     */
    public function canFindRoute()
    {
        return null !== $this->findRoute();
    }

    /**
     * returns route qhich is applicable for given request
     *
     * @return  Route
     */
    public function findRoute()
    {
        if (null === $this->selectedRoute) {
            foreach ($this->routes as $route) {
                if ($route->matches($this->calledUri)) {
                    $this->selectedRoute = $route;
                }
            }
        }

        return $this->selectedRoute;
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
     * @param   string|Closure  $preInterceptor  pre interceptor to add
     * @param   string          $requestMethod   request method for which interceptor should be executed
     * @return  RoutingConfigurator
     */
    public function preIntercept($preInterceptor, $requestMethod = null)
    {
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
     * @param   string|Closure  $postInterceptor  post interceptor to add
     * @param   string          $requestMethod    request method for which interceptor should be executed
     * @return  RoutingConfigurator
     */
    public function postIntercept($postInterceptor, $requestMethod = null)
    {
        $this->postInterceptors[] = array('interceptor'   => $postInterceptor,
                                          'requestMethod' => $requestMethod
                                   );
        return $this;
    }

    /**
     * returns list of applicable pre interceptors for this request
     *
     * @return  string[]|Closure[]
     */
    public function getPreInterceptors()
    {
        $global = $this->getApplicable($this->preInterceptors);
        $route  = $this->findRoute();
        if (null === $route) {
            return $global;
        }

        return array_merge($global, $route->getPreInterceptors());
    }

    /**
     * returns list of applicable post interceptors for this request
     *
     * @return  string[]|Closure[]
     */
    public function getPostInterceptors()
    {
        $global = $this->getApplicable($this->postInterceptors);
        $route  = $this->findRoute();
        if (null === $route) {
            return $global;
        }

        return array_merge($route->getPostInterceptors(), $global);
    }

    /**
     * calculates which interceptors are applicable for given request method
     *
     * @param   string[]|Closure[]  $interceptors   list of pre/post interceptors to check
     * @return  string[]|Closure[]
     */
    private function getApplicable(array $interceptors)
    {
        $applicable = array();
        foreach ($interceptors as  $interceptor) {
            if ($this->calledUri->methodEquals($interceptor['requestMethod'])) {
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
     * negotiates best mime type based on accept header
     *
     * @param   AcceptHeader  $acceptedMimeTypes
     * @return  string
     */
    public function negotiateMimeType(AcceptHeader $acceptedMimeTypes)
    {
        $supportedMimeTypes = $this->getSupportedMimeTypes();
        if (count($supportedMimeTypes) === 0) {
            return 'text/html';
        }

        if (count($acceptedMimeTypes) === 0) {
            return array_shift($supportedMimeTypes);
        }

        if ($acceptedMimeTypes->hasSharedAcceptables($supportedMimeTypes)) {
            return $acceptedMimeTypes->findMatchWithGreatestPriority($supportedMimeTypes);
        }

        return null;
    }

    /**
     * returns list of supported mime types
     *
     * @return  string[]
     */
    public function getSupportedMimeTypes()
    {
        $route = $this->findRoute();
        if (null === $route) {
            return $this->mimeTypes;
        }

        return array_merge($route->getSupportedMimeTypes(), $this->mimeTypes);
    }
}
?>