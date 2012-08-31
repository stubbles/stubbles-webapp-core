<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  net\stubbles\webapp
 */
namespace net\stubbles\webapp\response\format;
/**
 * Tests for net\stubbles\webapp\response\format\VoidFormatter.
 *
 * @since  1.1.0
 * @group  format
 */
class VoidFormatterTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * instance to test
     *
     * @type  VoidFormatter
     */
    private $voidFormatter;

    /**
     * set up test environment
     */
    public function setUp()
    {
        $this->voidFormatter = new VoidFormatter();
    }

    /**
     * @test
     */
    public function formatReturnsEmptyString()
    {
        $this->assertEquals('', $this->voidFormatter->format(array('foo', 'bar' => 313)));
    }

    /**
     * @test
     */
    public function formatForbiddenErrorReturnsEmptyString()
    {
        $this->assertEquals('', $this->voidFormatter->formatForbiddenError());
    }

    /**
     * @test
     */
    public function formatNotFoundErrorReturnsEmptyString()
    {
        $this->assertEquals('', $this->voidFormatter->formatNotFoundError());
    }

    /**
     * @test
     */
    public function formatMethodNotAllowedErrorReturnsEmptyString()
    {
        $this->assertEquals('', $this->voidFormatter->formatMethodNotAllowedError('PUT', array('GET', 'POST', 'DELETE')));
    }

    /**
     * @test
     */
    public function formatInternalServerErrorReturnsEmptyString()
    {
        $this->assertEquals('', $this->voidFormatter->formatInternalServerError(new \Exception('Error  message')));
    }
}
?>