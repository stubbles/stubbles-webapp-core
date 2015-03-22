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
use stubbles\webapp\request\Request;
use stubbles\webapp\response\Response;
/**
 * Interface for processors.
 *
 * @api
 */
interface Processor
{
    /**
     * processes the request
     *
     * @param  \stubbles\webapp\request\Request    $request   current request
     * @param  \stubbles\webapp\response\Response  $response  response to send
     * @param  \stubbles\webapp\UriPath            $uriPath   information about called uri path
     */
    public function process(Request $request, Response $response, UriPath $uriPath);
}
