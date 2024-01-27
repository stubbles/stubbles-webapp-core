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
    /** @var string|callable|Target */
    private $target;
    private ?RoutingAnnotations $routingAnnotations = null;
    /** @var  string[] */
    private array $allowedRequestMethods;
    /**
     * list of pre interceptors which should be applied to this route
     *
     * @var  array<class-string<PreInterceptor>|callable|PreInterceptor>
     */
    private array $preInterceptors = [];
    /**
     * list of post interceptors which should be applied to this route
     *
     * @var  array<class-string<PostInterceptor>|callable|PostInterceptor>
     */
    private array $postInterceptors = [];
    private bool $requiresHttps = false;
    private ?AuthConstraint $authConstraint = null;
    /**
     * list of mime types supported by this route
     *
     * @var  string[]
     */
    private array $mimeTypes = [];
    /**
     * whether content negotation is disabled or not
     */
    private bool $disableContentNegotation = false;
    /**
     * map of additional mime type classes for this route
     *
     * @var  string[]
     */
    private array $mimeTypeClasses = [];
    /**
     * whether route should be ignored in API index or not
     *
     * @since  6.1.0
     */
    private bool $ignoreInApiIndex = false;

    /**
     * If no request method(s) specified it matches request methods GET, HEAD,
     * POST, PUT and DELETE.
     *
     * @param  string                                $path           path this route is applicable for
     * @param  class-string<Target>|callable|Target  $target         code to be executed when the route is active
     * @param  string|string[]                       $requestMethod  request method(s) this route is applicable for
     */
    public function __construct(
        private string $path,
        string|callable|Target $target,
        null|string|array $requestMethod = null
    ) {
        $this->target = $target;
        $this->allowedRequestMethods = $this->arrayFrom($requestMethod);
    }

    /**
     * @param   string|string[]|null  $requestMethod
     * @return  string[]
     */
    private function arrayFrom(null|string|array $requestMethod): array
    {
        if (is_string($requestMethod)) {
            return [$requestMethod];
        }

        if (null === $requestMethod) {
            return [Http::GET, Http::HEAD, Http::POST, Http::PUT, Http::DELETE];
        }

        return $requestMethod;
    }

    /**
     * @return  string[]
     */
    public function allowedRequestMethods(): array
    {
        return $this->allowedRequestMethods;
    }

    /**
     * checks if this route is applicable for given request
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
     */
    public function matchesPath(CalledUri $calledUri): bool
    {
        return $calledUri->satisfiesPath($this->path);
    }

    /**
     * returns path this route is applicable for
     */
    public function configuredPath(): string
    {
        return $this->path;
    }

    /**
     * returns callback for this route
     *
     * @return  class-string<Target>|callable|Target
     */
    public function target(): string|callable|Target
    {
        return $this->target;
    }

    /**
     * add a pre interceptor for this route
     *
     * @param  class-string<PreInterceptor>|callable|PreInterceptor  $preInterceptor
     */
    public function preIntercept(
        string|callable|PreInterceptor $preInterceptor
    ): ConfigurableRoute {
        $this->preInterceptors[] = $preInterceptor;
        return $this;
    }

    /**
     * returns list of pre interceptors which should be applied to this route
     *
     * @return  array<class-string<PreInterceptor>|callable|PreInterceptor>
     */
    public function preInterceptors(): array
    {
        return $this->preInterceptors;
    }

    /**
     * add a post interceptor for this route
     *
     * @param  class-string<PostInterceptor>|callable|PostInterceptor  $postInterceptor
     */
    public function postIntercept(
        string|callable|PostInterceptor $postInterceptor
    ): ConfigurableRoute {
        $this->postInterceptors[] = $postInterceptor;
        return $this;
    }

    /**
     * returns list of post interceptors which should be applied to this route
     *
     * @return  array<class-string<PostInterceptor>|callable|PostInterceptor>
     */
    public function postInterceptors(): array
    {
        return $this->postInterceptors;
    }

    /**
     * make route only available via https
     */
    public function httpsOnly(): ConfigurableRoute
    {
        $this->requiresHttps = true;
        return $this;
    }

    /**
     * whether route is only available via https
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
     * @since  3.0.0
     */
    public function withLoginOnly(): ConfigurableRoute
    {
        $this->authConstraint()->requireLogin();
        return $this;
    }

    /**
     * when user is not logged in respond with 401 Unauthorized
     *
     * Otherwise, the user would just be redirected to the login uri of the
     * authentication provider.
     *
     * @since  8.0.0
     */
    public function sendChallengeWhenNotLoggedIn(): ConfigurableRoute
    {
        $this->authConstraint()->sendChallengeWhenNotLoggedIn();
        return $this;
    }

    /**
     * forbid the actual login
     *
     * @deprecated  use sendChallengeWhenNotLoggedIn() instead, will be removed with 9.0.0
     * @since   5.0.0
     */
    public function forbiddenWhenNotAlreadyLoggedIn(): ConfigurableRoute
    {
        return $this->sendChallengeWhenNotLoggedIn();
    }

    /**
     * adds a role which is required to access the route
     */
    public function withRoleOnly(string $requiredRole): ConfigurableRoute
    {
        $this->authConstraint()->requireRole($requiredRole);
        return $this;
    }

    /**
     * checks whether auth is required
     */
    public function requiresAuth(): bool
    {
        return $this->authConstraint()->requiresAuth();
    }

    /**
     * returns auth constraint for this route
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
     * @throws  InvalidArgumentException
     */
    public function supportsMimeType(string $mimeType, ?string $class = null): ConfigurableRoute
    {
        if (null === $class && !SupportedMimeTypes::provideDefaultClassFor($mimeType)) {
            throw new InvalidArgumentException(
                sprintf(
                    'No default class known for mime type %s, please provide a class',
                    $mimeType
                )
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
     * @param   string[]  $globalMimeTypes  list of globally supported mime types
     * @param   array<string,class-string<MimeType>>  $globalClasses  list of globally defined mime type classes
     */
    public function supportedMimeTypes(
        array $globalMimeTypes = [],
        array $globalClasses = []
    ): SupportedMimeTypes {
        if (
            $this->disableContentNegotation
            || $this->routingAnnotations()->isContentNegotiationDisabled()
        ) {
            return SupportedMimeTypes::createWithDisabledContentNegotation();
        }

        /** @var  array<string,class-string<MimeType>>  $mimeTypeClasses */
        $mimeTypeClasses = array_merge(
            $globalClasses,
            $this->routingAnnotations()->mimeTypeClasses(),
            $this->mimeTypeClasses
        );
        return new SupportedMimeTypes(
            array_merge(
                $this->routingAnnotations()->mimeTypes(),
                $this->mimeTypes,
                $globalMimeTypes
            ),
            $mimeTypeClasses
        );
    }

    /**
     * disables content negotation
     *
     * @since  2.1.1
     */
    public function disableContentNegotiation(): ConfigurableRoute
    {
        $this->disableContentNegotation = true;
        return $this;
    }

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
     * @since  6.1.0
     */
    public function excludeFromApiIndex(): ConfigurableRoute
    {
        $this->ignoreInApiIndex = true;
        return $this;
    }

    /**
     * checks whether route should be ignored when building the API index
     *
     * @since  6.1.0
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
     * @param  string[]  $globalMimeTypes  list of globally supported mime types
     * @since  6.1.0
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
     */
    private function resourceName(): ?string
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
