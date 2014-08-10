<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  stubbles\webapp
 */
namespace stubbles\webapp\auth\token;
use stubbles\input\ValueReader;
use stubbles\lang;
use stubbles\webapp\auth\Token;
/**
 * Test for stubbles\webapp\auth\token\TokenAuthenticator.
 *
 * @group  stubbles
 * @group  token
 * @since  2.1.0
 */
class TokenAuthenticatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * instance to test
     *
     * @type  \stubbles\webapp\auth\token\TokenAuthenticator
     */
    private $tokenAuthenticator;
    /**
     * mocked token store
     *
     * @type  \PHPUnit_Framework_MockObject_MockObject
     */
    private $mockTokenStore;
    /**
     * mocked login provider
     *
     * @type  \PHPUnit_Framework_MockObject_MockObject
     */
    private $mockLoginProvider;
    /**
     * mocked request
     *
     * @type  \PHPUnit_Framework_MockObject_MockObject
     */
    private $mockRequest;

    /**
     * set up test environment
     */
    public function setUp()
    {
        $this->mockTokenStore     = $this->getMock('stubbles\webapp\auth\token\TokenStore');
        $this->mockLoginProvider  = $this->getMock('stubbles\webapp\auth\AuthenticationProvider');
        $this->tokenAuthenticator = new TokenAuthenticator(
                $this->mockTokenStore,
                'some salt',
                $this->mockLoginProvider
        );
        $this->mockRequest = $this->getMock('stubbles\input\web\WebRequest');
    }

    /**
     * @test
     */
    public function annotationsPresentOnConstructor()
    {
        $constructor = lang\reflectConstructor($this->tokenAuthenticator);
        $this->assertTrue($constructor->hasAnnotation('Inject'));

        $parameters = $constructor->getParameters();
        $this->assertTrue($parameters[1]->hasAnnotation('Property'));
        $this->assertEquals(
                'stubbles.webapp.auth.token.salt',
                $parameters[1]->getAnnotation('Property')->getName()
        );

        $this->assertTrue($parameters[2]->hasAnnotation('Named'));
        $this->assertEquals(
                'stubbles.webapp.auth.token.loginProvider',
                $parameters[2]->getAnnotation('Named')->getName()
        );
    }

    /**
     * @test
     */
    public function delegatesAuthenticationToLoginProviderIfNoTokenInRequest()
    {
        $this->mockRequest->expects($this->once())
                          ->method('hasRedirectHeader')
                          ->will($this->returnValue(false));
        $this->mockLoginProvider->expects($this->once())
                                ->method('authenticate')
                                ->will($this->returnValue(null));
        $this->assertNull($this->tokenAuthenticator->authenticate($this->mockRequest));
    }

    /**
     * @test
     */
    public function createsAndStoresTokenFromUserReturnedByLoginProvider()
    {
        $user = $this->getMock('stubbles\webapp\auth\User');
        $this->mockRequest->expects($this->any())
                          ->method('hasHeader')
                          ->will($this->returnValue(false));
        $this->mockLoginProvider->expects($this->once())
                                ->method('authenticate')
                                ->will($this->returnValue($user));
        $this->mockTokenStore->expects($this->once())
                             ->method('store')
                             ->with(
                                     $this->equalTo($this->mockRequest),
                                     $this->isInstanceOf('stubbles\webapp\auth\Token'),
                                     $this->equalTo($user)
                               );
        $this->assertSame(
                $user,
                $this->tokenAuthenticator->authenticate($this->mockRequest)
        );
    }

    /**
     * @test
     */
    public function returnsNullWhenAuthorizationHeaderIsSetButEmpty()
    {
        $this->mockRequest->expects($this->once())
                          ->method('hasRedirectHeader')
                          ->will($this->returnValue(true));
        $this->mockRequest->expects($this->once())
                          ->method('readRedirectHeader')
                          ->will($this->returnValue(ValueReader::forValue('')));
        $this->mockTokenStore->expects($this->never())
                             ->method('findUserByToken');
        $this->assertNull($this->tokenAuthenticator->authenticate($this->mockRequest));
    }

    /**
     * @return  array
     */
    public function validTokens()
    {
        return [['Bearer 123456789012345678901234567890ab', '123456789012345678901234567890ab'],
                ['someOtherToken', 'someOtherToken']
        ];
    }

    /**
     * @test
     * @dataProvider  validTokens
     */
    public function returnsUserWhenAuthorizationHeaderContainsValidToken($headerValue, $tokenValue)
    {
        $this->mockRequest->expects($this->once())
                          ->method('hasRedirectHeader')
                          ->will($this->returnValue(true));
        $this->mockRequest->expects($this->once())
                          ->method('readRedirectHeader')
                          ->will($this->returnValue(ValueReader::forValue($headerValue)));
        $user = $this->getMock('stubbles\webapp\auth\User');
        $this->mockTokenStore->expects($this->once())
                             ->method('findUserByToken')
                             ->with(
                                     $this->equalTo($this->mockRequest),
                                     $this->equalTo(new Token($tokenValue))
                               )
                             ->will($this->returnValue($user));
        $this->assertSame($user, $this->tokenAuthenticator->authenticate($this->mockRequest));
    }
}
