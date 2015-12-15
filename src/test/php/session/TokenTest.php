<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  stubbles\webapp
 */
namespace stubbles\webapp\session;
use bovigo\callmap\NewInstance;
use stubbles\webapp\session\Session;

use function bovigo\callmap\onConsecutiveCalls;
use function bovigo\callmap\verify;
use function stubbles\lang\reflect\annotationsOf;
use function stubbles\lang\reflect\annotationsOfConstructor;
/**
 * Tests for stubbles\webapp\session\Token.
 *
 * @since  2.0.0
 * @group  session
 */
class TokenTest extends \PHPUnit_Framework_TestCase
{
    /**
     * instance to test
     *
     * @type  Token
     */
    private $token;
    /**
     * mocked session id
     *
     * @type  \bovigo\callmap\Proxy
     */
    private $session;

    /**
     * set up test enviroment
     */
    public function setUp()
    {
        $this->session = NewInstance::of(Session::class);
        $this->token   = new Token($this->session);
    }

    /**
     * @test
     */
    public function annotationsPresentOnClass()
    {
        assertTrue(annotationsOf($this->token)->contain('Singleton'));
    }

    /**
     * @test
     */
    public function annotationsPresentOnConstructor()
    {
        assertTrue(annotationsOfConstructor($this->token)->contain('Inject'));
    }

    /**
     * @test
     */
    public function givenTokenIsNotValidWhenNotEqualToSessionToken()
    {
        $this->session->mapCalls(['value' => 'aToken']);
        assertFalse($this->token->isValid('otherToken'));
    }

    /**
     * @test
     */
    public function givenTokenIsValidWhenEqualToSessionToken()
    {
        $this->session->mapCalls(['value' => 'aToken']);
        assertTrue($this->token->isValid('aToken'));
    }

    /**
     * @test
     */
    public function storesNextTokenInSessionWhenTokenIsValidated()
    {
        $this->session->mapCalls(['value' => 'aToken']);
        $this->token->isValid('otherToken');
        verify($this->session, 'putValue')->wasCalledOnce();
    }

    /**
     * @test
     */
    public function nextTokenTakenFromSession()
    {
        $this->session->mapCalls(
                ['value' => onConsecutiveCalls('aToken', 'nextToken')]
        );
        assertEquals('nextToken', $this->token->next());
    }

    /**
     * @test
     */
    public function nextStoresNextTokenInSession()
    {
        $this->token->next();
        verify($this->session, 'putValue')->wasCalledOnce();
    }
}
