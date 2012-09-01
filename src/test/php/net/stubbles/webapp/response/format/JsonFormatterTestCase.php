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
 * Tests for net\stubbles\webapp\response\format\JsonFormatter.
 *
 * @since  1.1.0
 * @group  response
 * @group  format
 */
class JsonFormatterTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * instance to test
     *
     * @type  JsonFormatter
     */
    private $jsonFormatter;

    /**
     * set up test environment
     */
    public function setUp()
    {
        $this->jsonFormatter = new JsonFormatter();
    }

    /**
     * @test
     */
    public function formatsJson()
    {
        $this->assertEquals(json_encode(array('foo', 'bar' => 313)),
                            $this->jsonFormatter->format(array('foo', 'bar' => 313))
        );
    }

    /**
     * @test
     */
    public function formatForbiddenError()
    {
        $this->assertEquals(json_encode(array('error' => 'You are not allowed to access this resource.')),
                            $this->jsonFormatter->formatForbiddenError()
        );
    }

    /**
     * @test
     */
    public function formatNotFoundError()
    {
        $this->assertEquals(json_encode(array('error' => 'Given resource could not be found.')),
                            $this->jsonFormatter->formatNotFoundError()
        );
    }

    /**
     * @test
     */
    public function formatMethodNotAllowedError()
    {
        $this->assertEquals(json_encode(array('error' => 'The given request method PUT is not valid. Please use one of GET, POST, DELETE.')),
                            $this->jsonFormatter->formatMethodNotAllowedError('PUT', array('GET', 'POST', 'DELETE'))
        );
    }

    /**
     * @test
     */
    public function formatInternalServerError()
    {
        $this->assertEquals(json_encode(array('error' => 'Internal Server Error: Error message')),
                            $this->jsonFormatter->formatInternalServerError('Error message')
        );
    }
}
?>