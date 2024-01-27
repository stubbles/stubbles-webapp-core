<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace stubbles\webapp;
/**
 * Interface for resolving resource targets.
 *
 * @api
 */
interface Target
{
    /**
     * resolves the request and returns resource data
     *
     * @param  Request   $request   current request
     * @param  Response  $response  response to send
     * @param  UriPath   $uriPath   information about called uri path
     * @return  mixed resource data to be rendered
     */
    public function resolve(Request $request, Response $response, UriPath $uriPath): mixed;
}
