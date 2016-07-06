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
use stubbles\webapp\auth\AuthorizationProvider;
use stubbles\webapp\auth\Roles;
use stubbles\webapp\auth\User;
use stubbles\webapp\session\Session;
/**
 * Authorization provider which caches the roles within the session.
 *
 * @since  5.0.0
 */
class CachingAuthorizationProvider implements AuthorizationProvider
{
    /**
     * session where user and roles are stored
     *
     * @type  \stubbles\webapp\session\Session
     */
    private $session;
    /**
     * provider which delivers authorization
     *
     * @type  \stubbles\webapp\auth\AuthorizationProvider
     */
    private $authorizationProvider;

    /**
     * constructor
     *
     * @param  \stubbles\webapp\session\Session             $session
     * @param  \stubbles\webapp\auth\AuthorizationProvider  $authorizationProvider
     * @Named{authorizationProvider}('original')
     */
    public function __construct(Session $session, AuthorizationProvider $authorizationProvider)
    {
        $this->session               = $session;
        $this->authorizationProvider = $authorizationProvider;
    }

    /**
     * returns the roles available for this request and user
     *
     * @param   \stubbles\webapp\auth\User  $user
     * @return  \stubbles\webapp\auth\Roles
     */
    public function roles(User $user)
    {
        if ($this->session->hasValue(Roles::SESSION_KEY)) {
            return $this->session->value(Roles::SESSION_KEY);
        }

        $roles = $this->authorizationProvider->roles($user);
        $this->session->putValue(Roles::SESSION_KEY, $roles);
        return $roles;
    }
}
