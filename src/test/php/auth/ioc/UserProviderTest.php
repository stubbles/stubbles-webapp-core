<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  stubbles\webapp
 */
namespace stubbles\webapp\auth\ioc;
use stubbles\lang\reflect;
/**
 * Test for stubbles\webapp\auth\ioc\UserProvider.
 *
 * @since  5.0.0
 * @group  auth
 * @group  ioc
 */
class UserProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * instance to test
     *
     * @type  \stubbles\webapp\auth\ioc\UserProvider
     */
    private $userProvider;

    /**
     * set up test environment
     */
    public function setUp()
    {
        $this->userProvider = new UserProvider();
    }

    /**
     * clean up test environment
     */
    public function tearDown()
    {
        UserProvider::store(null);
    }

    /**
     * @test
     */
    public function annotationsPresentOnClass()
    {
        $this->assertTrue(
                reflect\annotationsOf($this->userProvider)->contain('Singleton')
        );
    }

    /**
     * @test
     */
    public function isProviderForUser()
    {
        $this->assertEquals(
                get_class($this->userProvider),
                reflect\annotationsOf('stubbles\webapp\auth\User')
                    ->firstNamed('ProvidedBy')
                    ->getValue()
                    ->getName()
        );
    }

    /**
     * @test
     * @expectedException  RuntimeException
     */
    public function throwsRuntimeExceptionWhenNoUserStored()
    {
        $this->userProvider->get();
    }

    /**
     * @test
     */
    public function returnsUserPreviouslyStored()
    {
        $user = $this->getMock('stubbles\webapp\auth\User');
        $this->assertSame($user, UserProvider::store($user));
        $this->assertSame($user, $this->userProvider->get());
    }
}
