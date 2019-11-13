<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace stubbles\webapp\auth;
/**
 * Represents an authenticated and authorized identity which issued the request.
 *
 * @since  6.0.0
 */
class Identity
{
    /**
     * @type  \stubbles\webapp\auth\User
     */
    private $user;
    /**
     * @type  \stubbles\webapp\auth\Roles
     */
    private $roles;

    /**
     * constructor
     *
     * @param  \stubbles\webapp\auth\User   $user   user who is associated with the identity
     * @param  \stubbles\webapp\auth\Roles  $roles  list of roles the identity has
     */
    public function __construct(User $user, Roles $roles)
    {
        $this->user  = $user;
        $this->roles = $roles;
    }

    /**
     * returns user who is associated with the identity
     *
     * @return  \stubbles\webapp\auth\User
     */
    public function user(): User
    {
        return $this->user;
    }

    /**
     * checks if the identity has the given role
     *
     * @param   string  $roleName
     * @return  bool
     */
    public function hasRole(string $roleName): bool
    {
        return $this->roles->contain($roleName);
    }

    /**
     * returns roles of the identity
     *
     * @return  \stubbles\webapp\auth\Roles
     */
    public function roles(): Roles
    {
        return $this->roles;
    }
}
