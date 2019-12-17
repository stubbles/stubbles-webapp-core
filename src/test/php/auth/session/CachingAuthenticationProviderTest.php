<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace stubbles\webapp\auth\session;
use bovigo\callmap\NewInstance;
use PHPUnit\Framework\TestCase;
use stubbles\webapp\Request;
use stubbles\webapp\auth\AuthenticationProvider;
use stubbles\webapp\auth\User;
use stubbles\webapp\session\Session;

use function bovigo\assert\{
    assertThat,
    assertNull,
    assertTrue,
    predicate\equals,
    predicate\isSameAs
};
use function bovigo\callmap\verify;
use function stubbles\reflect\annotationsOfConstructorParameter;
/**
 * Tests for stubbles\webapp\auth\session\CachingAuthenticationProvider
 *
 * @since  5.0.0
 * @group  auth
 * @group  auth_session
 */
class CachingAuthenticationProviderTest extends TestCase
{
    /**
     * @var  \stubbles\webapp\auth\session\CachingAuthenticationProvider
     */
    private $cachingAuthenticationProvider;
    /**
     * @var  Session&\bovigo\callmap\ClassProxy
     */
    private $session;
    /**
     * @var  AuthenticationProvider&\bovigo\callmap\ClassProxy
     */
    private $authenticationProvider;

    protected function setUp(): void
    {
        $this->session                = NewInstance::of(Session::class);
        $this->authenticationProvider = NewInstance::of(AuthenticationProvider::class);
        $this->cachingAuthenticationProvider = new CachingAuthenticationProvider(
            $this->session,
            $this->authenticationProvider
        );
    }

    /**
     * @test
     */
    public function annotationsPresentOnConstructor(): void
    {
        $parameterAnnotations = annotationsOfConstructorParameter(
            'authenticationProvider',
            $this->cachingAuthenticationProvider
        );
        assertTrue($parameterAnnotations->contain('Named'));
        assertThat(
            $parameterAnnotations->firstNamed('Named')->getName(),
            equals('original')
        );
    }

    /**
     * @test
     */
    public function usesSessionValueIfUserStoredInSession(): void
    {
        $user = NewInstance::of(User::class);
        $this->session->returns(['hasValue' => true, 'value' => $user]);
        assertThat(
            $this->cachingAuthenticationProvider->authenticate(NewInstance::of(Request::class)),
            isSameAs($user)
        );
        verify($this->authenticationProvider, 'authenticate')->wasNeverCalled();
    }

    /**
     * @test
     */
    public function doesNotStoreReturnValueWhenOriginalAuthenticationProviderReturnsNull(): void
    {
        $this->session->returns(['hasValue' => false]);
        assertNull(
            $this->cachingAuthenticationProvider->authenticate(NewInstance::of(Request::class))
        );
        verify($this->session, 'putValue')->wasNeverCalled();
    }

    /**
     * @test
     */
    public function storeReturnValueInSessionWhenOriginalAuthenticationProviderReturnsUser(): void
    {
        $user = NewInstance::of(User::class);
        $this->session->returns(['hasValue' => false]);
        $this->authenticationProvider->returns(['authenticate' => $user]);
        assertThat(
            $this->cachingAuthenticationProvider->authenticate(
                NewInstance::of(Request::class)
            ),
            isSameAs($user)
        );
        verify($this->session, 'putValue')->received(User::SESSION_KEY, $user);
    }

    /**
     * @test
     */
    public function returnsLoginUriFromOriginalAuthenticationProvider(): void
    {
        $this->authenticationProvider->returns([
            'loginUri' => 'http://login.example.net/'
        ]);
        assertThat(
            $this->cachingAuthenticationProvider->loginUri(
                NewInstance::of(Request::class)
            ),
            equals('http://login.example.net/')
        );
    }

    /**
     * @test
     * @group  issue_73
     * @since  8.0.0
     */
    public function returnsChallengesFromOriginalAuthenticationProvider(): void
    {
        $this->authenticationProvider->returns([
            'challengesFor' => ['Basic realm="simple"']
        ]);
        assertThat(
            $this->cachingAuthenticationProvider->challengesFor(
                NewInstance::of(Request::class)
            ),
            equals(['Basic realm="simple"'])
        );
    }
}
