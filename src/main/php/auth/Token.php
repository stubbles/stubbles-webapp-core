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
    public function __construct(private ?string $value = null) { }

    /**
     * creates token for given user
     *
     * @param  User    $user  user to create token for
     * @param  string  $salt  salt to use for token creation
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

    private static function createRandomContent(): string
    {
        return uniqid('', true);
    }

    public function isEmpty(): bool
    {
        return null == $this->value;
    }

    public function __toString(): string
    {
        return (string) $this->value;
    }
}
