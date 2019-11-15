<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace stubbles\webapp\routing;
use stubbles\peer\http\Http;
use stubbles\peer\http\HttpUri;
use stubbles\webapp\Target;
use stubbles\webapp\auth\AuthConstraint;
use stubbles\webapp\interceptor\PreInterceptor;
use stubbles\webapp\interceptor\PostInterceptor;
use stubbles\webapp\routing\api\Resource;

use function stubbles\values\typeOf;
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
     * @type  string|callable|\stubbles\webapp\Processor
     */
    private $target;
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
     * @type  string[]|callable[]
     */
    private $preInterceptors          = [];
    /**
     * list of post interceptors which should be applied to this route
     *
     * @type  string[]|callable[]
     */
    private $postInterceptors         = [];
    /**
     * whether route requires https
     *
     * @type  bool
     */
    private $requiresHttps            = false;
    /**
     * auth constraint for this route
     *
     * @type  \stubbles\webapp\auth\AuthConstraint
     */
    private $authConstraint;
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
     * map of additional mime type classes for this route
     *
     * @type  string[]
     */
    private $mimeTypeClasses          = [];
    /**
     * whether route should be ignored in API index or not
     *
     * @type   bool
     * @since  6.1.0
     */
    private $ignoreInApiIndex         = false;

    /**
     * constructor
     *
     * If no request method(s) specified it matches request methods GET, HEAD,
     * POST, PUT and DELETE.
     *
     * @param   string                                   $path           path this route is applicable for
     * @param   string|callable|\stubbles\webapp\Target  $target         code to be executed when the route is active
     * @param   string|string[]                          $requestMethod  optional  request method(s) this route is applicable for
     * @throws  \InvalidArgumentException
     */
    public function __construct(string $path, $target, $requestMethod = null)
    {
        if (!is_callable($target) && !($target instanceof Target) && !class_exists((string) $target)) {
            throw new \InvalidArgumentException(
                    'Given target for path "' . $path . '" must be a callable,'
                    . ' an instance of ' . Target::class . ' or a classname of'
                    . ' an existing ' . Target::class . ' implementation'
            );
        }

        $this->path                  = $path;
        $this->target                = $target;
        $this->allowedRequestMethods = $this->arrayFrom($requestMethod);
    }

    /**
     * turns given value into a list
     *
     * @param   string|string[]|null  $requestMethod
     * @return  string[]
     * @throws  \InvalidArgumentException
     */
    private function arrayFrom($requestMethod): array
    {
        if (is_string($requestMethod)) {
            return [$requestMethod];
        }

        if (null === $requestMethod) {
            return [Http::GET, Http::HEAD, Http::POST, Http::PUT, Http::DELETE];
        }

        if (is_array($requestMethod)) {
            return $requestMethod;
        }

        throw new \InvalidArgumentException(
                'Given request method must be null, a string or an array, but received '
                . typeOf($requestMethod)
        );
    }

    /**
     * returns request method
     *
     * @return  string[]
     */
    public function allowedRequestMethods(): array
    {
        return $this->allowedRequestMethods;
    }

    /**
     * checks if this route is applicable for given request
     *
     * @param   \stubbles\webapp\routing\CalledUri  $calledUri  current request uri
     * @return  bool
     */
    public function matches(CalledUri $calledUri): bool
    {
        if (!$this->matchesPath($calledUri)) {
            return false;
        }

        if (in_array($calledUri->method(), $this->allowedRequestMethods)) {
            return true;
        }

        if (in_array(Http::GET, $this->allowedRequestMethods)) {
            return $calledUri->methodEquals(Http::HEAD);
        }

        return false;
    }

    /**
     * checks if this route is applicable for given request path
     *
     * @param   \stubbles\webapp\routing\CalledUri  $calledUri  current request uri
     * @return  bool
     */
    public function matchesPath(CalledUri $calledUri): bool
    {
        return $calledUri->satisfiesPath($this->path);
    }

    /**
     * returns path this route is applicable for
     *
     * @return  string
     */
    public function configuredPath(): string
    {
        return $this->path;
    }

    /**
     * returns callback for this route
     *
     * @return  string|callable|\stubbles\webapp\Target
     */
    public function target()
    {
        return $this->target;
    }

    /**
     * add a pre interceptor for this route
     *
     * @param   string|callable|\stubbles\webapp\interceptor\PreInterceptor  $preInterceptor
     * @return  \stubbles\webapp\routing\Route
     * @throws  \InvalidArgumentException
     */
    public function preIntercept($preInterceptor): ConfigurableRoute
    {
        if (!is_callable($preInterceptor) && !($preInterceptor instanceof PreInterceptor) && !class_exists((string) $preInterceptor)) {
            throw new \InvalidArgumentException(
                    'Given pre interceptor must be a callable, an instance of '
                    . PreInterceptor::class
                    . ' or a class name of an existing pre interceptor class'
            );
        }

        $this->preInterceptors[] = $preInterceptor;
        return $this;
    }

    /**
     * returns list of pre interceptors which should be applied to this route
     *
     * @return  string[]|callable[]
     */
    public function preInterceptors(): array
    {
        return $this->preInterceptors;
    }

    /**
     * add a post interceptor for this route
     *
     * @param   string|callable|\stubbles\webapp\interceptor\PostInterceptor  $postInterceptor
     * @return  \stubbles\webapp\routing\Route
     * @throws  \InvalidArgumentException
     */
    public function postIntercept($postInterceptor): ConfigurableRoute
    {
        if (!is_callable($postInterceptor) && !($postInterceptor instanceof PostInterceptor) && !class_exists((string) $postInterceptor)) {
            throw new \InvalidArgumentException(
                    'Given pre interceptor must be a callable, an instance of '
                    . PostInterceptor::class
                    . ' or a class name of an existing post interceptor class'
            );
        }

        $this->postInterceptors[] = $postInterceptor;
        return $this;
    }

    /**
     * returns list of post interceptors which should be applied to this route
     *
     * @return  string[]|callable[]
     */
    public function postInterceptors(): array
    {
        return $this->postInterceptors;
    }

    /**
     * make route only available via https
     *
     * @return  \stubbles\webapp\routing\Route
     */
    public function httpsOnly(): ConfigurableRoute
    {
        $this->requiresHttps = true;
        return $this;
    }

    /**
     * whether route is only available via https
     *
     * @return  bool
     */
    public function requiresHttps(): bool
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
     * @return  \stubbles\webapp\routing\Route
     * @since   3.0.0
     */
    public function withLoginOnly(): ConfigurableRoute
    {
        $this->authConstraint()->requireLogin();
        return $this;
    }

    /**
     * forbid the actual login
     *
     * Forbidding a login means that the user receives a 403 Forbidden response
     * in case he accesses a restricted resource but is not logged in yet.
     * Otherwise, he would just be redirected to the login uri of the
     * authentication provider.
     *
     * @return  \stubbles\webapp\routing\Route
     * @since   5.0.0
     */
    public function forbiddenWhenNotAlreadyLoggedIn(): ConfigurableRoute
    {
        $this->authConstraint()->forbiddenWhenNotAlreadyLoggedIn();
        return $this;
    }

    /**
     * adds a role which is required to access the route
     *
     * @param   string  $requiredRole
     * @return  \stubbles\webapp\routing\Route
     */
    public function withRoleOnly(string $requiredRole): ConfigurableRoute
    {
        $this->authConstraint()->requireRole($requiredRole);
        return $this;
    }

    /**
     * checks whether auth is required
     *
     * @return  bool
     */
    public function requiresAuth(): bool
    {
        return $this->authConstraint()->requiresAuth();
    }

    /**
     * returns auth constraint for this route
     *
     * @return  \stubbles\webapp\auth\AuthConstraint
     */
    public function authConstraint(): AuthConstraint
    {
        if (null === $this->authConstraint) {
            $this->authConstraint = new AuthConstraint($this->routingAnnotations());
        }

        return $this->authConstraint;
    }

    /**
     * add a mime type which this route supports
     *
     * @param   string  $mimeType
     * @param   string  $class     optional  special class to be used for given mime type on this route
     * @return  \stubbles\webapp\routing\Route
     * @throws  \InvalidArgumentException
     */
    public function supportsMimeType(string $mimeType, string $class = null): ConfigurableRoute
    {
        if (null === $class && !SupportedMimeTypes::provideDefaultClassFor($mimeType)) {
            throw new \InvalidArgumentException(
                    'No default class known for mime type ' . $mimeType
                    . ', please provide a class'
            );
        }

        $this->mimeTypes[] = $mimeType;
        if (null !== $class) {
            $this->mimeTypeClasses[$mimeType] = $class;
        }

        return $this;
    }

    /**
     * returns list of mime types supported by this route
     *
     * @param   string[]  $globalMimeTypes  optional list of globally supported mime types
     * @param   string[]  $globalClasses    optional list of globally defined mime type classes
     * @return  \stubbles\webapp\routing\SupportedMimeTypes
     */
    public function supportedMimeTypes(
            array $globalMimeTypes = [],
            array $globalClasses = []
    ): SupportedMimeTypes {
        if ($this->disableContentNegotation || $this->routingAnnotations()->isContentNegotiationDisabled()) {
            return SupportedMimeTypes::createWithDisabledContentNegotation();
        }


        return new SupportedMimeTypes(
                array_merge(
                        $this->routingAnnotations()->mimeTypes(),
                        $this->mimeTypes,
                        $globalMimeTypes
                ),
                array_merge(
                        $globalClasses,
                        $this->routingAnnotations()->mimeTypeClasses(),
                        $this->mimeTypeClasses
                )
        );
    }

    /**
     * disables content negotation
     *
     * @return  \stubbles\webapp\routing\Route
     * @since   2.1.1
     */
    public function disableContentNegotiation(): ConfigurableRoute
    {
        $this->disableContentNegotation = true;
        return $this;
    }

    /**
     * returns list of callback annotations
     *
     * @return  \stubbles\webapp\routing\RoutingAnnotations
     */
    private function routingAnnotations(): RoutingAnnotations
    {
        if (null === $this->routingAnnotations) {
            $this->routingAnnotations = new RoutingAnnotations($this->target);
        }

        return $this->routingAnnotations;
    }

    /**
     * hides route in API index
     *
     * @return  \stubbles\webapp\routing\Route
     * @since   6.1.0
     */
    public function excludeFromApiIndex(): ConfigurableRoute
    {
        $this->ignoreInApiIndex = true;
        return $this;
    }

    /**
     * checks whether route should be ignored when building the API index
     *
     * @return  bool
     * @since   6.1.0
     */
    public function shouldBeIgnoredInApiIndex(): bool
    {
        if ($this->ignoreInApiIndex) {
            return true;
        }

        return $this->routingAnnotations()->shouldBeIgnoredInApiIndex();
    }

    /**
     * returns route as resource
     *
     * @param   \stubbles\peer\http\HttpUri  $uri
     * @param   string[]                      $globalMimeTypes  list of globally supported mime types
     * @return  \stubbles\webapp\routing\api\Resource
     * @since   6.1.0
     */
    public function asResource(HttpUri $uri, array $globalMimeTypes = []): Resource
    {
        $routeUri = $uri->withPath($this->normalizePath());
        return new Resource(
                $this->resourceName(),
                $this->allowedRequestMethods,
                $this->requiresHttps() ? $routeUri->toHttps() : $routeUri,
                $this->supportedMimeTypes($globalMimeTypes)->asArray(),
                $this->routingAnnotations(),
                $this->authConstraint()
        );
    }

    /**
     * normalizes path for better understanding
     *
     * @return  string
     */
    private function normalizePath(): string
    {
        $path = $this->path;
        if (substr($path, -1) === '$') {
            $path = substr($path, 0, strlen($path) - 1);
        }

        if (substr($path, -1) === '?') {
            $path = substr($path, 0, strlen($path) - 1);
        }

        return $path;
    }

    /**
     * returns useful name for resource
     *
     * @return  string|null
     */
    private function resourceName()
    {
        if ($this->routingAnnotations()->hasName()) {
            return $this->routingAnnotations()->name();
        }

        if (is_string($this->target) && class_exists($this->target)) {
            return substr(
                    $this->target,
                    strrpos($this->target, '\\') + 1
            );
        } elseif (!is_callable($this->target) && is_object($this->target)) {
            return substr(
                    get_class($this->target),
                    strrpos(get_class($this->target), '\\') + 1
            );
        }

        return null;
    }
}
