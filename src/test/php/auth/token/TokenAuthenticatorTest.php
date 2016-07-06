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
use bovigo\callmap\NewInstance;
use stubbles\input\ValueReader;
use stubbles\webapp\Request;
use stubbles\webapp\auth\AuthenticationProvider;
use stubbles\webapp\auth\Token;
use stubbles\webapp\auth\TokenAwareUser;

use function bovigo\assert\assert;
use function bovigo\assert\assertNull;
use function bovigo\assert\assertNotNull;
use function bovigo\assert\assertTrue;
use function bovigo\assert\predicate\equals;
use function bovigo\assert\predicate\isNotEqualTo;
use function bovigo\assert\predicate\isSameAs;
use function bovigo\callmap\throws;
use function bovigo\callmap\verify;
use function stubbles\reflect\annotationsOfConstructorParameter;
/**
 * Test for stubbles\webapp\auth\token\TokenAuthenticator.
 *
 * @group  auth
 * @group  auth_token
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
        $this->tokenStore     = NewInstance::of(TokenStore::class);
        $this->loginProvider  = NewInstance::of(AuthenticationProvider::class);
        $this->tokenAuthenticator = new TokenAuthenticator(
                $this->tokenStore,
                'some salt',
                $this->loginProvider
        );
        $this->request = NewInstance::of(Request::class);
    }

    /**
     * @test
     */
    public function annotationsPresentOnConstructor()
    {
        $tokenSaltParamAnnotations = annotationsOfConstructorParameter(
                'tokenSalt',
                $this->tokenAuthenticator
        );
        assertTrue($tokenSaltParamAnnotations->contain('Property'));
        assert(
                $tokenSaltParamAnnotations->firstNamed('Property')->getName(),
                equals('stubbles.webapp.auth.token.salt')
        );

        $loginProviderParamAnnotations = annotationsOfConstructorParameter(
                'loginProvider',
                $this->tokenAuthenticator
        );
        assertTrue($loginProviderParamAnnotations->contain('Named'));
        assert(
                $loginProviderParamAnnotations->firstNamed('Named')->getName(),
                equals('stubbles.webapp.auth.token.loginProvider')
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
        verify($this->loginProvider, 'authenticate')->wasCalledOnce();
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
        verify($this->loginProvider, 'authenticate')->wasCalledOnce();
    }

    /**
     * @return  \bovigo\callmap\Proxy
     */
    private function createTokenAwareUser()
    {
        return NewInstance::of(TokenAwareUser::class);
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
                ['store' => throws(new \Exception('failure'))]
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
        assert(
                $this->tokenAuthenticator->authenticate($this->request),
                isSameAs($user)
        );
        verify($this->tokenStore, 'store')
                ->received($this->request, $token, $user);
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
                ['findUserByToken' => throws(new \Exception('failure'))]
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
        assert(
                $this->tokenAuthenticator->authenticate($this->request),
                isSameAs($user)
        );
        verify($this->tokenStore, 'findUserByToken')
                ->received($this->request, new Token($tokenValue));
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
        assert(
                $this->tokenAuthenticator->authenticate($this->request)->token(),
                equals($token)
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
        verify($this->tokenStore, 'findUserByToken')
                ->received($this->request, new Token('someOtherToken'));
        verify($this->loginProvider, 'authenticate')->wasCalledOnce();
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
        assert(
                $this->tokenAuthenticator->authenticate($this->request),
                isSameAs($user)
        );
        verify($this->tokenStore, 'findUserByToken')
                ->received($this->request, new Token('someOtherToken'));
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
        verify($this->tokenStore, 'findUserByToken')
                ->received($this->request, new Token('someOtherToken'));
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
        assert(
                $this->tokenAuthenticator->authenticate($this->request)->token(),
                isNotEqualTo(new Token('someOtherToken'))
        );
    }
}
