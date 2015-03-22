<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  stubbles\webapp
 */
namespace stubbles\webapp\routing;
use stubbles\webapp\request\Request;
use stubbles\webapp\response\Response;
/**
 * Contains logic to process the route.
 *
 * @since  2.0.0
 */
interface ProcessableRoute
{
    /**
     * checks whether switch to https is required
     *
     * @return  bool
     */
    public function requiresHttps();

    /**
     * returns https uri of current route
     *
     * @return  \stubbles\peer\http\HttpUri
     */
    public function httpsUri();

    /**
     * negotiates proper mime type for given request
     *
     * @param   \stubbles\webapp\response\Request  $request
     * @return  \stubbles\webapp\response\mimetypes\MimeType
     * @since   6.0.0
     */
    public function negotiateMimeType(Request $request);

    /**
     * returns list of supported mime types
     *
     * @return  \stubbles\webapp\routing\SupportedMimeTypes
     */
    public function supportedMimeTypes();

    /**
     * apply pre interceptors
     *
     * Returns false if one of the pre interceptors cancels the request.
     *
     * @param   \stubbles\webapp\request\Request    $request   current request
     * @param   \stubbles\webapp\response\Response  $response  response to send
     * @return  bool
     */
    public function applyPreInterceptors(Request $request, Response $response);

    /**
     * creates processor instance
     *
     * @param   \stubbles\webapp\request\Request    $request   current request
     * @param   \stubbles\webapp\response\Response  $response  response to send
     * @return  bool
     */
    public function process(Request $request, Response $response);

    /**
     * apply post interceptors
     *
     * @param   \stubbles\webapp\request\Request    $request   current request
     * @param   \stubbles\webapp\response\Response  $response  response to send
     * @return  bool
     */
    public function applyPostInterceptors(Request $request, Response $response);
}
