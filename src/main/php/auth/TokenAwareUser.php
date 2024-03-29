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
 * Basic implementation of a user which holds a token.
 *
 * @since  5.0.0
 */
abstract class TokenAwareUser implements User
{
    private ?Token $token = null;

    public function setToken(Token $token): User
    {
        $this->token = $token;
        return $this;
    }

    /**
     * creates new token for the user with given token salt
     *
     * The token is already stored in the user afterwards, any further request
     * to token() will yield the same token.
     */
    public function createToken(string $tokenSalt): Token
    {
        $token = Token::create($this, $tokenSalt);
        $this->setToken($token);
        return $token;
    }

    public function token(): ?Token
    {
        return $this->token;
    }
}
