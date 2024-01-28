<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace stubbles\webapp\auth;
use bovigo\callmap\NewInstance;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function bovigo\assert\{
    assertThat,
    assertFalse,
    assertTrue,
    predicate\equals,
    predicate\isInstanceOf
};
/**
 * Test for stubbles\webapp\auth\Token.
 *
 * @since  5.0.0
 */
#[Group('auth')]
class TokenTest extends TestCase
{
    #[Test]
    public function canCreateTokenFromUser(): void
    {
        assertThat(
            Token::create(
                NewInstance::of(User::class)->returns([
                    'name'        => 'Heinz Mustermann',
                    'firstName'   => 'Heinz',
                    'lastName'    => 'Mustermann',
                    'mailAddress' => 'mm@example.com'
                ]),
                'some caramel salt'
            ),
            isInstanceOf(Token::class)
        );
    }

    public static function tokenValues(): Generator
    {
        yield from self::emptyValues();
        yield ['someTokenValue'];
    }

    #[Test]
    #[DataProvider('tokenValues')]
    public function tokenCanBeCastedToString(?string $tokenValue): void
    {
        $token = new Token($tokenValue);
        assertThat((string) $token, equals($tokenValue));
    }

    public static function emptyValues(): Generator
    {
        yield [null];
        yield [''];
    }

    #[Test]
    #[DataProvider('emptyValues')]
    public function tokenIsEmptyWhenValueIsEmpty(?string $emptyValue): void
    {
        $token = new Token($emptyValue);
        assertTrue($token->isEmpty());
    }

    #[Test]
    public function tokenIsNotEmptyWhenValueNotEmpty(): void
    {
        $token = new Token('someTokenValue');
        assertFalse($token->isEmpty());
    }
}
