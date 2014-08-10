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
 * @ProvidedBy(stubbles\webapp\auth\ioc\UserProvider.class)
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

    /**
     * sets token for the user
     *
     * @param   \stubbles\webapp\auth\Token  $token
     * @return  \stubbles\webapp\auth\User
     */
    public function setToken(Token $token);

    /**
     * creates new token for the user with given token salt
     *
     * The token is already stored in the user afterwards, any further request
     * to token() will yield the same token.
     *
     * @param  string  $tokenSalt
     * @return  \stubbles\webapp\auth\Token
     */
    public function createToken($tokenSalt);

    /**
     * returns token for the user
     *
     * @return  \stubbles\webapp\auth\Token
     */
    public function token();
}
