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
/**
 * An authorization provder delivers a list of roles a user has.
 *
 * The provider can either use the request or a user information or both to
 * determine the roles. If it can not find any roles it should return an empty
 * role list via Roles::none().
 *
 * @since  5.0.0
 */
interface AuthorizationProvider
{
    /**
     * returns the roles available for this request and user
     *
     * @param   \stubbles\webapp\auth\User  $user
     * @return  \stubbles\webapp\auth\Roles
     */
    public function roles(User $user);
}
