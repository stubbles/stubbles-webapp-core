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
use bovigo\callmap;
use bovigo\callmap\NewInstance;
use stubbles\lang\reflect;
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
        $this->session = NewInstance::of('stubbles\webapp\session\Session');
        $this->token   = new Token($this->session);
    }

    /**
     * @test
     */
    public function annotationsPresentOnClass()
    {
        assertTrue(
                reflect\annotationsOf($this->token)
                        ->contain('Singleton')
        );
    }

    /**
     * @test
     */
    public function annotationsPresentOnConstructor()
    {
        assertTrue(
                reflect\annotationsOfConstructor($this->token)
                        ->contain('Inject')
        );
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
        callmap\verify($this->session, 'putValue')->wasCalledOnce();
    }

    /**
     * @test
     */
    public function nextTokenTakenFromSession()
    {
        $this->session->mapCalls(
                ['value' => callmap\onConsecutiveCalls('aToken', 'nextToken')]
        );
        assertEquals('nextToken', $this->token->next());
    }

    /**
     * @test
     */
    public function nextStoresNextTokenInSession()
    {
        $this->token->next();
        callmap\verify($this->session, 'putValue')->wasCalledOnce();
    }
}
