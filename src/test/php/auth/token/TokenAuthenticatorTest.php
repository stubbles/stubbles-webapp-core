<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace stubbles\webapp\auth\token;

use bovigo\callmap\ClassProxy;
use bovigo\callmap\NewInstance;
use Exception;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
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
 * @since  2.1.0
 */
#[Group('auth')]
#[Group('auth_token')]
class TokenAuthenticatorTest extends TestCase
{
    private TokenAuthenticator $tokenAuthenticator;
    private TokenStore&ClassProxy $tokenStore;
    private AuthenticationProvider&ClassProxy $loginProvider;
    private Request&ClassProxy $request;

    protected function setUp(): void
    {
        $this->tokenStore = NewInstance::of(TokenStore::class);
        $this->loginProvider = NewInstance::of(AuthenticationProvider::class);
        $this->tokenAuthenticator = new TokenAuthenticator(
            $this->tokenStore,
            'some salt',
            $this->loginProvider
        );
        $this->request = NewInstance::of(Request::class);
    }

    #[Test]
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

    #[Test]
    public function delegatesAuthenticationToLoginProviderIfNoTokenInRequest(): void
    {
        $this->request->returns(['hasRedirectHeader' => false]);
        assertNull($this->tokenAuthenticator->authenticate($this->request));
        verify($this->loginProvider, 'authenticate')->wasCalledOnce();
    }

    #[Test]
    public function delegatesAuthenticationToLoginProviderIfAuthorizationHeaderIsSetButEmpty(): void
    {
        $this->request->returns([
            'hasRedirectHeader'  => true,
            'readRedirectHeader' => ValueReader::forValue('')
        ]);
        assertNull($this->tokenAuthenticator->authenticate($this->request));
        verify($this->loginProvider, 'authenticate')->wasCalledOnce();
    }

    private function createTokenAwareUser(): TokenAwareUser&ClassProxy
    {
        return NewInstance::of(TokenAwareUser::class)->returns([
            'name'        => 'Heinz Mustermann',
            'firstName'   => 'Heinz',
            'lastName'    => 'Mustermann',
            'mailAddress' => 'mm@example.com'
        ]);
    }

    #[Test]
    public function throwsInternalAuthProviderExceptionWhenTokenStoreThrowsExceptionWhileStoringToken(): void
    {
        $user = $this->createTokenAwareUser();
        $this->request->returns(['hasRedirectHeader' => false]);
        $this->loginProvider->returns(['authenticate' => $user]);
        $this->tokenStore->returns(['store' => throws(new Exception('failure'))]);

        expect(function() {
            $this->tokenAuthenticator->authenticate($this->request);
        })
            ->throws(InternalAuthProviderException::class)
            ->withMessage('Error while trying to store new token for user: failure');
    }

    #[Test]
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

    #[Test]
    public function userReturnedByLoginProviderHasToken(): void
    {
        $user  = $this->createTokenAwareUser();
        $this->request->returns(['hasRedirectHeader' => false]);
        $this->loginProvider->returns(['authenticate' => $user]);
        $loggedInUser = $this->tokenAuthenticator->authenticate($this->request);
        assertNotNull($loggedInUser->token());
    }

    #[Test]
    public function throwsInternalAuthProviderExceptionWhenTokenStoreThrowsExceptionWhileFindingUser(): void
    {
        $this->request->returns([
            'hasRedirectHeader'  => true,
            'readRedirectHeader' => ValueReader::forValue('someToken')
        ]);
        $this->tokenStore->returns(
            ['findUserByToken' => throws(new Exception('failure'))]
        );
        expect(function() {
            $this->tokenAuthenticator->authenticate($this->request);
        })
            ->throws(InternalAuthProviderException::class)
            ->withMessage('Error while trying to find user by token: failure');
    }

    public static function validTokens(): Generator
    {
        yield ['Bearer 123456789012345678901234567890ab', '123456789012345678901234567890ab'];
        yield ['someOtherToken', 'someOtherToken'];
    }

    #[Test]
    #[DataProvider('validTokens')]
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

    #[Test]
    #[DataProvider('validTokens')]
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
        $loggedInUser = $this->tokenAuthenticator->authenticate($this->request);
        assertThat($loggedInUser->token(), equals($token));
    }

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
    public function userReturnedAfterTokenRecreationHasDifferentTokenIfTokenFromHeaderDoesNotYieldUser(): void
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
