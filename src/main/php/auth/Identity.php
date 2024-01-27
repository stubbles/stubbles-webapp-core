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
 * An identity combines the user and the roles of this user.
 *
 * @since  6.0.0
 */
class Identity
{
    public function __construct(private User $user, private Roles $roles) { }

    /**
     * returns user who is associated with the identity
     */
    public function user(): User
    {
        return $this->user;
    }

    /**
     * checks if the identity has the given role
     */
    public function hasRole(string $roleName): bool
    {
        return $this->roles->contain($roleName);
    }

    /**
     * returns roles of the identity
     */
    public function roles(): Roles
    {
        return $this->roles;
    }
}
