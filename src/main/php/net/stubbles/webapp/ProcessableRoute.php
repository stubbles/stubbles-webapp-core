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
use net\stubbles\lang\exception\RuntimeException;
use net\stubbles\webapp\response\Response;
/**
 * Contains logic to process the route.
 *
 * @since  2.0.0
 */
class ProcessableRoute extends BaseObject
{
    /**
     * route configuration
     *
     * @type  Route
     */
    private $route;
    /**
     * actual called uri
     *
     * @type  UriRequest
     */
    private $calledUri;
    /**
     * list of pre interceptors to be processed
     *
     * @type  array
     */
    private $preInterceptors;
    /**
     * list of post interceptors to be processed
     *
     * @type  array
     */
    private $postInterceptors;
    /**
     * injector instance
     *
     * @type  Injector
     */
    private $injector;

    /**
     * constructor
     *
     * @param  Route       $route             route configuration
     * @param  UriRequest  $calledUri         actual called uri
     * @param  array       $preInterceptors   list of pre interceptors to be processed
     * @param  array       $postInterceptors  list of post interceptors to be processed
     * @param  Injector    $injector
     */
    public function __construct(Route $route,
                                UriRequest $calledUri,
                                array $preInterceptors,
                                array $postInterceptors,
                                Injector $injector)
    {
        $this->route            = $route;
        $this->calledUri        = $calledUri;
        $this->preInterceptors  = $preInterceptors;
        $this->postInterceptors = $postInterceptors;
        $this->injector         = $injector;
    }

    /**
     * checks whether switch to https is required
     *
     * @return  bool
     */
    public function switchToHttps()
    {
        return (!$this->calledUri->isHttps() && $this->route->requiresHttps());
    }

    /**
     * returns https uri of current route
     *
     * @return  HttpUri
     */
    public function getHttpsUri()
    {
        return $this->calledUri->toHttps();
    }

    /**
     * checks if access to this route required authorization
     *
     * @return  bool
     */
    public function requiresRole()
    {
        return $this->route->requiresRole();
    }

    /**
     * checks whether this is an authorized request to this route
     *
     * @return  bool
     */
    public function getRequiredRole()
    {
        return $this->route->getRequiredRole();
    }

    /**
     * apply pre interceptors
     *
     * Returns false if one of the pre interceptors cancels the request.
     *
     * @param   WebRequest  $request    current request
     * @param   Response    $response   response to send
     * @return  bool
     */
    public function applyPreInterceptors(WebRequest $request, Response $response)
    {
        foreach ($this->preInterceptors as $interceptor) {
            if ($interceptor instanceof \Closure) {
                $interceptor($request, $response);
            } elseif (is_callable($interceptor)) {
                call_user_func_array($interceptor, array($request, $response));
            } elseif ($interceptor instanceof interceptor\PreInterceptor) {
                $interceptor->preProcess($request, $response);
            } else {
                $this->injector->getInstance($interceptor)
                               ->preProcess($request, $response);
            }

            if ($request->isCancelled()) {
                return false;
            }
        }

        return true;
    }

    /**
     * creates processor instance
     *
     * @param   WebRequest  $request    current request
     * @param   Response    $response   response to send
     * @return  bool
     * @throws  RuntimeException
     */
    public function process(WebRequest $request, Response $response)
    {

        $uriPath  = $this->route->getUriPath($this->calledUri);
        $callback = $this->route->getCallback();
        if ($callback instanceof \Closure) {
            $callback($request, $response, $uriPath);
        } elseif (is_callable($callback)) {
            call_user_func_array($callback, array($request, $response, $uriPath));
        } elseif ($callback instanceof Processor) {
            $callback->process($request, $response, $uriPath);
        } else {
            $processor = $this->injector->getInstance($callback);
            if (!($processor instanceof Processor)) {
                throw new RuntimeException('Configured callback class ' . $callback . ' for route ' . $uriPath->getMatched() . ' is not an instance of net\stubbles\webapp\Processor');
            }

            $processor->process($request, $response, $uriPath);
        }

        return !$request->isCancelled();
    }

    /**
     * apply post interceptors
     *
     * @param   WebRequest  $request    current request
     * @param   Response    $response   response to send
     * @return  bool
     */
    public function applyPostInterceptors(WebRequest $request, Response $response)
    {
        foreach ($this->postInterceptors as $interceptor) {
            if ($interceptor instanceof \Closure) {
                $interceptor($request, $response);
            } elseif (is_callable($interceptor)) {
                call_user_func_array($interceptor, array($request, $response));
            } elseif ($interceptor instanceof interceptor\PostInterceptor) {
                $interceptor->postProcess($request, $response);
            } else {
                $this->injector->getInstance($interceptor)
                               ->postProcess($request, $response);
            }

            if ($request->isCancelled()) {
                return false;
            }
        }

        return true;
    }
}
?>