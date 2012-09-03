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
use net\stubbles\lang\BaseObject;
use net\stubbles\webapp\response\Response;
/**
 * Handles all pre and post interceptors.
 *
 * @since     2.0.0
 * @internal
 */
class InterceptorHandler extends BaseObject
{
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
     */
    public function __construct(Injector $injector)
    {
        $this->injector = $injector;
    }

    /**
     * apply pre interceptors
     *
     * Returns false if one of the pre interceptors cancels the request.
     *
     * @param   string|Closure  $preInterceptors
     * @param   WebRequest      $request
     * @param   Response        $response
     * @return  bool
     */
    public function applyPreInterceptors(array $preInterceptors, WebRequest $request, Response $response)
    {
        foreach ($preInterceptors as $interceptor) {
            if ($interceptor instanceof \Closure) {
                $interceptor($request, $response);
            } elseif (is_callable($interceptor)) {
                call_user_func_array($interceptor, array($request, $response));
            } elseif ($interceptor instanceof PreInterceptor) {
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
     * apply post interceptors
     *
     * @param   string|Closure  $postInterceptors
     * @param   WebRequest      $request
     * @param   Response        $response
     * @return  bool
     */
    public function applyPostInterceptors(array $postInterceptors, WebRequest $request, Response $response)
    {
        foreach ($postInterceptors as $interceptor) {
            if ($interceptor instanceof \Closure) {
                $interceptor($request, $response);
            } elseif (is_callable($interceptor)) {
                call_user_func_array($interceptor, array($request, $response));
            } elseif ($interceptor instanceof PostInterceptor) {
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
