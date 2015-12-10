<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  stubbles\webapp\session
 */
namespace stubbles\webapp\session;
/**
 * Tests for stubbles\webapp\session\*().
 *
 * @since  4.0.0
 * @group  session
 */
class FunctionsTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function nativeCreatesWebSession()
    {
        assertInstanceOf(
                WebSession::class,
                native('example', md5('example user agent'))
        );
    }

    /**
     * @test
     */
    public function noneDurableCreatesWebSession()
    {
        assertInstanceOf(WebSession::class, noneDurable());
    }

    /**
     * @test
     * @since  5.0.0
     */
    public function nullSessionCreatesNullSession()
    {
        assertInstanceOf(NullSession::class, nullSession());
    }
}
