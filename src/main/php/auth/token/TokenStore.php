<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  stubbles\webapp
 */
namespace stubbles\webapp\auth\token;
use stubbles\input\web\WebRequest;
use stubbles\webapp\auth\Token;
use stubbles\webapp\auth\User;
/**
 * A token store holds tokens of users.
 *
 * It is responsible to persist them between requests, and to remove or
 * invalidate them.
 *
 * @since  5.0.0
 */
interface TokenStore
{
    /**
     * store token for given user
     *
     * @param  \stubbles\webapp\auth\Token  $token
     * @param  \stubbles\webapp\auth\User   $user
     */
    public function store(Token $token, User $user);

    /**
     * finds token for given user
     *
     * @param   \stubbles\webapp\auth\User  $user
     * @return  \stubbles\webapp\auth\Token
     */
    public function findTokenByUser(User $user);

    /**
     * checks if given token is known and valid
     *
     * @param   \stubbles\input\web\WebRequest  $request  request the token was provided with
     * @param   \stubbles\webapp\auth\Token     $token    actual token
     * @return  \stubbles\webapp\auth\User
     */
    public function findUserByToken(WebRequest $request, Token $token);
}
