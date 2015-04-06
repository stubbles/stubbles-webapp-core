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
/**
 * Tests for stubbles\webapp\auth\Identity.
 *
 * @since  6.0.0
 */
class IdentityTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @return  \stubbles\webapp\auth\Identity
     */
    private function createIdentity($user = null)
    {
        return new Identity(
                null === $user ? $this->mockUser() : $user,
                new Roles(['admin'])
        );
    }

    /**
     * @return  \PHPUnit_Framework_MockObject_MockObject
     */
    private function mockUser()
    {
        return $this->getMock('stubbles\webapp\auth\User');
    }

    /**
     * @test
     */
    public function isAssociatedWithGivenUser()
    {
        $user = $this->mockUser();
        assertSame(
                $user,
                $this->createIdentity($user)->user()
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
        assertEquals(
                new Roles(['admin']),
                $this->createIdentity()->roles()
        );
    }
}
