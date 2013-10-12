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
use net\stubbles\webapp\response\Response;
/**
 * Processable route which denotes a 404 Not Found.
 *
 * @since  2.2.0
 */
class MissingRoute extends AbstractProcessableRoute
{
    /**
     * checks whether switch to https is required
     *
     * @return  bool
     */
    public function switchToHttps()
    {
        return false;
    }

    /**
     * checks if access to this route required authorization
     *
     * @return  bool
     */
    public function requiresAuth()
    {
        return false;
    }

    /**
     * checks whether this is an authorized request to this route
     *
     * @param   AuthHandler  $authHandler
     * @return  bool
     */
    public function isAuthorized(AuthHandler $authHandler)
    {
        return true;
    }

    /**
     * checks whether route required login
     *
     * @param   AuthHandler  $authHandler
     * @return  bool
     */
    public function requiresLogin(AuthHandler $authHandler)
    {
        return false;
    }

    /**
     * creates processor instance
     *
     * @param   WebRequest  $request    current request
     * @param   Response    $response   response to send
     * @return  bool
     */
    public function process(WebRequest $request, Response $response)
    {
        $response->notFound();
        return true;
    }
}