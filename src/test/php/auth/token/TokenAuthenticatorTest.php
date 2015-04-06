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
    private $tokenStore;
    /**
     * mocked login provider
     *
     * @type  \PHPUnit_Framework_MockObject_MockObject
     */
    private $loginProvider;
    /**
     * mocked request
     *
     * @type  \PHPUnit_Framework_MockObject_MockObject
     */
    private $request;

    /**
     * set up test environment
     */
    public function setUp()
    {
        $this->tokenStore     = $this->getMock('stubbles\webapp\auth\token\TokenStore');
        $this->loginProvider  = $this->getMock('stubbles\webapp\auth\AuthenticationProvider');
        $this->tokenAuthenticator = new TokenAuthenticator(
                $this->tokenStore,
                'some salt',
                $this->loginProvider
        );
        $this->request  = $this->getMock('stubbles\webapp\Request');
    }

    /**
     * @test
     */
    public function annotationsPresentOnConstructor()
    {
        assertTrue(
                reflect\annotationsOfConstructor($this->tokenAuthenticator)
                        ->contain('Inject')
        );

        $tokenSaltParamAnnotations = reflect\annotationsOfConstructorParameter(
                'tokenSalt',
                $this->tokenAuthenticator
        );
        assertTrue($tokenSaltParamAnnotations->contain('Property'));
        assertEquals(
                'stubbles.webapp.auth.token.salt',
                $tokenSaltParamAnnotations->firstNamed('Property')->getName()
        );

        $loginProviderParamAnnotations = reflect\annotationsOfConstructorParameter(
                'loginProvider',
                $this->tokenAuthenticator
        );
        assertTrue($loginProviderParamAnnotations->contain('Named'));
        assertEquals(
                'stubbles.webapp.auth.token.loginProvider',
                $loginProviderParamAnnotations->firstNamed('Named')->getName()
        );
    }

    /**
     * @test
     */
    public function delegatesAuthenticationToLoginProviderIfNoTokenInRequest()
    {
        $this->request->expects(once())
                ->method('hasRedirectHeader')
                ->will(returnValue(false));
        $this->loginProvider->expects(once())
                ->method('authenticate')
                ->will($this->returnValue(null));
        assertNull(
                $this->tokenAuthenticator->authenticate($this->request)
        );
    }

    /**
     * @test
     */
    public function delegatesAuthenticationToLoginProviderIfAuthorizationHeaderIsSetButEmpty()
    {
        $this->request->expects(once())
                ->method('hasRedirectHeader')
                ->will(returnValue(true));
        $this->request->expects(once())
                ->method('readRedirectHeader')
                ->will(returnValue(ValueReader::forValue('')));
        $this->loginProvider->expects(once())
                ->method('authenticate')
                ->will(returnValue(null));
        assertNull(
                $this->tokenAuthenticator->authenticate($this->request)
        );
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
        $this->request->expects(any())
                ->method('hasHeader')
                ->will(returnValue(false));
        $this->loginProvider->method('authenticate')
                ->will(returnValue($user));
        $this->tokenStore->method('store')
                ->will(throwException(new \Exception('failure')));
        $this->tokenAuthenticator->authenticate($this->request);
    }

    /**
     * @test
     */
    public function createsAndStoresTokenFromUserReturnedByLoginProvider()
    {
        $user = $this->mockTokenAwareUser();
        $this->request->expects(any())
                ->method('hasHeader')
                ->will(returnValue(false));
        $this->loginProvider->method('authenticate')
                ->will(returnValue($user));
        $this->tokenStore->expects(once())
                ->method('store')
                ->with(
                        equalTo($this->request),
                        isInstanceOf('stubbles\webapp\auth\Token'),
                        equalTo($user)
                  );
        assertSame(
                $user,
                $this->tokenAuthenticator->authenticate($this->request)
        );
    }

    /**
     * @test
     */
    public function userReturnedByLoginProviderHasToken()
    {
        $user = $this->mockTokenAwareUser();
        $this->request->expects(any())
                ->method('hasHeader')
                ->will(returnValue(false));
        $this->loginProvider->method('authenticate')
                ->will(returnValue($user));
        $this->tokenStore->expects($this->once())
                ->method('store')
                ->with(
                        equalTo($this->request),
                        isInstanceOf('stubbles\webapp\auth\Token'),
                        equalTo($user)
                );
        assertNotNull(
                $this->tokenAuthenticator->authenticate($this->request)
                        ->token()
        );
    }

    /**
     * @test
     * @expectedException  stubbles\webapp\auth\InternalAuthProviderException
     * @expectedExceptionMessage  Error while trying to find user by token: failure
     */
    public function throwsInternalAuthProviderExceptionWhenTokenStoreThrowsExceptionWhileFindingUser()
    {
        $this->request->expects(once())
                ->method('hasRedirectHeader')
                ->will(returnValue(true));
        $this->request->expects($this->once())
                ->method('readRedirectHeader')
                ->will(returnValue(ValueReader::forValue('someToken')));
        $this->tokenStore->method('findUserByToken')
                ->will(throwException(new \Exception('failure')));
        $this->tokenAuthenticator->authenticate($this->request);
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
        $this->request->expects(once())
                ->method('hasRedirectHeader')
                ->will(returnValue(true));
        $this->request->expects(once())
                ->method('readRedirectHeader')
                ->will(returnValue(ValueReader::forValue($headerValue)));
        $user = $this->mockTokenAwareUser();
        $this->tokenStore->method('findUserByToken')
                ->with(equalTo($this->request), equalTo(new Token($tokenValue)))
                ->will(returnValue($user));
        assertSame($user, $this->tokenAuthenticator->authenticate($this->request));
    }

    /**
     * @test
     * @dataProvider  validTokens
     */
    public function returnedUserFromValidTokenHasToken($headerValue, $tokenValue)
    {
        $this->request->expects(once())
                ->method('hasRedirectHeader')
                ->will(returnValue(true));
        $this->request->expects(once())
                ->method('readRedirectHeader')
                ->will(returnValue(ValueReader::forValue($headerValue)));
        $user  = $this->mockTokenAwareUser();
        $token = new Token($tokenValue);
        $this->tokenStore->method('findUserByToken')
                ->with(equalTo($this->request), equalTo($token))
                ->will(returnValue($user));
        assertEquals(
                $token,
                $this->tokenAuthenticator->authenticate($this->request)->token()
        );
    }

    /**
     * @test
     */
    public function delegatesAuthenticationToLoginProviderIfTokenFromAuthorizationHeaderDoesNotYieldUser()
    {
        $this->request->expects(once())
                ->method('hasRedirectHeader')
                ->will(returnValue(true));
        $this->request->expects(once())
                ->method('readRedirectHeader')
                ->will(returnValue(ValueReader::forValue('someOtherToken')));
        $token = new Token('someOtherToken');
        $this->tokenStore->method('findUserByToken')
                ->with(equalTo($this->request), equalTo($token))
                ->will($this->returnValue(null));
        $this->loginProvider->method('authenticate')->will(returnValue(null));
        assertNull($this->tokenAuthenticator->authenticate($this->request));
    }

    /**
     * @test
     */
    public function createAndStoresNewTokenIfTokenFromAuthorizationHeaderDoesNotYieldUser()
    {
        $this->request->expects(once())
                ->method('hasRedirectHeader')
                ->will(returnValue(true));
        $this->request->expects($this->once())
                ->method('readRedirectHeader')
                ->will(returnValue(ValueReader::forValue('someOtherToken')));
        $token = new Token('someOtherToken');
        $this->tokenStore->method('findUserByToken')
                ->with(equalTo($this->request), equalTo($token))
                ->will(returnValue(null));
        $user  = $this->mockTokenAwareUser();
        $this->loginProvider->method('authenticate')->will(returnValue($user));
        $this->tokenStore->expects(once())
                ->method('store')
                ->with(
                        equalTo($this->request),
                        isInstanceOf('stubbles\webapp\auth\Token'),
                        equalTo($user)
                );
        assertSame(
                $user,
                $this->tokenAuthenticator->authenticate($this->request)
        );
    }

    /**
     * @test
     */
    public function userReturnedAfterTokenRecreationHasTokenIfTokenFromAuthorizationHeaderDoesNotYieldUser()
    {
        $this->request->expects(once())
                ->method('hasRedirectHeader')
                ->will(returnValue(true));
        $this->request->expects(once())
                ->method('readRedirectHeader')
                ->will(returnValue(ValueReader::forValue('someOtherToken')));
        $token = new Token('someOtherToken');
        $this->tokenStore->method('findUserByToken')
                ->with(equalTo($this->request), equalTo($token))
                ->will($this->returnValue(null));
        $user  = $this->mockTokenAwareUser();
        $this->loginProvider->method('authenticate')->will(returnValue($user));
        $this->tokenStore->expects(once())
                ->method('store')
                ->with(
                        equalTo($this->request),
                        isInstanceOf('stubbles\webapp\auth\Token'),
                        equalTo($user)
                );
        assertNotNull(
                $this->tokenAuthenticator->authenticate($this->request)->token()
        );
    }

    /**
     * @test
     */
    public function userReturnedAfterTokenRecreationHasDifferentTokenIfTokenFromAuthorizationHeaderDoesNotYieldUser()
    {
        $this->request->expects(once())
                ->method('hasRedirectHeader')
                ->will(returnValue(true));
        $this->request->expects(once())
                ->method('readRedirectHeader')
                ->will(returnValue(ValueReader::forValue('someOtherToken')));
        $token = new Token('someOtherToken');
        $this->tokenStore->method('findUserByToken')
                ->with(equalTo($this->request), equalTo($token))
                ->will(returnValue(null));
        $user  = $this->mockTokenAwareUser();
        $this->loginProvider->method('authenticate')->will(returnValue($user));
        $this->tokenStore->expects(once())
                ->method('store')
                ->with(
                        equalTo($this->request),
                        isInstanceOf('stubbles\webapp\auth\Token'),
                        equalTo($user)
                  );
        assertNotEquals(
                $token,
                $this->tokenAuthenticator->authenticate($this->request)->token()
        );
    }
}
