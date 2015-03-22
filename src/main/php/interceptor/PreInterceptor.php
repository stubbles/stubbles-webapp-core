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
use stubbles\webapp\Request;
use stubbles\webapp\Response;
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
     * @param  \stubbles\webapp\Request   $request   current request
     * @param  \stubbles\webapp\Response  $response  response to send
     */
    public function preProcess(Request $request, Response $response);
}
