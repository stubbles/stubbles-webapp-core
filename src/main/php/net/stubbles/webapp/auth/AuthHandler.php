<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  net\stubbles\webapp
 */
namespace net\stubbles\webapp\auth;
use net\stubbles\lang\Object;
/**
 * Interface for authentication handlers.
 *
 * @api
 */
interface AuthHandler extends Object
{
    /**
     * checks if given role required login
     *
     * @param   string  $role
     * @return  bool
     */
    public function requiresLogin($role);

    /**
     * returns login uri
     *
     * @return  string
     */
    public function getLoginUri();

    /**
     * checks whether the auth handler has a user
     *
     * @return  bool
     */
    public function hasUser();

    /**
     * checks if user has a specific role
     *
     * @param   string  $role
     * @return  bool
     */
    public function userHasRole($role);
}
?>