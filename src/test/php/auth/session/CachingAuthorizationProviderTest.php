<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace stubbles\webapp\auth\session;

use bovigo\callmap\ClassProxy;
use bovigo\callmap\NewInstance;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
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
 */
#[Group('auth')]
#[Group('auth_session')]
class CachingAuthorizationProviderTest extends TestCase
{
    private CachingAuthorizationProvider $cachingAuthorizationProvider;
    private Session&ClassProxy $session;
    private AuthorizationProvider&ClassProxy $authorizationProvider;

    protected function setUp(): void
    {
        $this->session  = NewInstance::of(Session::class);
        $this->authorizationProvider = NewInstance::of(AuthorizationProvider::class);
        $this->cachingAuthorizationProvider = new CachingAuthorizationProvider(
            $this->session,
            $this->authorizationProvider
        );
    }

    #[Test]
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

    #[Test]
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

    #[Test]
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
