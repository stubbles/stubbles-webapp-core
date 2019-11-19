<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace stubbles\webapp\auth\session;
use stubbles\webapp\Request;
use stubbles\webapp\auth\AuthenticationProvider;
use stubbles\webapp\auth\User;
use stubbles\webapp\session\Session;
/**
 * Authentication provider which caches the user within the session.
 *
 * @since  5.0.0
 */
class CachingAuthenticationProvider implements AuthenticationProvider
{
    /**
     * session where user and roles are stored
     *
     * @type  \stubbles\webapp\session\Session
     */
    private $session;
    /**
     * provider which delivers authentication
     *
     * @type  \stubbles\webapp\auth\AuthenticationProvider
     */
    private $authenticationProvider;

    /**
     * constructor
     *
     * @param  \stubbles\webapp\session\Session              $session
     * @param  \stubbles\webapp\auth\AuthenticationProvider  $authenticationProvider
     * @Named{authenticationProvider}('original')
     */
    public function __construct(Session $session, AuthenticationProvider $authenticationProvider)
    {
        $this->session                = $session;
        $this->authenticationProvider = $authenticationProvider;
    }

    /**
     * authenticates that the given request is valid
     *
     * @param   \stubbles\webapp\Request  $request
     * @return  \stubbles\webapp\auth\User|null
     */
    public function authenticate(Request $request): ?User
    {
        if ($this->session->hasValue(User::SESSION_KEY)) {
            return $this->session->value(User::SESSION_KEY);
        }

        $user = $this->authenticationProvider->authenticate($request);
        if (null === $user) {
            return null;
        }

        $this->session->putValue(User::SESSION_KEY, $user);
        return $user;
    }

    /**
     * returns login uri
     *
     * @param   \stubbles\webapp\Request  $request
     * @return  string|\stubbles\peer\http\HttpUri
     */
    public function loginUri(Request $request)
    {
        return $this->authenticationProvider->loginUri($request);
    }

    /**
     * returns a list of challenges to send in response's 401 WWW-Authenticate header for given request
     *
     * The method is called when the authenticate() method returns <null> and a
     * redirect to a login URI is not allowed for the resource, but a
     * 401 Unauthorized response should be send instead.
     *
     * @since   8.0.0
     * @param   \stubbles\webapp\Request  $request
     * @return  string[]  list of challenges for the WWW-Authenticate header, must at least contain one
     */
    public function challengesFor(Request $request): array
    {
        return $this->authenticationProvider->challengesFor($request);
    }
}
