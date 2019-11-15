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
 * An authorization provder delivers a list of roles a user has.
 *
 * @since  5.0.0
 */
interface AuthorizationProvider
{
    /**
     * returns the roles available for this request and user
     *
     * The provider should determine the roles based on the user information. If
     * it can not find any roles it should return an empty role list via
     * Roles::none(). In case it can not find any roles because it stumbles
     * about an error it can not resolve it should throw an
     * stubbles\webapp\auth\AuthProviderException.
     *
     * @param   \stubbles\webapp\auth\User  $user
     * @return  \stubbles\webapp\auth\Roles|null
     * @throws  \stubbles\webapp\auth\AuthProviderException
     */
    public function roles(User $user): ?Roles;
}
