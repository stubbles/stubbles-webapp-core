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
use net\stubbles\input\web\WebRequest;
use net\stubbles\ioc\Injector;
use net\stubbles\lang\BaseObject;
use net\stubbles\lang\exception\IllegalArgumentException;
use net\stubbles\lang\exception\RuntimeException;
use net\stubbles\webapp\response\Response;
/**
 * Represents information about a route that can be called.
 *
 * @since  2.0.0
 */
class Route extends BaseObject implements ConfigurableRoute
{
    /**
     * path this route is applicable for
     *
     * @type  string
     */
    private $path;
    /**
     * code to be executed when the route is active
     *
     * @type  string|callback
     */
    private $callback;
    /**
     * request method this route is applicable for
     *
     * @type  string
     */
    private $requestMethod;
    /**
     * list of pre interceptors which should be applied to this route
     *
     * @type  string[]|Closure[]
     */
    private $preInterceptors  = array();
    /**
     * list of post interceptors which should be applied to this route
     *
     * @type  string[]|Closure[]
     */
    private $postInterceptors = array();
    /**
     * whether route requires https
     *
     * @type  bool
     */
    private $requiresHttps    = false;
    /**
     * required role to access the route
     *
     * @type  string
     */
    private $requiredRole;

    /**
     * constructor
     *
     * If no request method is provided this route matches all request methods.
     *
     * @param   string           $path           path this route is applicable for
     * @param   string|callback  $callback       code to be executed when the route is active
     * @param   string           $requestMethod  request method this route is applicable for
     * @throws  IllegalArgumentException
     */
    public function __construct($path, $callback, $requestMethod = null)
    {
        if (!is_callable($callback) && !class_exists($callback)) {
            throw new IllegalArgumentException('Given callback must be a callable or a class name of an existing class');
        }

        $this->path          = $path;
        $this->callback      = $callback;
        $this->requestMethod = $requestMethod;
    }

    /**
     * returns request method
     *
     * @return  string
     */
    public function getMethod()
    {
        return $this->requestMethod;
    }

    /**
     * checks if this route is applicable for given request
     *
     * @param   UriRequest  $calledUri  current request uri
     * @return  bool
     */
    public function matches(UriRequest $calledUri)
    {
        if (!$calledUri->methodEquals($this->requestMethod)) {
            return false;
        }

        return $this->matchesPath($calledUri);
    }

    /**
     * checks if this route is applicable for given request path
     *
     * @param   UriRequest  $calledUri  current request uri
     * @return  bool
     */
    public function matchesPath(UriRequest $calledUri)
    {
        return $calledUri->satisfiesPath($this->path);
    }

    /**
     * creates processor instance
     *
     * @param   UriRequest  $calledUri  current request uri
     * @param   Injector    $injector
     * @param   WebRequest  $request    current request
     * @param   Response    $response   response to send
     * @throws  RuntimeException
     */
    public function process(UriRequest $calledUri, Injector $injector, WebRequest $request, Response $response)
    {
        if ($this->callback instanceof \Closure) {
            $callback = $this->callback;
            $callback($request, $response, $calledUri->getPathArguments($this->path));
        } elseif (is_callable($this->callback)) {
            call_user_func_array($this->callback, array($request, $response, $calledUri->getPathArguments($this->path)));
        } else {
            $processor = $injector->getInstance($this->callback);
            if (!($processor instanceof Processor)) {
                throw new RuntimeException('Configured callback class ' . $this->callback . ' for route ' . $this->path . ' is not an instance of net\stubbles\webapp\Processor');
            }

            $processor->process($request, $response, $calledUri->getPathArguments($this->path));
        }
    }

    /**
     * add a pre interceptor for this route
     *
     * @param   string|Closure  $preInterceptor
     * @return  Route
     */
    public function preIntercept($preInterceptor)
    {
        $this->preInterceptors[] = $preInterceptor;
        return $this;
    }

    /**
     * returns list of pre interceptors which should be applied to this route
     *
     * @return  string[]|Closure[]
     */
    public function getPreInterceptors()
    {
        return $this->preInterceptors;
    }

    /**
     * add a post interceptor for this route
     *
     * @param   string|Closure  $preInterceptor
     * @return  Route
     */
    public function postIntercept($postInterceptor)
    {
        $this->postInterceptors[] = $postInterceptor;
        return $this;
    }

    /**
     * returns list of post interceptors which should be applied to this route
     *
     * @return  string[]|Closure[]
     */
    public function getPostInterceptors()
    {
        return $this->postInterceptors;
    }

    /**
     * make route only available via https
     *
     * @return  Route
     */
    public function httpsOnly()
    {
        $this->requiresHttps = true;
        return $this;
    }

    /**
     * whether route is only available via https
     *
     * @return  bool
     */
    public function requiresHttps()
    {
        return $this->requiresHttps;
    }

    /**
     * adds a role which is required to access the route
     *
     * @param   string  $requiredRole
     * @return  Route
     */
    public function withRoleOnly($requiredRole)
    {
        $this->requiredRole = $requiredRole;
        return $this;
    }

    /**
     * checks if access to this route required authorization
     *
     * @return  bool
     */
    public function requiresAuth()
    {
        return null !== $this->requiredRole;
    }

    /**
     * checks whether this is an authorized request to this route
     *
     * @param   AuthHandler  $authHandler
     * @return  bool
     */
    public function isAuthorized(AuthHandler $authHandler)
    {
        if (!$this->requiresAuth() || $authHandler->userHasRole($this->requiredRole)) {
            return true;
        }

        return false;
    }

    /**
     * checks whether route requires login
     *
     * @param   AuthHandler  $authHandler
     * @return  bool
     */
    public function requiresLogin(AuthHandler $authHandler)
    {
        if (!$this->requiresAuth() || $authHandler->userHasRole($this->requiredRole)) {
            return false;
        }

        if (!$authHandler->hasUser()) {
            return true;
        }

        return false;
    }
}
?>