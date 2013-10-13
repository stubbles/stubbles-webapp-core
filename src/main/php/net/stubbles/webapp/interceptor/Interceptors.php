<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  net\stubbles\webapp
 */
namespace net\stubbles\webapp\interceptor;
use net\stubbles\input\web\WebRequest;
use net\stubbles\ioc\Injector;
use net\stubbles\webapp\response\Response;
/**
 * Interceptor handler.
 */
class Interceptors
{
    /**
     * list of global pre interceptors and to which request method they respond
     *
     * @type  array
     */
    private $preInterceptors;
    /**
     * list of global post interceptors and to which request method they respond
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
     * @param  Injector  $injector
     * @param  array     $preInterceptors
     * @param  array     $postInterceptors
     */
    public function __construct(Injector $injector, array $preInterceptors, array $postInterceptors)
    {
        $this->injector         = $injector;
        $this->preInterceptors  = $preInterceptors;
        $this->postInterceptors = $postInterceptors;
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
    public function preProcess(WebRequest $request, Response $response)
    {
        foreach ($this->preInterceptors as $interceptor) {
            if ($interceptor instanceof \Closure) {
                $result = $interceptor($request, $response);
            } elseif (is_callable($interceptor)) {
                $result = call_user_func_array($interceptor, array($request, $response));
            } elseif ($interceptor instanceof PreInterceptor) {
                $result = $interceptor->preProcess($request, $response);
            } else {
                $result = $this->injector->getInstance($interceptor)
                                         ->preProcess($request, $response);
            }

            if (false === $result) {
                return false;
            }
        }

        return true;
    }

    /**
     * apply post interceptors
     *
     * @param   WebRequest  $request    current request
     * @param   Response    $response   response to send
     * @return  bool
     */
    public function postProcess(WebRequest $request, Response $response)
    {
        foreach ($this->postInterceptors as $interceptor) {
            if ($interceptor instanceof \Closure) {
                $result = $interceptor($request, $response);
            } elseif (is_callable($interceptor)) {
                $result = call_user_func_array($interceptor, array($request, $response));
            } elseif ($interceptor instanceof PostInterceptor) {
                $result = $interceptor->postProcess($request, $response);
            } else {
                $result = $this->injector->getInstance($interceptor)
                                         ->postProcess($request, $response);
            }

            if (false === $result) {
                return false;
            }
        }

        return true;
    }
}
