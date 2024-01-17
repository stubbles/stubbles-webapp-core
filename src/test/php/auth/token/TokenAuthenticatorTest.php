<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace stubbles\webapp\auth\token;
use bovigo\callmap\NewInstance;
use PHPUnit\Framework\TestCase;
use stubbles\input\ValueReader;
use stubbles\webapp\Request;
use stubbles\webapp\auth\{
    AuthenticationProvider,
    InternalAuthProviderException,
    Token,
    TokenAwareUser
};

use function bovigo\assert\{
    assertThat,
    assertNull,
    assertNotNull,
    assertTrue,
    expect,
    predicate\equals,
    predicate\isNotEqualTo,
    predicate\isSameAs
};
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
class TokenAuthenticatorTest extends TestCase
{
    /**
     * @var  \stubbles\webapp\auth\token\TokenAuthenticator
     */
    private $tokenAuthenticator;
    /**
     * @var  TokenStore&\bovigo\callmap\ClassProxy
     */
    private $tokenStore;
    /**
     * @var  AuthenticationProvider&\bovigo\callmap\ClassProxy
     */
    private $loginProvider;
    /**
     * @var  Request&\bovigo\callmap\ClassProxy
     */
    private $request;

    protected function setUp(): void
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
    public function annotationsPresentOnConstructor(): void
    {
        $tokenSaltParamAnnotations = annotationsOfConstructorParameter(
                'tokenSalt',
                $this->tokenAuthenticator
        );
        assertTrue($tokenSaltParamAnnotations->contain('Property'));
        assertThat(
                $tokenSaltParamAnnotations->firstNamed('Property')->getName(),
                equals('stubbles.webapp.auth.token.salt')
        );

        $loginProviderParamAnnotations = annotationsOfConstructorParameter(
                'loginProvider',
                $this->tokenAuthenticator
        );
        assertTrue($loginProviderParamAnnotations->contain('Named'));
        assertThat(
                $loginProviderParamAnnotations->firstNamed('Named')->getName(),
                equals('stubbles.webapp.auth.token.loginProvider')
        );
    }

    /**
     * @test
     */
    public function delegatesAuthenticationToLoginProviderIfNoTokenInRequest(): void
    {
        $this->request->returns(['hasRedirectHeader' => false]);
        assertNull($this->tokenAuthenticator->authenticate($this->request));
        verify($this->loginProvider, 'authenticate')->wasCalledOnce();
    }

    /**
     * @test
     */
    public function delegatesAuthenticationToLoginProviderIfAuthorizationHeaderIsSetButEmpty(): void
    {
        $this->request->returns([
                'hasRedirectHeader'  => true,
                'readRedirectHeader' => ValueReader::forValue('')
        ]);
        assertNull($this->tokenAuthenticator->authenticate($this->request));
        verify($this->loginProvider, 'authenticate')->wasCalledOnce();
    }

    /**
     * @return  TokenAwareUser&\bovigo\callmap\ClassProxy
     */
    private function createTokenAwareUser(): TokenAwareUser
    {
        return NewInstance::of(TokenAwareUser::class)->returns([
                'name'        => 'Heinz Mustermann',
                'firstName'   => 'Heinz',
                'lastName'    => 'Mustermann',
                'mailAddress' => 'mm@example.com'
        ]);
    }

    /**
     * @test
     */
    public function throwsInternalAuthProviderExceptionWhenTokenStoreThrowsExceptionWhileStoringToken(): void
    {
        $user = $this->createTokenAwareUser();
        $this->request->returns(['hasRedirectHeader' => false]);
        $this->loginProvider->returns(['authenticate' => $user]);
        $this->tokenStore->returns(['store' => throws(new \Exception('failure'))]);

        expect(function() {
                $this->tokenAuthenticator->authenticate($this->request);
        })
                ->throws(InternalAuthProviderException::class)
                ->withMessage('Error while trying to store new token for user: failure');
    }

    /**
     * @test
     */
    public function createsAndStoresTokenFromUserReturnedByLoginProvider(): void
    {
        $token = new Token('value');
        $user  = $this->createTokenAwareUser();
        $user->returns(['createToken' => $token]);
        $this->request->returns(['hasRedirectHeader' => false]);
        $this->loginProvider->returns(['authenticate' => $user]);
        assertThat(
                $this->tokenAuthenticator->authenticate($this->request),
                isSameAs($user)
        );
        verify($this->tokenStore, 'store')
                ->received($this->request, $token, $user);
    }

    /**
     * @test
     */
    public function userReturnedByLoginProviderHasToken(): void
    {
        $user  = $this->createTokenAwareUser();
        $this->request->returns(['hasRedirectHeader' => false]);
        $this->loginProvider->returns(['authenticate' => $user]);
        /** @var  \stubbles\webapp\auth\User  $loggedInUser */
        $loggedInUser = $this->tokenAuthenticator->authenticate($this->request);
        assertNotNull($loggedInUser->token());
    }

