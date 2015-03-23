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
use stubbles\webapp\Request;
use stubbles\webapp\Response;
/**
 * Contains logic to process the route.
 *
 * @since  2.0.0
 */
interface Resource
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
     * @param   \stubbles\webapp\Request   $request
     * @param   \stubbles\webapp\Response  $response  response to send
     * @return  bool
     * @since   6.0.0
     */
    public function negotiateMimeType(Request $request, Response $response);

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
     * @param   \stubbles\webapp\Request   $request   current request
     * @param   \stubbles\webapp\Response  $response  response to send
     * @return  bool
     */
    public function applyPreInterceptors(Request $request, Response $response);

    /**
     * returns the resource data model
     *
     * @param   \stubbles\webapp\Request   $request   current request
     * @param   \stubbles\webapp\Response  $response  response to send
     * @return  mixed
     */
    public function data(Request $request, Response $response);

    /**
     * apply post interceptors
     *
     * @param   \stubbles\webapp\Request   $request   current request
     * @param   \stubbles\webapp\Response  $response  response to send
     * @return  bool
     */
    public function applyPostInterceptors(Request $request, Response $response);
}
