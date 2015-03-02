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
use stubbles\lang\reflect;
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
        $this->assertTrue(
                reflect\annotationsOfConstructor($this->tokenAuthenticator)
                        ->contain('Inject')
        );

        $tokenSaltParamAnnotations = reflect\annotationsOfConstructorParameter(
                'tokenSalt',
                $this->tokenAuthenticator
        );
        $this->assertTrue($tokenSaltParamAnnotations->contain('Property'));
        $this->assertEquals(
                'stubbles.webapp.auth.token.salt',
                $tokenSaltParamAnnotations->firstNamed('Property')->getName()
        );

        $loginProviderParamAnnotations = reflect\annotationsOfConstructorParameter(
                'loginProvider',
                $this->tokenAuthenticator
        );
        $this->assertTrue($loginProviderParamAnnotations->contain('Named'));
        $this->assertEquals(
                'stubbles.webapp.auth.token.loginProvider',
                $loginProviderParamAnnotations->firstNamed('Named')->getName()
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
    public function delegatesAuthenticationToLoginProviderIfAuthorizationHeaderIsSetButEmpty()
    {
        $this->mockRequest->expects($this->once())
                          ->method('hasRedirectHeader')
                          ->will($this->returnValue(true));
        $this->mockRequest->expects($this->once())
                          ->method('readRedirectHeader')
                          ->will($this->returnValue(ValueReader::forValue('')));
        $this->mockLoginProvider->expects($this->once())
                                ->method('authenticate')
                                ->will($this->returnValue(null));
        $this->assertNull($this->tokenAuthenticator->authenticate($this->mockRequest));
    }

    /**
     * @return  \PHPUnit_Framework_MockObject_MockObject
     */
    private function mockTokenAwareUser()
    {
        return $this->getMock(
                'stubbles\webapp\auth\TokenAwareUser',
                ['name', 'firstName', 'lastName', 'mailAddress']
        );
    }

    /**
     * @test
     * @expectedException  stubbles\webapp\auth\InternalAuthProviderException
     * @expectedExceptionMessage  Error while trying to store new token for user: failure
     */
    public function throwsInternalAuthProviderExceptionWhenTokenStoreThrowsExceptionWhileStoringToken()
    {
        $user = $this->mockTokenAwareUser();
        $this->mockRequest->expects($this->any())
                          ->method('hasHeader')
                          ->will($this->returnValue(false));
        $this->mockLoginProvider->expects($this->once())
                                ->method('authenticate')
                                ->will($this->returnValue($user));
        $this->mockTokenStore->expects($this->once())
                             ->method('store')
                             ->will($this->throwException(new \Exception('failure')));
        $this->tokenAuthenticator->authenticate($this->mockRequest);
    }

    /**
     * @test
     */
    public function createsAndStoresTokenFromUserReturnedByLoginProvider()
    {
        $user = $this->mockTokenAwareUser();
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
    public function userReturnedByLoginProviderHasToken()
    {
        $user = $this->mockTokenAwareUser();
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
        $this->assertNotNull(
                $this->tokenAuthenticator->authenticate($this->mockRequest)->token()
        );
    }

    /**
     * @test
     * @expectedException  stubbles\webapp\auth\InternalAuthProviderException
     * @expectedExceptionMessage  Error while trying to find user by token: failure
     */
    public function throwsInternalAuthProviderExceptionWhenTokenStoreThrowsExceptionWhileFindingUser()
    {
        $this->mockRequest->expects($this->once())
                          ->method('hasRedirectHeader')
                          ->will($this->returnValue(true));
        $this->mockRequest->expects($this->once())
                          ->method('readRedirectHeader')
                          ->will($this->returnValue(ValueReader::forValue('someToken')));
        $this->mockTokenStore->expects($this->once())
                             ->method('findUserByToken')
                             ->will($this->throwException(new \Exception('failure')));
        $this->tokenAuthenticator->authenticate($this->mockRequest);
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
        $user = $this->mockTokenAwareUser();
        $this->mockTokenStore->expects($this->once())
                             ->method('findUserByToken')
                             ->with(
                                     $this->equalTo($this->mockRequest),
                                     $this->equalTo(new Token($tokenValue))
                               )
                             ->will($this->returnValue($user));
        $this->assertSame($user, $this->tokenAuthenticator->authenticate($this->mockRequest));
    }

    /**
     * @test
     * @dataProvider  validTokens
     */
    public function returnedUserFromValidTokenHasToken($headerValue, $tokenValue)
    {
        $this->mockRequest->expects($this->once())
                          ->method('hasRedirectHeader')
                          ->will($this->returnValue(true));
        $this->mockRequest->expects($this->once())
                          ->method('readRedirectHeader')
                          ->will($this->returnValue(ValueReader::forValue($headerValue)));
        $user  = $this->mockTokenAwareUser();
        $token = new Token($tokenValue);
        $this->mockTokenStore->expects($this->once())
                             ->method('findUserByToken')
                             ->with(
                                     $this->equalTo($this->mockRequest),
                                     $this->equalTo($token)
                               )
                             ->will($this->returnValue($user));
        $this->assertEquals(
                $token,
                $this->tokenAuthenticator->authenticate($this->mockRequest)->token()
        );
    }

    /**
     * @test
     */
    public function delegatesAuthenticationToLoginProviderIfTokenFromAuthorizationHeaderDoesNotYieldUser()
    {
        $this->mockRequest->expects($this->once())
                          ->method('hasRedirectHeader')
                          ->will($this->returnValue(true));
        $this->mockRequest->expects($this->once())
                          ->method('readRedirectHeader')
                          ->will($this->returnValue(ValueReader::forValue('someOtherToken')));
        $token = new Token('someOtherToken');
        $this->mockTokenStore->expects($this->once())
                             ->method('findUserByToken')
                             ->with(
                                     $this->equalTo($this->mockRequest),
                                     $this->equalTo($token)
                               )
                             ->will($this->returnValue(null));
        $this->mockLoginProvider->expects($this->once())
                                ->method('authenticate')
                                ->will($this->returnValue(null));
        $this->assertNull($this->tokenAuthenticator->authenticate($this->mockRequest));
    }

    /**
     * @test
     */
    public function createAndStoresNewTokenIfTokenFromAuthorizationHeaderDoesNotYieldUser()
    {
        $this->mockRequest->expects($this->once())
                          ->method('hasRedirectHeader')
                          ->will($this->returnValue(true));
        $this->mockRequest->expects($this->once())
                          ->method('readRedirectHeader')
                          ->will($this->returnValue(ValueReader::forValue('someOtherToken')));
        $token = new Token('someOtherToken');
        $this->mockTokenStore->expects($this->once())
                             ->method('findUserByToken')
                             ->with(
                                     $this->equalTo($this->mockRequest),
                                     $this->equalTo($token)
                               )
                             ->will($this->returnValue(null));
        $user  = $this->mockTokenAwareUser();
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
    public function userReturnedAfterTokenRecreationHasTokenIfTokenFromAuthorizationHeaderDoesNotYieldUser()
    {
        $this->mockRequest->expects($this->once())
                          ->method('hasRedirectHeader')
                          ->will($this->returnValue(true));
        $this->mockRequest->expects($this->once())
                          ->method('readRedirectHeader')
                          ->will($this->returnValue(ValueReader::forValue('someOtherToken')));
        $token = new Token('someOtherToken');
        $this->mockTokenStore->expects($this->once())
                             ->method('findUserByToken')
                             ->with(
                                     $this->equalTo($this->mockRequest),
                                     $this->equalTo($token)
                               )
                             ->will($this->returnValue(null));
        $user  = $this->mockTokenAwareUser();
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
        $this->assertNotNull(
                $this->tokenAuthenticator->authenticate($this->mockRequest)->token()
        );
    }

    /**
     * @test
     */
    public function userReturnedAfterTokenRecreationHasDifferentTokenIfTokenFromAuthorizationHeaderDoesNotYieldUser()
    {
        $this->mockRequest->expects($this->once())
                          ->method('hasRedirectHeader')
                          ->will($this->returnValue(true));
        $this->mockRequest->expects($this->once())
                          ->method('readRedirectHeader')
                          ->will($this->returnValue(ValueReader::forValue('someOtherToken')));
        $token = new Token('someOtherToken');
        $this->mockTokenStore->expects($this->once())
                             ->method('findUserByToken')
                             ->with(
                                     $this->equalTo($this->mockRequest),
                                     $this->equalTo($token)
                               )
                             ->will($this->returnValue(null));
        $user  = $this->mockTokenAwareUser();
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
        $this->assertNotEquals(
                $token,
                $this->tokenAuthenticator->authenticate($this->mockRequest)->token()
        );
    }
}
