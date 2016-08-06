<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  stubbles\webapp
 */
namespace stubbles\webapp\auth\session;
use bovigo\callmap\NewInstance;
use stubbles\webapp\auth\{AuthorizationProvider, Roles, User};
use stubbles\webapp\session\Session;

use function bovigo\assert\{
    assert,
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
class CachingAuthorizationProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * instance to test
     *
     * @type  \stubbles\webapp\auth\session\CachingAuthorizationProvider
     */
    private $cachingAuthorizationProvider;
    /**
     * mocked session
     *
     * @type  \bovigo\callmap\Proxy
     */
    private $session;
    /**
     * mocked base authentication provider
     *
     * @type  \bovigo\callmap\Proxy
     */
    private $authorizationProvider;

    /**
     * set up test environment
     */
    public function setUp()
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
    public function annotationsPresentOnConstructor()
    {
        $annotations = annotationsOfConstructorParameter(
                'authorizationProvider',
                $this->cachingAuthorizationProvider
        );
        assertTrue($annotations->contain('Named'));
        assert(
                $annotations->firstNamed('Named')->getName(),
                equals('original')
        );
    }

    /**
     * @test
     */
    public function usesSessionValueIfRolesStoredInSession()
    {
        $roles = new Roles(['admin']);
        $this->session->returns(['hasValue' => true, 'value' => $roles]);
        assert(
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
    public function storeReturnValueInSessionWhenOriginalAuthenticationProviderReturnsRoles()
    {
        $roles = new Roles(['admin']);
        $this->session->returns(['hasValue' => false]);
        $this->authorizationProvider->returns(['roles' => $roles]);
        assert(
                $this->cachingAuthorizationProvider->roles(
                        NewInstance::of(User::class)
                ),
                isSameAs($roles)
        );
        verify($this->session, 'putValue')->received(Roles::SESSION_KEY, $roles);
    }
}
