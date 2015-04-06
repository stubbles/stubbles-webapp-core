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
     * @type  \PHPUnit_Framework_MockObject_MockObject
     */
    private $mockSession;

    /**
     * set up test enviroment
     */
    public function setUp()
    {
        $this->mockSession = $this->getMock('stubbles\webapp\session\Session');
        $this->token       = new Token($this->mockSession);
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
        $this->mockSession->expects($this->once())
                          ->method('value')
                          ->will($this->returnValue('aToken'));
        $this->mockSession->expects($this->once())
                          ->method('putValue');
        assertFalse($this->token->isValid('otherToken'));
    }

    /**
     * @test
     */
    public function givenTokenIsValidWhenEqualToSessionToken()
    {
        $this->mockSession->expects($this->once())
                          ->method('value')
                          ->will($this->returnValue('aToken'));
        $this->mockSession->expects($this->once())
                          ->method('putValue');
        assertTrue($this->token->isValid('aToken'));
    }

    /**
     * @test
     */
    public function nextTokenTakenFromSession()
    {
        $this->mockSession->expects($this->exactly(2))
                          ->method('value')
                          ->will($this->onConsecutiveCalls('aToken', 'nextToken'));
        $this->mockSession->expects($this->once())
                          ->method('putValue');
        assertEquals('nextToken', $this->token->next());
    }
}
