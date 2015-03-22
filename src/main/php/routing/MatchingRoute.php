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
use stubbles\webapp\UriRequest;
use stubbles\webapp\interceptor\Interceptors;
/**
 * Contains logic to process the route.
 *
 * @since  2.0.0
 */
class MatchingRoute extends AbstractProcessableRoute
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
     * @param  \stubbles\webapp\UriRequest                  $calledUri           actual called uri
     * @param  \stubbles\webapp\interceptor\Interceptors    $interceptors
     * @param  \stubbles\webapp\routing\SupportedMimeTypes  $supportedMimeTypes
     * @param  \stubbles\webapp\Route                       $route               route configuration
     */
    public function __construct(
            Injector $injector,
            UriRequest $calledUri,
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
     * @return  bool
     */
    public function process(Request $request, Response $response)
    {
        $uriPath  = $this->calledUri->path($this->route->configuredPath());
        $callback = $this->route->callback();
        if (is_callable($callback)) {
            return $this->result(call_user_func_array($callback, [$request, $response, $uriPath]));
        }

        if ($callback instanceof Processor) {
            return $this->result($callback->process($request, $response, $uriPath));
        }

        $processor = $this->injector->getInstance($callback);
        if (!($processor instanceof Processor)) {
            $response->internalServerError('Configured callback class ' . $callback . ' for route ' . $uriPath . ' is not an instance of stubbles\webapp\Processor');
            return false;
        }

        return $this->result($processor->process($request, $response, $uriPath));
    }

    /**
     * calculates result from return value
     *
     * Result will be false if return value from callback is false. If callback
     * returns any other value result will be true.
     *
     * @param   bool  $returnValue
     * @return  bool
     */
    private function result($returnValue)
    {
        if (false === $returnValue) {
            return false;
        }

        return true;
    }
}
