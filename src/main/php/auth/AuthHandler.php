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
 * Interface for authentication/authorization handlers.
 *
 * @api
 */
interface AuthHandler
{
    /**
     * checks whether authentication is given
     *
     * @return  bool
     */
    public function isAuthenticated();

    /**
     * checks whether expected role is given
     *
     * @param   string  $expectedRole
     * @return  bool
     */
    public function isAuthorized($expectedRole);

    /**
     * returns login uri
     *
     * @return  string
     */
    public function getLoginUri();
}
