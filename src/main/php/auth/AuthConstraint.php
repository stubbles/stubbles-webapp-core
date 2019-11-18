<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace stubbles\webapp\auth;
use stubbles\webapp\routing\RoutingAnnotations;
/**
 * Contains auth informations about a route.
 *
 * @since  5.0.0
 * @XmlTag(tagName='auth')
 */
class AuthConstraint implements \JsonSerializable
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
     * @XmlIgnore
     */
    public function requireLogin(): self
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
     * @XmlIgnore
     */
    public function forbiddenWhenNotAlreadyLoggedIn(): self
    {
        $this->loginAllowed = false;
        return $this;
    }

    /**
     * checks whether a login is allowed
     *
     * @return  bool
     * @XmlIgnore
     */
    public function loginAllowed(): bool
    {
        return $this->loginAllowed;
    }

    /**
     * checks whether login is required
     *
     * @return  bool
     * @XmlAttribute(attributeName='requiresLogin')
     */
    private function requiresLogin(): bool
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
    public function requireRole(string $role): self
    {
        $this->requiredRole = $role;
        return $this;
    }

    /**
     * checks whether auth is required
     *
     * @return  bool
     * @XmlAttribute(attributeName='required')
     */
    public function requiresAuth(): bool
    {
        return $this->requiresLogin() || $this->requiresRoles();
    }

    /**
     * checks if access to this route required authorization
     *
     * @return  bool
     * @XmlAttribute(attributeName='requiresRoles')
     */
    public function requiresRoles(): bool
    {
        return (null !== $this->requiredRole()) || $this->callbackAnnotatedWith->rolesAware();
    }

    /**
     * returns required role for this route
     *
     * @return  string|null
     * @XmlAttribute(attributeName='role', skipEmpty=true)
     */
    public function requiredRole(): ?string
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
    public function satisfiedByRoles(Roles $roles = null): bool
    {
        if (null === $roles) {
            return false;
        }

        if ($this->callbackAnnotatedWith->rolesAware()) {
            return true;
        }

        $requiredRole = $this->requiredRole();
        if (null === $requiredRole) {
            throw new \LogicException('Route says it requires a role but doesn\'t specify which.');
        }

        return $roles->contain($requiredRole);
    }

    /**
     * returns data suitable for encoding to JSON
     *
     * @return  array
     * @since   6.1.0
     * @XmlIgnore
     */
    public function jsonSerialize(): array
    {
        $data = ['required' => $this->requiresAuth()];
        if ($this->requiresAuth()) {
            $data['requiresLogin'] = $this->requiresLogin();
        }

        if ($this->requiresRoles()) {
            $data['requiresRoles'] = true;
            $data['requiredRole']  = $this->requiredRole();
        }

        return $data;
    }
}
