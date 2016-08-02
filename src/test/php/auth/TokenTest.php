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
use bovigo\callmap\NewInstance;

use function bovigo\assert\assert;
use function bovigo\assert\assertFalse;
use function bovigo\assert\assertTrue;
use function bovigo\assert\predicate\equals;
use function bovigo\assert\predicate\isInstanceOf;
/**
 * Test for stubbles\webapp\auth\Token.
 *
 * @since  5.0.0
 * @group  auth
 */
class TokenTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function canCreateTokenFromUser()
    {
        assert(
                Token::create(
                        NewInstance::of(User::class)->mapCalls([
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

    public function tokenValues(): array
    {
        $tokenValues = $this->emptyValues();
        $tokenValues[] = ['someTokenValue'];
        return $tokenValues;
    }

    /**
     * @param  string  $tokenValue
     * @test
     * @dataProvider  tokenValues
     */
    public function tokenCanBeCastedToString($tokenValue)
    {
        $token = new Token($tokenValue);
        assert((string) $token, equals($tokenValue));
    }

    public function emptyValues(): array
    {
        return [[null], ['']];
    }

    /**
     * @param  string  $emptyValue
     * @test
     * @dataProvider  emptyValues
     */
    public function tokenIsEmptyWhenValueIsEmpty($emptyValue)
    {
        $token = new Token($emptyValue);
        assertTrue($token->isEmpty());
    }

    /**
     * @test
     */
    public function tokenIsNotEmptyWhenValueNotEmpty()
    {
        $token = new Token('someTokenValue');
        assertFalse($token->isEmpty());
    }
}
