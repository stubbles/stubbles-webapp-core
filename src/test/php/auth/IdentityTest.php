<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  stubbles\webapp
 */
namespace stubbles\webapp\auth;
use bovigo\callmap\NewInstance;

use function bovigo\assert\assert;
use function bovigo\assert\assertTrue;
use function bovigo\assert\predicate\equals;
use function bovigo\assert\predicate\isSameAs;
/**
 * Tests for stubbles\webapp\auth\Identity.
 *
 * @since  6.0.0
 * @group  auth
 */
class IdentityTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @param   \stubbles\webapp\auth\User
     * @return  \stubbles\webapp\auth\Identity
     */
    private function createIdentity(User $user = null)
    {
        return new Identity(
                null === $user ? $this->createUser() : $user,
                new Roles(['admin'])
        );
    }

    /**
     * @return  \stubbles\webapp\auth\User
     */
    private function createUser()
    {
        return NewInstance::of(User::class);
    }

    /**
     * @test
     */
    public function isAssociatedWithGivenUser()
    {
        $user = $this->createUser();
        assert(
                $this->createIdentity($user)->user(),
                isSameAs($user)
        );
    }

    /**
     * @test
     */
    public function identityHasRoleWhenGivenRolesContainRole()
    {
        assertTrue($this->createIdentity()->hasRole('admin'));
    }

    /**
     * @test
     */
    public function returnsGivenRoles()
    {
        assert($this->createIdentity()->roles(), equals(new Roles(['admin'])));
    }
}
