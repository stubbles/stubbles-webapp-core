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
 * interface for pre interceptors.
 *
 * Preinterceptors are called after all initializations have been done and
 * before processing of data starts.
 */
interface PreInterceptor
{
    /**
     * does the preprocessing stuff
     *
     * @param  \stubbles\input\web\WebRequest      $request   current request
     * @param  \stubbles\webapp\response\Response  $response  response to send
     */
    public function preProcess(WebRequest $request, Response $response);
}
