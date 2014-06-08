<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  net\stubbles\webapp
 */
namespace net\stubbles\webapp;
use stubbles\input\web\WebRequest;
use net\stubbles\webapp\response\Response;
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
     * @param  WebRequest  $request   current request
     * @param  Response    $response  response to send
     * @param  UriPath     $uriPath   information about called uri path
     */
    public function process(WebRequest $request, Response $response, UriPath $uriPath);
}
