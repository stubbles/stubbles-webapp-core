<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  stubbles\webapp\session
 */
namespace stubbles\webapp\session;
use function bovigo\assert\assert;
use function bovigo\assert\predicate\isInstanceOf;
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
        assert(
                native('example', md5('example user agent')),
                isInstanceOf(WebSession::class)
        );
    }

    /**
     * @test
     */
    public function noneDurableCreatesWebSession()
    {
        assert(noneDurable(), isInstanceOf(WebSession::class));
    }

    /**
     * @test
     * @since  5.0.0
     */
    public function nullSessionCreatesNullSession()
    {
        assert(nullSession(), isInstanceOf(NullSession::class));
    }
}
