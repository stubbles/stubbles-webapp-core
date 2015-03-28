<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  stubbles\webapp
 */
namespace stubbles\webapp\routing;
use stubbles\webapp\Request;
use stubbles\webapp\Response;
/**
 * Represents a missing resource.
 *
 * @since  2.2.0
 */
class NotFound extends AbstractResource
{
    /**
     * checks whether switch to https is required
     *
     * @return  bool
     */
    public function requiresHttps()
    {
        return false;
    }

    /**
     * creates processor instance
     *
     * @param   \stubbles\webapp\Request   $request   current request
     * @param   \stubbles\webapp\Response  $response  response to send
     * @return  \stubbles\webapp\response\Error
     */
    public function resolve(Request $request, Response $response)
    {
        return $response->notFound();
    }
}