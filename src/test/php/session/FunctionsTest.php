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
        $this->assertInstanceOf(
                'stubbles\webapp\session\WebSession',
                 native('example', md5('example user agent'))
        );
    }

    /**
     * @test
     */
    public function noneDurableCreatesWebSession()
    {
        $this->assertInstanceOf(
                'stubbles\webapp\session\WebSession',
                 noneDurable()
        );
    }

    /**
     * @test
     * @since  5.0.0
     */
    public function nullSessionCreatesNullSession()
    {
        $this->assertInstanceOf(
                'stubbles\webapp\session\NullSession',
                nullSession()
        );
    }
}
