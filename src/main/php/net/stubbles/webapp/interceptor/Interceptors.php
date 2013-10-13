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
        foreach ($this->preInterceptors as $preInterceptor) {
            if (false === $this->executePreInterceptor($preInterceptor, $request, $response)) {
                return false;
            }
        }

        return true;
    }

    /**
     * executes pre interceptor
     *
     * @param   mixed       $preInterceptor
     * @param   WebRequest  $request
     * @param   Response    $response
     * @return  bool
     */
    private function executePreInterceptor($preInterceptor, WebRequest $request, Response $response)
    {
        if ($preInterceptor instanceof \Closure) {
            return $preInterceptor($request, $response);
        }

        if (is_callable($preInterceptor)) {
            return call_user_func_array($preInterceptor, array($request, $response));
        }

        if ($preInterceptor instanceof PreInterceptor) {
            return $preInterceptor->preProcess($request, $response);
        }

        return $this->injector->getInstance($preInterceptor)
                              ->preProcess($request, $response);
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
        foreach ($this->postInterceptors as $postInterceptor) {
            if (false === $this->executePostInterceptor($postInterceptor, $request, $response)) {
                return false;
            }
        }

        return true;
    }

    /**
     * executes post interceptor
     *
     * @param   mixed       $postInterceptor
     * @param   WebRequest  $request
     * @param   Response    $response
     * @return  bool
     */
    private function executePostInterceptor($postInterceptor, WebRequest $request, Response $response)
    {
        if ($postInterceptor instanceof \Closure) {
            return $postInterceptor($request, $response);
        }

        if (is_callable($postInterceptor)) {
            return call_user_func_array($postInterceptor, array($request, $response));
        }

        if ($postInterceptor instanceof PostInterceptor) {
            return $postInterceptor->postProcess($request, $response);
        }

        return $this->injector->getInstance($postInterceptor)
                              ->postProcess($request, $response);
    }
}
