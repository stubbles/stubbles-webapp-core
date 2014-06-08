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
use stubbles\input\web\WebRequest;
use stubbles\ioc\Injector;
use stubbles\webapp\interceptor\Interceptors;
use stubbles\webapp\response\Response;
use stubbles\webapp\response\SupportedMimeTypes;
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
     * @type  Route
     */
    private $route;
    /**
     * injector instance
     *
     * @type  Injector
     */
    private $injector;

    /**
     * constructor
     *
     * @param  UriRequest          $calledUri           actual called uri
     * @param  Interceptors        $interceptors
     * @param  SupportedMimeTypes  $supportedMimeTypes
     * @param  Route               $route               route configuration
     * @param  Injector            $injector
     */
    public function __construct(UriRequest $calledUri,
                                Interceptors $interceptors,
                                SupportedMimeTypes $supportedMimeTypes,
                                Route $route,
                                Injector $injector)
    {
        parent::__construct($calledUri,
                            $interceptors,
                            $supportedMimeTypes
        );
        $this->route    = $route;
        $this->injector = $injector;
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
     * @param   WebRequest  $request    current request
     * @param   Response    $response   response to send
     * @return  bool
     */
    public function process(WebRequest $request, Response $response)
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
            $response->internalServerError('Configured callback class ' . $callback . ' for route ' . $uriPath->getMatched() . ' is not an instance of stubbles\webapp\Processor');
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
