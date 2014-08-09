<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  stubbles\webapp
 */
namespace stubbles\webapp\auth;
use stubbles\input\web\WebRequest;
/**
 * Interface for authentication/authorization handlers.
 *
 * @api
 */
interface AuthHandler
{
    /**
     * checks whether request is authenticated
     *
     * @param   \stubbles\input\web\WebRequest  $request
     * @return  bool
     */
    public function isAuthenticated(WebRequest $request);

    /**
     * checks whether expected role is given
     *
     * @param   \stubbles\input\web\WebRequest  $request
     * @param   string                          $expectedRole
     * @return  bool
     */
    public function isAuthorized(WebRequest $request, $expectedRole);

    /**
     * returns login uri
     *
     * @param   \stubbles\input\web\WebRequest  $request
     * @return  string|\stubbles\peer\http\HttpUri
     */
    public function loginUri(WebRequest $request);
}
