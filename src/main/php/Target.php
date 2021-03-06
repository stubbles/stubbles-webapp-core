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
     * @param  \stubbles\webapp\Request   $request   current request
     * @param  \stubbles\webapp\Response  $response  response to send
     * @param  \stubbles\webapp\UriPath   $uriPath   information about called uri path
     * @return  mixed
     */
    public function resolve(Request $request, Response $response, UriPath $uriPath);
}
