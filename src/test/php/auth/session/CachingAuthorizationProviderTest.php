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
use stubbles\webapp\auth\{AuthorizationProvider, Roles, User};
use stubbles\webapp\session\Session;

use function bovigo\assert\{
    assertThat,
    assertFalse,
    assertNull,
    assertTrue,
    predicate\equals,
    predicate\isSameAs
};
use function bovigo\callmap\verify;
use function stubbles\reflect\annotationsOfConstructorParameter;
/**
 * Tests for stubbles\webapp\auth\session\CachingAuthorizationProvider
 *
 * @since  5.0.0
 * @group  auth
 * @group  auth_session
 */
class CachingAuthorizationProviderTest extends TestCase
{
    /**
     * @var  \stubbles\webapp\auth\session\CachingAuthorizationProvider
     */
    private $cachingAuthorizationProvider;
    /**
     * mocked session
     * @var  Session&\bovigo\callmap\ClassProxy
     */
    private $session;
    /**
     * @var  AuthorizationProvider&\bovigo\callmap\ClassProxy
     */
    private $authorizationProvider;

    protected function setUp(): void
    {
        $this->session                  = NewInstance::of(Session::class);
        $this->authorizationProvider    = NewInstance::of(AuthorizationProvider::class);
        $this->cachingAuthorizationProvider = new CachingAuthorizationProvider(
                $this->session,
                $this->authorizationProvider
        );
    }

    /**
     * @test
     */
    public function annotationsPresentOnConstructor(): void
    {
        $annotations = annotationsOfConstructorParameter(
                'authorizationProvider',
                $this->cachingAuthorizationProvider
        );
        assertTrue($annotations->contain('Named'));
        assertThat(
                $annotations->firstNamed('Named')->getName(),
                equals('original')
        );
    }

    /**
     * @test
     */
    public function usesSessionValueIfRolesStoredInSession(): void
    {
        $roles = new Roles(['admin']);
        $this->session->returns(['hasValue' => true, 'value' => $roles]);
        assertThat(
                $this->cachingAuthorizationProvider->roles(
                        NewInstance::of(User::class)
                ),
                isSameAs($roles)
        );
        verify($this->authorizationProvider, 'roles')->wasNeverCalled();
    }

    /**
     * @test
     */
    public function storeReturnValueInSessionWhenOriginalAuthenticationProviderReturnsRoles(): void
    {
        $roles = new Roles(['admin']);
        $this->session->returns(['hasValue' => false]);
        $this->authorizationProvider->returns(['roles' => $roles]);
        assertThat(
                $this->cachingAuthorizationProvider->roles(
                        NewInstance::of(User::class)
                ),
                isSameAs($roles)
        );
        verify($this->session, 'putValue')->received(Roles::SESSION_KEY, $roles);
    }
}
