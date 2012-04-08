<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  net\stubbles\webapp
 */
namespace net\stubbles\webapp\session;
/**
 * Tests for net\stubbles\webapp\session\Token.
 *
 * @since  2.0.0
 * @group  session
 */
class TokenTestCase extends \PHPUnit_Framework_TestCase
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
        $this->mockSession = $this->getMock('net\\stubbles\\webapp\\session\\Session');
        $this->token       = new Token($this->mockSession);
    }

    /**
     * @test
     */
    public function annotationsPresentOnClass()
    {
        $this->assertTrue($this->token->getClass()->hasAnnotation('Singleton'));
    }

    /**
     * @test
     */
    public function annotationsPresentOnConstructor()
    {
        $this->assertTrue($this->token->getClass()
                                      ->getConstructor()
                                      ->hasAnnotation('Inject')
        );
    }

    /**
     * @test
     */
    public function givenTokenIsNotValidWhenNotEqualToSessionToken()
    {
        $this->mockSession->expects($this->once())
                          ->method('getValue')
                          ->will($this->returnValue('aToken'));
        $this->mockSession->expects($this->once())
                          ->method('putValue');
        $this->assertFalse($this->token->isValid('otherToken'));
    }

    /**
     * @test
     */
    public function givenTokenIsValidWhenEqualToSessionToken()
    {
        $this->mockSession->expects($this->once())
                          ->method('getValue')
                          ->will($this->returnValue('aToken'));
        $this->mockSession->expects($this->once())
                          ->method('putValue');
        $this->assertTrue($this->token->isValid('aToken'));
    }

    /**
     * @test
     */
    public function nextTokenTakenFromSession()
    {
        $this->mockSession->expects($this->exactly(2))
                          ->method('getValue')
                          ->will($this->onConsecutiveCalls('aToken', 'nextToken'));
        $this->mockSession->expects($this->once())
                          ->method('putValue');
        $this->assertEquals('nextToken', $this->token->next());
    }
}
?>