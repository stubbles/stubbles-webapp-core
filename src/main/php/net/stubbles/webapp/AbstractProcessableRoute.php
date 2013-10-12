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
use net\stubbles\webapp\response\Response;
use net\stubbles\webapp\response\SupportedMimeTypes;
/**
 * Contains logic to process the route.
 *
 * @since  2.2.0
 */
abstract class AbstractProcessableRoute implements ProcessableRoute
{
    /**
     * actual called uri
     *
     * @type  UriRequest
     */
    protected $calledUri;
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
    protected $injector;
    /**
     * list of available mime types for all routes
     *
     * @type  SupportedMimeTypes
     */
    private $supportedMimeTypes;

    /**
     * constructor
     *
     * @param  UriRequest          $calledUri           actual called uri
     * @param  array               $preInterceptors     list of pre interceptors to be processed
     * @param  array               $postInterceptors    list of post interceptors to be processed
     * @param  Injector            $injector
     * @param  SupportedMimeTypes  $supportedMimeTypes
     */
    public function __construct(UriRequest $calledUri,
                                array $preInterceptors,
                                array $postInterceptors,
                                Injector $injector,
                                SupportedMimeTypes $supportedMimeTypes)
    {
        $this->calledUri          = $calledUri;
        $this->preInterceptors    = $preInterceptors;
        $this->postInterceptors   = $postInterceptors;
        $this->injector           = $injector;
        $this->supportedMimeTypes = $supportedMimeTypes;
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
     * returns list of supported mime types
     *
     * @return  SupportedMimeTypes
     */
    public function getSupportedMimeTypes()
    {
        return $this->supportedMimeTypes;
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
