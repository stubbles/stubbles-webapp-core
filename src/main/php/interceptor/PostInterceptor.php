<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  stubbles\webapp
 */
namespace stubbles\webapp\interceptor;
use stubbles\input\web\WebRequest;
use stubbles\webapp\response\Response;
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
     * @param  \stubbles\input\web\WebRequest      $request   current request
     * @param  \stubbles\webapp\response\Response  $response  response to send
     */
    public function postProcess(WebRequest $request, Response $response);
}
