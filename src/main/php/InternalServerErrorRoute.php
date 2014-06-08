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
use stubbles\webapp\response\Response;
use stubbles\webapp\response\SupportedMimeTypes;
/**
 * Description of InternalServerErrorRoute
 *
 * @since  3.0.0
 */
class InternalServerErrorRoute implements ProcessableRoute
{
    /**
     * error message to display
     *
     * @type  string
     */
    private $errorMessage;
    /**
     * called uri
     *
     * @type  UriRequest
     */
    private $calledUri;
    /**
     * list of available mime types for all routes
     *
     * @type  SupportedMimeTypes
     */
    private $supportedMimeTypes;

    /**
     * constructor
     *
     * @param  string              $errorMessage
     * @param  UriRequest          $calledUri
     * @param  SupportedMimeTypes  $supportedMimeTypes
     */
    public function __construct($errorMessage,
                                UriRequest $calledUri,
                                SupportedMimeTypes $supportedMimeTypes)
    {
        $this->errorMessage       = $errorMessage;
        $this->calledUri          = $calledUri;
        $this->supportedMimeTypes = $supportedMimeTypes;
    }

    /**
     * checks whether switch to https is required
     *
     * @return  bool
     */
    public function switchToHttps()
    {
        return false;
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
        $response->internalServerError($this->errorMessage);
        return false;
    }

    /**
     * creates processor instance
     *
     * @param   WebRequest  $request    current request
     * @param   Response    $response   response to send
     * @return  bool
     */
    public function process(WebRequest $request, Response $response)
    {
        return false;
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
        return false;
    }
}


