<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace stubbles\webapp\auth;

use stubbles\peer\http\HttpUri;
use stubbles\webapp\Request;
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
     * @throws  AuthProviderException
     */
    public function authenticate(Request $request): ?User;

    /**
     * returns login uri
     *
     * The method is called when the authenticate() method returns <null> and a
     * redirect to a login URI is allowed for the resource.
     */
    public function loginUri(Request $request): string|HttpUri;

    /**
     * returns a list of challenges to send in response's 401 WWW-Authenticate header
     *
     * The method is called when the authenticate() method returns <null> and a
     * redirect to a login URI is not allowed for the resource, but a
     * 401 Unauthorized response should be send instead.
     *
     * @since   8.0.0
     * @return  string[]  list of challenges for the WWW-Authenticate header, must at least contain one
     */
    public function challengesFor(Request $request): array;
}
