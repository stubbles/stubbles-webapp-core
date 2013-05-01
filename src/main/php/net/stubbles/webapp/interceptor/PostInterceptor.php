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
use net\stubbles\webapp\response\Response;
/**
 * Interface for post interceptors.
 *
 * Postinterceptors are called after all data processing is done. They can change
 * the response or add additional data to the response.
 */
interface PostInterceptor
{
    /**
     * does the postprocessing stuff
     *
     * @param  WebRequest  $request   access to request data
     * @param  Response    $response  access to response data
     */
    public function postProcess(WebRequest $request, Response $response);
}
?>