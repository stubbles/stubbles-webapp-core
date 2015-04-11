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
use bovigo\callmap;
use bovigo\callmap\NewInstance;
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
     * @type  \bovigo\callmap\Proxy
     */
    private $tokenStore;
    /**
     * mocked login provider
     *
     * @type  \bovigo\callmap\Proxy
     */
    private $loginProvider;
    /**
     * mocked request
     *
     * @type  \bovigo\callmap\Proxy
     */
    private $request;

    /**
     * set up test environment
     */
    public function setUp()
    {
        $this->tokenStore     = NewInstance::of('stubbles\webapp\auth\token\TokenStore');
        $this->loginProvider  = NewInstance::of('stubbles\webapp\auth\AuthenticationProvider');
        $this->tokenAuthenticator = new TokenAuthenticator(
                $this->tokenStore,
                'some salt',
                $this->loginProvider
        );
        $this->request = NewInstance::of('stubbles\webapp\Request');
    }

    /**
     * @test
     */
    public function annotationsPresentOnConstructor()
    {
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
        $this->request->mapCalls(['hasRedirectHeader' => false]);
        assertNull(
                $this->tokenAuthenticator->authenticate($this->request)
        );
        assertEquals(1, $this->loginProvider->callsReceivedFor('authenticate'));
    }

    /**
     * @test
     */
    public function delegatesAuthenticationToLoginProviderIfAuthorizationHeaderIsSetButEmpty()
    {
        $this->request->mapCalls(
                ['hasRedirectHeader'  => true,
                 'readRedirectHeader' => ValueReader::forValue('')
                ]
        );
        assertNull(
                $this->tokenAuthenticator->authenticate($this->request)
        );
        assertEquals(1, $this->loginProvider->callsReceivedFor('authenticate'));
    }

    /**
     * @return  \bovigo\callmap\Proxy
     */
    private function createTokenAwareUser()
    {
        return NewInstance::of('stubbles\webapp\auth\TokenAwareUser');
    }

    /**
     * @test
     * @expectedException  stubbles\webapp\auth\InternalAuthProviderException
     * @expectedExceptionMessage  Error while trying to store new token for user: failure
     */
    public function throwsInternalAuthProviderExceptionWhenTokenStoreThrowsExceptionWhileStoringToken()
    {
        $user = $this->createTokenAwareUser();
        $this->request->mapCalls(['hasRedirectHeader' => false]);
        $this->loginProvider->mapCalls(['authenticate' => $user]);
        $this->tokenStore->mapCalls(
                ['store' => callmap\throws(new \Exception('failure'))]
        );

        $this->tokenAuthenticator->authenticate($this->request);
    }

    /**
     * @test
     */
    public function createsAndStoresTokenFromUserReturnedByLoginProvider()
    {
        $token = new Token('value');
        $user  = $this->createTokenAwareUser();
        $user->mapCalls(['createToken' => $token]);
        $this->request->mapCalls(['hasRedirectHeader' => false]);
        $this->loginProvider->mapCalls(['authenticate' => $user]);
        assertSame(
                $user,
                $this->tokenAuthenticator->authenticate($this->request)
        );
        assertEquals(
                [$this->request, $token, $user],
                $this->tokenStore->argumentsReceivedFor('store')
        );
    }

    /**
     * @test
     */
    public function userReturnedByLoginProviderHasToken()
    {
        $user  = $this->createTokenAwareUser();
        $this->request->mapCalls(['hasRedirectHeader' => false]);
        $this->loginProvider->mapCalls(['authenticate' => $user]);
        assertNotNull(
                $this->tokenAuthenticator->authenticate($this->request)->token()
        );
    }

    /**
     * @test
     * @expectedException  stubbles\webapp\auth\InternalAuthProviderException
     * @expectedExceptionMessage  Error while trying to find user by token: failure
     */
    public function throwsInternalAuthProviderExceptionWhenTokenStoreThrowsExceptionWhileFindingUser()
    {
        $this->request->mapCalls(
                ['hasRedirectHeader'  => true,
                 'readRedirectHeader' => ValueReader::forValue('someToken')
                ]
        );
        $this->tokenStore->mapCalls(
                ['findUserByToken' => callmap\throws(new \Exception('failure'))]
        );
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
        $this->request->mapCalls(
                ['hasRedirectHeader'  => true,
                 'readRedirectHeader' => ValueReader::forValue($headerValue)
                ]
        );
        $user = $this->createTokenAwareUser();
        $this->tokenStore->mapCalls(['findUserByToken' => $user]);
        assertSame($user, $this->tokenAuthenticator->authenticate($this->request));
        assertEquals(
                [$this->request, new Token($tokenValue)],
                $this->tokenStore->argumentsReceivedFor('findUserByToken')
        );
    }

    /**
     * @test
     * @dataProvider  validTokens
     */
    public function returnedUserFromValidTokenHasToken($headerValue, $tokenValue)
    {
        $this->request->mapCalls(
                ['hasRedirectHeader'  => true,
                 'readRedirectHeader' => ValueReader::forValue($headerValue)
                ]
        );
        $user  = $this->createTokenAwareUser();
        $token = new Token($tokenValue);
        $this->tokenStore->mapCalls(['findUserByToken' => $user]);
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
        $this->request->mapCalls(
                ['hasRedirectHeader'  => true,
                 'readRedirectHeader' => ValueReader::forValue('someOtherToken')
                ]
        );
        assertNull($this->tokenAuthenticator->authenticate($this->request));
        assertEquals(
                [$this->request, new Token('someOtherToken')],
                $this->tokenStore->argumentsReceivedFor('findUserByToken')
        );
        assertEquals(1, $this->loginProvider->callsReceivedFor('authenticate'));
    }

    /**
     * @test
     */
    public function createAndStoresNewTokenIfTokenFromAuthorizationHeaderDoesNotYieldUser()
    {
        $this->request->mapCalls(
                ['hasRedirectHeader'  => true,
                 'readRedirectHeader' => ValueReader::forValue('someOtherToken')
                ]
        );
        $user  = $this->createTokenAwareUser();
        $this->loginProvider->mapCalls(['authenticate' => $user]);
        assertSame(
                $user,
                $this->tokenAuthenticator->authenticate($this->request)
        );
        assertEquals(
                [$this->request, new Token('someOtherToken')],
                $this->tokenStore->argumentsReceivedFor('findUserByToken')
        );
    }

    /**
     * @test
     */
    public function userReturnedAfterTokenRecreationHasTokenIfTokenFromAuthorizationHeaderDoesNotYieldUser()
    {
        $this->request->mapCalls(
                ['hasRedirectHeader'  => true,
                 'readRedirectHeader' => ValueReader::forValue('someOtherToken')
                ]
        );
        $user = $this->createTokenAwareUser();
        $this->loginProvider->mapCalls(['authenticate' => $user]);
        assertNotNull(
                $this->tokenAuthenticator->authenticate($this->request)->token()
        );
        assertEquals(
                [$this->request, new Token('someOtherToken')],
                $this->tokenStore->argumentsReceivedFor('findUserByToken')
        );
    }

    /**
     * @test
     */
    public function userReturnedAfterTokenRecreationHasDifferentTokenIfTokenFromAuthorizationHeaderDoesNotYieldUser()
    {
        $this->request->mapCalls(
                ['hasRedirectHeader'  => true,
                 'readRedirectHeader' => ValueReader::forValue('someOtherToken')
                ]
        );
        $user  = $this->createTokenAwareUser();
        $this->loginProvider->mapCalls(['authenticate' => $user]);
        assertNotEquals(
                new Token('someOtherToken'),
                $this->tokenAuthenticator->authenticate($this->request)->token()
        );
    }
}
