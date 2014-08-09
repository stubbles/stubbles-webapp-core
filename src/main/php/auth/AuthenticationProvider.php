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
 * An authentication provider delivers user information for the given request.
 *
 * @since  5.0.0
 */
interface AuthenticationProvider
{
    /**
     * authenticates that the given request is valid
     *
     * @param   \stubbles\input\web\WebRequest  $request
     * @return  \stubbles\webapp\auth\User
     */
    public function authenticate(WebRequest $request);

    /**
     * returns login uri
     *
     * @param   \stubbles\input\web\WebRequest  $request
     * @return  string|\stubbles\peer\http\HttpUri
     */
    public function loginUri(WebRequest $request);
}
