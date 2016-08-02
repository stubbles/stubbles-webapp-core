<?php
declare(strict_types=1);
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
     * @param  \stubbles\webapp\Request   $request   current request
     * @param  \stubbles\webapp\Response  $response  response to send
     */
    public function postProcess(Request $request, Response $response);
}
