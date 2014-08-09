<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  stubbles\webapp
 */
namespace stubbles\webapp\auth\session;
use stubbles\input\web\WebRequest;
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
     * @Inject
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
     * @param   \stubbles\input\web\WebRequest  $request
     * @return  \stubbles\webapp\auth\User
     */
    public function authenticate(WebRequest $request)
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
     * @param   \stubbles\input\web\WebRequest  $request
     * @return  string|\stubbles\peer\http\HttpUri
     */
    public function loginUri(WebRequest $request)
    {
        return $this->authenticationProvider->loginUri($request);
    }
}
