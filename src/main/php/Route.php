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
use stubbles\lang;
use stubbles\webapp\auth\Roles;
use stubbles\webapp\response\SupportedMimeTypes;
use stubbles\webapp\routing\RoutingAnnotations;
/**
 * Represents information about a route that can be called.
 *
 * @since  2.0.0
 */
class Route implements ConfigurableRoute
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
     * list of annotations on callback
     *
     * @type  \stubbles\webapp\routing\RoutingAnnotations
     */
    private $routingAnnotations;
    /**
     * request method this route is applicable for
     *
     * @type  string[]
     */
    private $allowedRequestMethods;
    /**
     * list of pre interceptors which should be applied to this route
     *
     * @type  string[]|\Closure[]
     */
    private $preInterceptors          = [];
    /**
     * list of post interceptors which should be applied to this route
     *
     * @type  string[]|\Closure[]
     */
    private $postInterceptors         = [];
    /**
     * whether route requires https
     *
     * @type  bool
     */
    private $requiresHttps            = false;
    /**
     * switch whether login is required for this route
     *
     * @type  bool
     */
    private $requiresLogin            = false;
    /**
     * required role to access the route
     *
     * @type  string
     */
    private $requiredRole;
    /**
     * whether the route needs access to roles of the user
     *
     * @type  bool
     */
    private $rolesAware;
    /**
     * list of mime types supported by this route
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
     * map of additional formatters for this route
     *
     * @type  array
     */
    private $formatter                = [];

    /**
     * constructor
     *
     * If no request method(s) specified it matches request methods GET, HEAD,
     * POST, PUT and DELETE.
     *
     * @param   string                                      $path           path this route is applicable for
     * @param   string|callable|\stubbles\webapp\Processor  $callback       code to be executed when the route is active
     * @param   string|string[]                             $requestMethod  optional  request method(s) this route is applicable for
     * @throws  \InvalidArgumentException
     */
    public function __construct($path, $callback, $requestMethod = null)
    {
        if (!is_callable($callback) && !($callback instanceof Processor) && !class_exists($callback)) {
            throw new \InvalidArgumentException('Given callback for path "' . $path . '" must be a callable, an instance of stubbles\webapp\Processor or a class name of an existing processor class');
        }

        $this->path                  = $path;
        $this->callback              = $callback;
        $this->allowedRequestMethods = $this->arrayFrom($requestMethod);
    }

    /**
     * turns given value into a list
     *
     * @param   string|string[]  $requestMethod
     * @return  string
     * @throws  \InvalidArgumentException
     */
    private function arrayFrom($requestMethod)
    {
        if (is_string($requestMethod)) {
            return [$requestMethod];
        }

        if (null === $requestMethod) {
            return ['GET', 'HEAD', 'POST', 'PUT', 'DELETE'];
        }

        if (is_array($requestMethod)) {
            return $requestMethod;
        }

        throw new \InvalidArgumentException('Given request method must be null, a string or an array, but received ' . lang\getType($requestMethod));
    }

    /**
     * returns request method
     *
     * @return  string[]
     */
    public function allowedRequestMethods()
    {
        return $this->allowedRequestMethods;
    }

    /**
     * checks if this route is applicable for given request
     *
     * @param   \stubbles\webapp\UriRequest  $calledUri  current request uri
     * @return  bool
     */
    public function matches(UriRequest $calledUri)
    {
        if (!$this->matchesPath($calledUri)) {
            return false;
        }

        if (in_array($calledUri->method(), $this->allowedRequestMethods)) {
            return true;
        }

        if (in_array('GET', $this->allowedRequestMethods)) {
            return $calledUri->methodEquals('HEAD');
        }

        return false;
    }

    /**
     * checks if this route is applicable for given request path
     *
     * @param   \stubbles\webapp\UriRequest  $calledUri  current request uri
     * @return  bool
     */
    public function matchesPath(UriRequest $calledUri)
    {
        return $calledUri->satisfiesPath($this->path);
    }

    /**
     * returns path this route is applicable for
     *
     * @return  string
     */
    public function configuredPath()
    {
        return $this->path;
    }

    /**
     * returns callback for this route
     *
     * @return  string|callable|\stubbles\webapp\Processor
     */
    public function callback()
    {
        return $this->callback;
    }

    /**
     * add a pre interceptor for this route
     *
     * @param   string|callback|\stubbles\webapp\interceptor\PreInterceptor  $preInterceptor
     * @return  Route
     * @throws  \InvalidArgumentException
     */
    public function preIntercept($preInterceptor)
    {
        if (!is_callable($preInterceptor) && !($preInterceptor instanceof interceptor\PreInterceptor) && !class_exists($preInterceptor)) {
            throw new \InvalidArgumentException('Given pre interceptor must be a callable, an instance of stubbles\webapp\interceptor\PreInterceptor or a class name of an existing pre interceptor class');
        }

        $this->preInterceptors[] = $preInterceptor;
        return $this;
    }

    /**
     * returns list of pre interceptors which should be applied to this route
     *
     * @return  string[]|\Closure[]
     */
    public function preInterceptors()
    {
        return $this->preInterceptors;
    }

    /**
     * add a post interceptor for this route
     *
     * @param   string|callback|\stubbles\webapp\interceptor\PostInterceptor  $postInterceptor
     * @return  \stubbles\webapp\Route
     * @throws  \InvalidArgumentException
     */
    public function postIntercept($postInterceptor)
    {
        if (!is_callable($postInterceptor) && !($postInterceptor instanceof interceptor\PostInterceptor) && !class_exists($postInterceptor)) {
            throw new \InvalidArgumentException('Given pre interceptor must be a callable, an instance of stubbles\webapp\interceptor\PostInterceptor or a class name of an existing post interceptor class');
        }

        $this->postInterceptors[] = $postInterceptor;
        return $this;
    }

    /**
     * returns list of post interceptors which should be applied to this route
     *
     * @return  string[]|\Closure[]
     */
    public function postInterceptors()
    {
        return $this->postInterceptors;
    }

    /**
     * make route only available via https
     *
     * @return  \stubbles\webapp\Route
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
        if ($this->requiresHttps) {
            return true;
        }

        $this->requiresHttps = $this->routingAnnotations()->requiresHttps();
        return $this->requiresHttps;
    }

    /**
     * makes route only available if a user is logged in
     *
     * @return  \stubbles\webapp\Route
     * @since   3.0.0
     */
    public function withLoginOnly()
    {
        $this->requiresLogin = true;
        return $this;
    }

    /**
     * checks whether auth is required
     *
     * @return  bool
     */
    public function requiresAuth()
    {
        return $this->requiresLogin() || $this->requiresRoles();
    }

    /**
     * checks whether login is required
     *
     * @return  bool
     */
    private function requiresLogin()
    {
        if ($this->requiresLogin) {
            return true;
        }

        $this->requiresLogin = $this->routingAnnotations()->requiresLogin();
        return $this->requiresLogin;
    }

    /**
     * adds a role which is required to access the route
     *
     * @param   string  $requiredRole
     * @return  \stubbles\webapp\Route
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
    public function requiresRoles()
    {
        return (null !== $this->requiredRole()) || $this->rolesAware();
    }

    /**
     * checks whether route is satisfied by the given roles
     *
     * @param   \stubbles\webapp\auth\Roles  $roles
     * @return  bool
     */
    public function satisfiedByRoles(Roles $roles = null)
    {
        if (null === $roles) {
            return false;
        }

        if ($this->rolesAware()) {
            return true;
        }

        return $roles->contain($this->requiredRole());
    }

    /**
     * checks whether the route wants to be aware of roles
     *
     * Roles aware means that a resource might work different depending on the
     * roles a user has, but that access to the resource in general is not
     * forbidden even if the user doesn't have any of the roles.
     *
     * @return  bool
     */
    private function rolesAware()
    {
        if (null === $this->rolesAware) {
            $this->rolesAware = $this->routingAnnotations()->rolesAware();
        }

        return $this->rolesAware;
    }

    /**
     * returns required role for this route
     *
     * @return  string
     */
    private function requiredRole()
    {
        if (null === $this->requiredRole) {
            $this->requiredRole = $this->routingAnnotations()->requiredRole();
        }

        return $this->requiredRole;
    }

    /**
     * add a mime type which this route supports
     *
     * @param   string  $mimeType
     * @param   string  $formatterClass  optional  special formatter class to be used for given mime type on this route
     * @return  \stubbles\webapp\Route
     */
    public function supportsMimeType($mimeType, $formatterClass = null)
    {
        $this->mimeTypes[] = $mimeType;
        if (null !== $formatterClass) {
            $this->formatter[$mimeType] = $formatterClass;
        }

        return $this;
    }

    /**
     * returns list of mime types supported by this route
     *
     * @param   string[]  $globalMimeTypes  list of globally supported mime types
     * @return  \stubbles\webapp\response\SupportedMimeTypes
     */
    public function supportedMimeTypes(array $globalMimeTypes = [])
    {
        if ($this->disableContentNegotation) {
            return SupportedMimeTypes::createWithDisabledContentNegotation();
        }

        return new SupportedMimeTypes(array_merge($this->mimeTypes,
                                                  $globalMimeTypes
                                      ),
                                      $this->formatter
        );
    }

    /**
     * disables content negotation
     *
     * @return  \stubbles\webapp\Route
     * @since   2.1.1
     */
    public function disableContentNegotiation()
    {
        $this->disableContentNegotation = true;
        return $this;
    }

    /**
     * returns list of callback annotations
     *
     * @return  \stubbles\webapp\routing\RoutingAnnotations
     */
    private function routingAnnotations()
    {
        if (null === $this->routingAnnotations) {
            $this->routingAnnotations = new RoutingAnnotations($this->callback);
        }

        return $this->routingAnnotations;
    }
}
