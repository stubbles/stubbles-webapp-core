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
     */
    public function store(Request $request, Token $token, User $user);

    /**
     * returns the user for the given token if it is valid
     */
    public function findUserByToken(Request $request, Token $token): ?User;
}
