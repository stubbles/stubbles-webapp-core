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
use stubbles\webapp\interceptor\Interceptors;
use stubbles\webapp\response\Response;
use stubbles\webapp\response\SupportedMimeTypes;
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
     * interceptors to be processed
     *
     * @type  Interceptors
     */
    private $interceptors;
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
     * @param  Interceptors        $interceptors
     * @param  SupportedMimeTypes  $supportedMimeTypes
     */
    public function __construct(UriRequest $calledUri,
                                Interceptors $interceptors,
                                SupportedMimeTypes $supportedMimeTypes)
    {
        $this->calledUri          = $calledUri;
        $this->interceptors       = $interceptors;
        $this->supportedMimeTypes = $supportedMimeTypes;
    }

    /**
     * returns https uri of current route
     *
     * @return  HttpUri
     */
    public function httpsUri()
    {
        return $this->calledUri->toHttps();
    }

    /**
     * returns list of supported mime types
     *
     * @return  SupportedMimeTypes
     */
    public function supportedMimeTypes()
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
        return $this->interceptors->preProcess($request, $response);
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
        return $this->interceptors->postProcess($request, $response);
    }
}
