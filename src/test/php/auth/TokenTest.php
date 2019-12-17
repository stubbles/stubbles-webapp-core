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
 * @group  auth
 */
class TokenTest extends TestCase
{
    /**
     * @test
     */
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

    /**
     * @return  mixed[]
     */
    public function tokenValues(): array
    {
        $tokenValues = $this->emptyValues();
        $tokenValues[] = ['someTokenValue'];
        return $tokenValues;
    }

    /**
     * @param  mixed  $tokenValue
     * @test
     * @dataProvider  tokenValues
     */
    public function tokenCanBeCastedToString($tokenValue): void
    {
        $token = new Token($tokenValue);
        assertThat((string) $token, equals($tokenValue));
    }

    /**
     * @return  array<mixed[]>
     */
    public function emptyValues(): array
    {
        return [[null], ['']];
    }

    /**
     * @param  mixed  $emptyValue
     * @test
     * @dataProvider  emptyValues
     */
    public function tokenIsEmptyWhenValueIsEmpty($emptyValue): void
    {
        $token = new Token($emptyValue);
        assertTrue($token->isEmpty());
    }

    /**
     * @test
     */
    public function tokenIsNotEmptyWhenValueNotEmpty(): void
    {
        $token = new Token('someTokenValue');
        assertFalse($token->isEmpty());
    }
}
