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
use net\stubbles\input\web\WebRequest;
use net\stubbles\lang\Object;
use net\stubbles\webapp\response\Response;
/**
 * Interface for processors.
 *
 * @api
 */
interface Processor extends Object
{
    /**
     * processes the request
     *
     * @param  WebRequest  $request        current request
     * @param  Response    $response       response to send
     * @param  string[]    $pathArguments  any detected path arguments
     */
    public function process(WebRequest $request, Response $response, array $pathArguments);
}
?>