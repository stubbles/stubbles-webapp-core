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
 * A token can be used to re-authorize a user on a later visit.
 *
 * @since  5.0.0
 */
class Token
{
    /**
     * actual token value
     *
     * @var  string|null
     */
    private $value;

    /**
     * constructor
     *
     * @param  string  $value
     */
    public function __construct(string $value = null)
    {
        $this->value = $value;
    }

    /**
     * creates token for given user
     *
     * @param   \stubbles\webapp\auth\User  $user  user to create token for
     * @param   string                      $salt  salt to use for token creation
     * @return  \stubbles\webapp\auth\Token
     */
    public static function create(User $user, string $salt): self
    {
        return new self(md5($salt . serialize([
                $user->name(),
                $user->firstName(),
                $user->lastName(),
                $user->mailAddress(),
                self::createRandomContent()
        ])));
    }

    /**
     * creates some random token content
     *
     * @return  string
     */
    private static function createRandomContent(): string
    {
        return uniqid('', true);
    }

    /**
     * check if token is empty
     *
     * @return  bool
     */
    public function isEmpty(): bool
    {
        return null == $this->value;
    }

    /**
     * returns string value
     *
     * @return  string
     */
    public function __toString(): string
    {
        return (string) $this->value;
    }
}
