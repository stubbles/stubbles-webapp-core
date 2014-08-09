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
 * Represents information about an authenticated user.
 *
 * @since  5.0.0
 */
interface User
{
    /**
     * session key under which instance is stored within the session
     */
    const SESSION_KEY = 'stubbles.webapp.auth.user';

    /**
     * name of the user, should be unique
     *
     * @return  string
     */
    public function name();

    /**
     * first name of the user
     *
     * @return  string
     */
    public function firstName();

    /**
     * last name of the user
     *
     * @return  string
     */
    public function lastName();

    /**
     * mail address of the user
     *
     * @return  string
     */
    public function mailAddress();
}
