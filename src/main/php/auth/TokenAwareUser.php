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
    public function setToken(Token $token)
    {
        $this->token = $token;
        return $this;
    }

    /**
     * returns token for the user
     *
     * @return  \stubbles\webapp\auth\Token
     */
    public function token()
    {
        return $this->token;
    }
}