    /**
     * @test
     */
    public function throwsInternalAuthProviderExceptionWhenTokenStoreThrowsExceptionWhileFindingUser(): void
    {
        $this->request->returns([
                'hasRedirectHeader'  => true,
                'readRedirectHeader' => ValueReader::forValue('someToken')
        ]);
        $this->tokenStore->returns(
                ['findUserByToken' => throws(new \Exception('failure'))]
        );
        expect(function() {
                $this->tokenAuthenticator->authenticate($this->request);
        })
                ->throws(InternalAuthProviderException::class)
                ->withMessage('Error while trying to find user by token: failure');
    }

    /**
     * @return  array<string[]>
     */
    public static function validTokens(): array
    {
        return [['Bearer 123456789012345678901234567890ab', '123456789012345678901234567890ab'],
                ['someOtherToken', 'someOtherToken']
        ];
    }

    /**
     * @test
     * @dataProvider  validTokens
     */
    public function returnsUserWhenAuthorizationHeaderContainsValidToken(
            string $headerValue,
            string $tokenValue
    ): void {
        $this->request->returns([
                'hasRedirectHeader'  => true,
                'readRedirectHeader' => ValueReader::forValue($headerValue)
        ]);
        $user = $this->createTokenAwareUser();
        $this->tokenStore->returns(['findUserByToken' => $user]);
        assertThat(
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
    public function returnedUserFromValidTokenHasToken(
            string $headerValue,
            string $tokenValue
    ): void {
        $this->request->returns([
                'hasRedirectHeader'  => true,
                'readRedirectHeader' => ValueReader::forValue($headerValue)
        ]);
        $user  = $this->createTokenAwareUser();
        $token = new Token($tokenValue);
        $this->tokenStore->returns(['findUserByToken' => $user]);
        /** @var  \stubbles\webapp\auth\User  $loggedInUser */
        $loggedInUser = $this->tokenAuthenticator->authenticate($this->request);
        assertThat($loggedInUser->token(), equals($token));
    }

    /**
     * @test
     */
    public function delegatesAuthenticationToLoginProviderIfTokenFromAuthorizationHeaderDoesNotYieldUser(): void
    {
        $this->request->returns([
                'hasRedirectHeader'  => true,
                'readRedirectHeader' => ValueReader::forValue('someOtherToken')
        ]);
        assertNull($this->tokenAuthenticator->authenticate($this->request));
        verify($this->tokenStore, 'findUserByToken')
                ->received($this->request, new Token('someOtherToken'));
        verify($this->loginProvider, 'authenticate')->wasCalledOnce();
    }

    /**
     * @test
     */
    public function createAndStoresNewTokenIfTokenFromAuthorizationHeaderDoesNotYieldUser(): void
    {
        $this->request->returns([
                'hasRedirectHeader'  => true,
                'readRedirectHeader' => ValueReader::forValue('someOtherToken')
        ]);
        $user  = $this->createTokenAwareUser();
        $this->loginProvider->returns(['authenticate' => $user]);
        assertThat(
                $this->tokenAuthenticator->authenticate($this->request),
                isSameAs($user)
        );
        verify($this->tokenStore, 'findUserByToken')
                ->received($this->request, new Token('someOtherToken'));
    }

    /**
     * @test
     */
    public function userReturnedAfterTokenRecreationHasTokenIfTokenFromAuthorizationHeaderDoesNotYieldUser(): void
    {
        $this->request->returns([
                'hasRedirectHeader'  => true,
                'readRedirectHeader' => ValueReader::forValue('someOtherToken')
        ]);
        $user = $this->createTokenAwareUser();
        $this->loginProvider->returns(['authenticate' => $user]);
        /** @var  \stubbles\webapp\auth\User  $loggedInUser */
        $loggedInUser = $this->tokenAuthenticator->authenticate($this->request);
        assertNotNull($loggedInUser->token());
        verify($this->tokenStore, 'findUserByToken')
                ->received($this->request, new Token('someOtherToken'));
    }

    /**
     * @test
     */
    public function userReturnedAfterTokenRecreationHasDifferentTokenIfTokenFromAuthorizationHeaderDoesNotYieldUser(): void
    {
        $this->request->returns([
                'hasRedirectHeader'  => true,
                'readRedirectHeader' => ValueReader::forValue('someOtherToken')
        ]);
        $user = $this->createTokenAwareUser();
        $this->loginProvider->returns(['authenticate' => $user]);
        /** @var  \stubbles\webapp\auth\User  $loggedInUser */
        $loggedInUser = $this->tokenAuthenticator->authenticate($this->request);
        assertThat($loggedInUser->token(), isNotEqualTo(new Token('someOtherToken')));
    }
}
