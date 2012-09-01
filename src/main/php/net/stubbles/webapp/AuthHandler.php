<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  net\stubbles\webapp
 */
namespace net\stubbles\webapp;
use net\stubbles\lang\Object;
/**
 * Interface for authentication handlers.
 *
 * @api
 */
interface AuthHandler extends Object
{
    /**
     * checks whether expected role is given
     *
     * @param   string  $expectedRole
     * @return  bool
     */
    public function isAuthorized($expectedRole);

    /**
     * checks whether role requires login
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
}
?>