<?php
declare(strict_types=1);
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
 * Basic implementation of a user which holds a token.
 *
 * @since  5.0.0
 */
abstract class TokenAwareUser implements User
{
    /**
     * the token
     *
     * @type  \stubbles\webapp\auth\Token
     */
    private $token;

    /**
     * sets token for the user
     *
     * @param   \stubbles\webapp\auth\Token  $token
     * @return  \stubbles\webapp\auth\User
     */
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
     *
     * @param   string  $tokenSalt
     * @return  \stubbles\webapp\auth\Token
     */
    public function createToken(string $tokenSalt): Token
    {
        $this->setToken(Token::create($this, $tokenSalt));
        return $this->token();
    }

    /**
     * returns token for the user
     *
     * @return  \stubbles\webapp\auth\Token|null
     */
    public function token()
    {
        return $this->token;
    }
}
