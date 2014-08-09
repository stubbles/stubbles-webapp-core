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
     * The provider should return <null> if the request can not be authenticated.
     * In case it can not find a user because it stumbles about an error it can
     * not resolve it should throw an stubbles\webapp\auth\AuthProviderException.
     *
     * @param   \stubbles\input\web\WebRequest  $request
     * @return  \stubbles\webapp\auth\User
     * @throws  \stubbles\webapp\auth\AuthProviderException
     */
    public function authenticate(WebRequest $request);

    /**
     * returns login uri
     *
     * The method is called when the authenticate() method returns <null>.
     *
     * @param   \stubbles\input\web\WebRequest  $request
     * @return  string|\stubbles\peer\http\HttpUri
     */
    public function loginUri(WebRequest $request);
}
