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
     */
    public function name(): string;

    /**
     * first name of the user
     */
    public function firstName(): string;

    /**
     * last name of the user
     */
    public function lastName(): string;

    /**
     * mail address of the user
     */
    public function mailAddress(): string;

    /**
     * sets token for the user
     */
    public function setToken(Token $token): self;

    /**
     * creates new token for the user with given token salt
     *
     * The token is already stored in the user afterwards, any further request
     * to token() will yield the same token.
     */
    public function createToken(string $tokenSalt): Token;

    /**
     * returns token for the user
     */
    public function token(): ?Token;
}
