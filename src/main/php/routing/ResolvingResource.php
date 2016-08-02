<?php
declare(strict_types=1);
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
    /**
     * route configuration
     *
     * @type  \stubbles\webapp\Route
     */
    private $route;

    /**
     * constructor
     *
     * @param  \stubbles\ioc\Injector                       $injector
     * @param  \stubbles\webapp\routing\CalledUri           $calledUri           actual called uri
     * @param  \stubbles\webapp\interceptor\Interceptors    $interceptors
     * @param  \stubbles\webapp\routing\SupportedMimeTypes  $supportedMimeTypes
     * @param  \stubbles\webapp\Route                       $route               route configuration
     */
    public function __construct(
            Injector $injector,
            CalledUri $calledUri,
            Interceptors $interceptors,
            SupportedMimeTypes $supportedMimeTypes,
            Route $route)
    {
        parent::__construct($injector, $calledUri, $interceptors, $supportedMimeTypes);
        $this->route = $route;
    }

    /**
     * checks whether switch to https is required
     *
     * @return  bool
     */
    public function requiresHttps(): bool
    {
        return (!$this->calledUri->isHttps() && $this->route->requiresHttps());
    }

    /**
     * triggers actual logic on this resource
     *
     * The logic might be capsuled in a closure, a callback, or a processor
     * class. The return value from this logic will be used to evaluate whether
     * post processors are called by the web app. A return value of false means
     * no post processor will be called, whereas any other or no return value
     * will result in post processors being called by the webapp.
     *
     * @param   \stubbles\webapp\Request   $request   current request
     * @param   \stubbles\webapp\Response  $response  response to send
     * @return  mixed
     */
    public function resolve(Request $request, Response $response)
    {
        $uriPath = $this->calledUri->path($this->route->configuredPath());
        $target  = $this->route->target();
        if (is_callable($target)) {
            return $target($request, $response, $uriPath);
        }

        if ($target instanceof Target) {
            return $target->resolve($request, $response, $uriPath);
        }

        $targetInstance = $this->injector->getInstance($target);
        if (!($targetInstance instanceof Target)) {
            return $response->internalServerError(
                    'Configured target class ' . $target . ' for route ' . $uriPath
                    . ' is not an instance of ' . Target::class
            );
        }

        return $targetInstance->resolve($request, $response, $uriPath);
    }
}
