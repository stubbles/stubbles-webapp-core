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
use stubbles\input\web\WebRequest;
use stubbles\ioc\Injector;
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

        $instance = $this->injector->getInstance($preInterceptor);
        if (!($instance instanceof PreInterceptor)) {
            $response->internalServerError('Configured pre interceptor ' . $preInterceptor . ' is not an instance of net\stubbles\webapp\interceptor\PreInterceptor');
            return false;
        }

        return $instance->preProcess($request, $response);
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

        $instance = $this->injector->getInstance($postInterceptor);
        if (!($instance instanceof PostInterceptor)) {
            $response->internalServerError('Configured post interceptor ' . $postInterceptor . ' is not an instance of net\stubbles\webapp\interceptor\PostInterceptor');
            return false;
        }

        return $instance->postProcess($request, $response);
    }
}
