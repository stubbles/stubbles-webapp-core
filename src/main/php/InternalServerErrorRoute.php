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
     * @type  \stubbles\webapp\UriRequest
     */
    private $calledUri;
    /**
     * list of available mime types for all routes
     *
     * @type  \stubbles\webapp\response\SupportedMimeTypes
     */
    private $supportedMimeTypes;

    /**
     * constructor
     *
     * @param  string                                        $errorMessage
     * @param  \stubbles\webapp\UriRequest                   $calledUri
     * @param  \stubbles\webapp\response\SupportedMimeTypes  $supportedMimeTypes
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
    public function requiresHttps()
    {
        return false;
    }

    /**
     * returns https uri of current route
     *
     * @return  \stubbles\peer\http\HttpUri
     */
    public function httpsUri()
    {
        return $this->calledUri->toHttps();
    }

    /**
     * returns list of supported mime types
     *
     * @return  \stubbles\webapp\response\SupportedMimeTypes
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
     * @param   \stubbles\input\web\WebRequest      $request   current request
     * @param   \stubbles\webapp\response\Response  $response  response to send
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
     * @param   \stubbles\input\web\WebRequest      $request   current request
     * @param   \stubbles\webapp\response\Response  $response  response to send
     * @return  bool
     */
    public function process(WebRequest $request, Response $response)
    {
        return false;
    }

    /**
     * apply post interceptors
     *
     * @param   \stubbles\input\web\WebRequest      $request   current request
     * @param   \stubbles\webapp\response\Response  $response  response to send
     * @return  bool
     */
    public function applyPostInterceptors(WebRequest $request, Response $response)
    {
        return false;
    }
}


