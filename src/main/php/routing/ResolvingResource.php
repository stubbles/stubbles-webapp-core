<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace stubbles\webapp\routing;
use stubbles\ioc\Injector;
use stubbles\webapp\Target;
use stubbles\webapp\Request;
use stubbles\webapp\Response;
/**
 * Resource which can resolve the request using a target.
 *
 * @since  2.0.0
 */
class ResolvingResource extends AbstractResource
{
    public function __construct(
        Injector $injector,
        CalledUri $calledUri,
        Interceptors $interceptors,
        SupportedMimeTypes $supportedMimeTypes,
        private Route $route
    ) {
        parent::__construct($injector, $calledUri, $interceptors, $supportedMimeTypes);
    }

    public function requiresHttps(): bool
    {
        return !$this->calledUri->isHttps() && $this->route->requiresHttps();
    }

    /**
     * triggers actual logic on this resource
     *
     * The logic might be capsuled in a closure, a callback, or a processor
     * class. The return value from this logic will be used to evaluate whether
     * post processors are called by the web app. A return value of false means
     * no post processor will be called, whereas any other or no return value
     * will result in post processors being called by the webapp.
     */
    public function resolve(Request $request, Response $response): mixed
    {
        $uriPath = $this->calledUri->path($this->route->configuredPath());
        $target  = $this->route->target();
        if (is_callable($target)) {
            return $target($request, $response, $uriPath);
        }

        $targetInstance = $this->instanceOf($target);
        if (null === $targetInstance) {
            return $response->internalServerError(
                sprintf(
                    'Configured target class %s for route %s is not an instance of %s',
                    $target,
                    $uriPath,
                    Target::class
                )
                    
            );
        }

        return $targetInstance->resolve($request, $response, $uriPath);
    }

    private function instanceOf(string|Target $target): ?Target
    {
        if (!is_string($target)) {
            return $target;
        }

        $targetInstance = $this->injector->getInstance($target);
        if (!($targetInstance instanceof Target)) {
            return null;
        }

        return $targetInstance;
    }
}
