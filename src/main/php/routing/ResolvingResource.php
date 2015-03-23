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
use stubbles\webapp\Processor;
use stubbles\webapp\Request;
use stubbles\webapp\Response;
use stubbles\webapp\interceptor\Interceptors;
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
    public function requiresHttps()
    {
        return (!$this->calledUri->isHttps() && $this->route->requiresHttps());
    }

    /**
     * triggers actual logic on this route
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
    public function data(Request $request, Response $response)
    {
        $uriPath  = $this->calledUri->path($this->route->configuredPath());
        $callback = $this->route->callback();
        if (is_callable($callback)) {
            return call_user_func_array($callback, [$request, $response, $uriPath]);
        }

        if ($callback instanceof Processor) {
            return $callback->process($request, $response, $uriPath);
        }

        $processor = $this->injector->getInstance($callback);
        if (!($processor instanceof Processor)) {
            return $response->internalServerError('Configured callback class ' . $callback . ' for route ' . $uriPath . ' is not an instance of stubbles\webapp\Processor');
        }

        return $processor->process($request, $response, $uriPath);
    }
}
