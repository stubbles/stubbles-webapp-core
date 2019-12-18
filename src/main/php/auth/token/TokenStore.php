<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace stubbles\webapp\auth\token;
use stubbles\webapp\Request;
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
     * @param  \stubbles\webapp\Request     $request  request the token was issued with
     * @param  \stubbles\webapp\auth\Token  $token    actual token
     * @param  \stubbles\webapp\auth\User   $user     user the the token is for
     * @return  void  not in code as bovigo/callmap doesn't support throwing from methods declared as returning void yet
     */
    public function store(Request $request, Token $token, User $user);

    /**
     * returns the user for the given token if it is valid
     *
     * @param   \stubbles\webapp\Request     $request  request the token was provided with
     * @param   \stubbles\webapp\auth\Token  $token    actual token
     * @return  \stubbles\webapp\auth\User
     */
    public function findUserByToken(Request $request, Token $token): ?User;
}
