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
use stubbles\webapp\routing\RoutingAnnotations;
/**
 * Contains auth informations about a route.
 *
 * @since  5.0.0
 */
class AuthConstraint
{
    /**
     * list of annotations on callback
     *
     * @type  \stubbles\webapp\routing\RoutingAnnotations
     */
    private $callbackAnnotatedWith;
    /**
     * switch whether login is required for this route
     *
     * @type  bool
     */
    private $requiresLogin         = false;
    /**
     * switch whether a login is allowed
     *
     * @type  bool
     */
    private $loginAllowed          = true;
    /**
     * required role to access the route
     *
     * @type  string
     */
    private $requiredRole;

    /**
     * constructor
     *
     * @param  \stubbles\webapp\routing\RoutingAnnotations  $routingAnnotations
     */
    public function __construct(RoutingAnnotations $routingAnnotations)
    {
        $this->callbackAnnotatedWith = $routingAnnotations;
    }

    /**
     * require a login
     *
     * @return  \stubbles\webapp\auth\AuthConstraint
     */
    public function requireLogin()
    {
        $this->requiresLogin = true;
        return $this;
    }

    /**
     * forbid the actual login
     *
     * Forbidding a login means that the user receives a 403 Forbidden response
     * in case he accesses a restricted resource but is not logged in yet.
     * Otherwise, he would just be redirected to the login uri of the
     * authentication provider.
     *
     * @return  \stubbles\webapp\auth\AuthConstraint
     */
    public function forbiddenWhenNotAlreadyLoggedIn()
    {
        $this->loginAllowed = false;
        return $this;
    }

    /**
     * checks whether a login is allowed
     *
     * @return  bool
     */
    public function loginAllowed()
    {
        return $this->loginAllowed;
    }

    /**
     * checks whether login is required
     *
     * @return  bool
     */
    private function requiresLogin()
    {
        if ($this->requiresLogin) {
            return true;
        }

        $this->requiresLogin = $this->callbackAnnotatedWith->requiresLogin();
        return $this->requiresLogin;
    }

    /**
     * require a specific role
     *
     * @param   string  $role
     * @return  \stubbles\webapp\auth\AuthConstraint
     */
    public function requireRole($role)
    {
        $this->requiredRole = $role;
        return $this;
    }

    /**
     * checks whether auth is required
     *
     * @return  bool
     */
    public function requiresAuth()
    {
        return $this->requiresLogin() || $this->requiresRoles();
    }

    /**
     * checks if access to this route required authorization
     *
     * @return  bool
     */
    public function requiresRoles()
    {
        return (null !== $this->requiredRole()) || $this->callbackAnnotatedWith->rolesAware();
    }

    /**
     * returns required role for this route
     *
     * @return  string
     */
    private function requiredRole()
    {
        if (null === $this->requiredRole) {
            $this->requiredRole = $this->callbackAnnotatedWith->requiredRole();
        }

        return $this->requiredRole;
    }

    /**
     * checks whether route is satisfied by the given roles
     *
     * @param   \stubbles\webapp\auth\Roles  $roles
     * @return  bool
     */
    public function satisfiedByRoles(Roles $roles = null)
    {
        if (null === $roles) {
            return false;
        }

        if ($this->callbackAnnotatedWith->rolesAware()) {
            return true;
        }

        return $roles->contain($this->requiredRole());
    }
}
