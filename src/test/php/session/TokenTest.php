<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace stubbles\webapp\session;

use bovigo\callmap\ClassProxy;
use bovigo\callmap\NewInstance;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use stubbles\webapp\session\Session;

use function bovigo\assert\assertThat;
use function bovigo\assert\assertFalse;
use function bovigo\assert\assertTrue;
use function bovigo\assert\predicate\equals;
use function bovigo\callmap\{onConsecutiveCalls, verify};
use function stubbles\reflect\{annotationsOf, annotationsOfConstructor};
/**
 * Tests for stubbles\webapp\session\Token.
 *
 * @since  2.0.0
 */
#[Group('session')]
class TokenTest extends TestCase
{
    private Token $token;
    private Session&ClassProxy $session;

    protected function setUp(): void
    {
        $this->session = NewInstance::of(Session::class);
        $this->token   = new Token($this->session);
    }

    #[Test]
    public function annotationsPresentOnClass(): void
    {
        assertTrue(annotationsOf($this->token)->contain('Singleton'));
    }

    #[Test]
    public function annotationsPresentOnConstructor(): void
    {
        assertTrue(annotationsOfConstructor($this->token)->contain('Inject'));
    }

    #[Test]
    public function givenTokenIsNotValidWhenNotEqualToSessionToken(): void
    {
        $this->session->returns(['value' => 'aToken']);
        assertFalse($this->token->isValid('otherToken'));
    }

    #[Test]
    public function givenTokenIsValidWhenEqualToSessionToken(): void
    {
        $this->session->returns(['value' => 'aToken']);
        assertTrue($this->token->isValid('aToken'));
    }

    #[Test]
    public function storesNextTokenInSessionWhenTokenIsValidated(): void
    {
        $this->session->returns(['value' => 'aToken']);
        $this->token->isValid('otherToken');
        assertTrue(verify($this->session, 'putValue')->wasCalledOnce());
    }

    #[Test]
    public function nextTokenTakenFromSession(): void
    {
        $this->session->returns(
            ['value' => onConsecutiveCalls('aToken', 'nextToken')]
        );
        assertThat($this->token->next(), equals('nextToken'));
    }

    #[Test]
    public function nextStoresNextTokenInSession(): void
    {
        $this->session->returns(['value' => 'nextToken']);
        $this->token->next();
        assertTrue(verify($this->session, 'putValue')->wasCalledOnce());
    }
}
