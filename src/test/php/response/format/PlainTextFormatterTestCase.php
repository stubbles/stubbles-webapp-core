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
 * Helper class for the test.
 */
class StringConversionTestHelper
{
    /**
     * returns string conversion of this class
     *
     * @return  string
     */
    public function __toString()
    {
        return 'converted to string';
    }
}
/**
 * Tests for net\stubbles\webapp\response\format\PlainTextFormatter.
 *
 * @since  1.1.2
 * @group  response
 * @group  format
 */
class PlainTextFormatterTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * instance to test
     *
     * @type  PlainTextFormatter
     */
    private $plainTextFormatter;

    /**
     * set up test environment
     */
    public function setUp()
    {
        $this->plainTextFormatter = new PlainTextFormatter();
    }

    /**
     * @test
     */
    public function returnsPlainText()
    {
        $this->assertEquals('This is a response',
                            $this->plainTextFormatter->format('This is a response')
        );
    }

    /**
     * @test
     */
    public function returnsPlainTextForNumbers()
    {
        $this->assertEquals('303',
                            $this->plainTextFormatter->format(303)
        );
    }

    /**
     * @test
     */
    public function returnsPlainTextForBoolean()
    {
        $this->assertEquals('true',
                            $this->plainTextFormatter->format(true)
        );
        $this->assertEquals('false',
                            $this->plainTextFormatter->format(false)
        );
    }

    /**
     * @test
     */
    public function usesVarExportForArrays()
    {
        $this->assertEquals("array (\n  303 => 'cool',\n)",
                            $this->plainTextFormatter->format([303 => 'cool'])
        );
    }

    /**
     * @test
     */
    public function usesVarExportForObjectWithoutToStringMethod()
    {
        $stdClass = new \stdClass();
        $stdClass->foo = 'bar';
        $this->assertEquals("stdClass::__set_state(array(\n   'foo' => 'bar',\n))",
                            $this->plainTextFormatter->format($stdClass)
        );
    }

    /**
     * @test
     */
    public function castsObjectWithToStringMethod()
    {
        $this->assertEquals('converted to string',
                            $this->plainTextFormatter->format(new StringConversionTestHelper())
        );
    }

    /**
     * @test
     */
    public function formatForbiddenError()
    {
        $this->assertEquals('You are not allowed to access this resource.',
                            $this->plainTextFormatter->formatForbiddenError()
        );
    }

    /**
     * @test
     */
    public function formatNotFoundError()
    {
        $this->assertEquals('Given resource could not be found.',
                            $this->plainTextFormatter->formatNotFoundError()
        );
    }

    /**
     * @test
     */
    public function formatMethodNotAllowedError()
    {
        $this->assertEquals('The given request method PUT is not valid. Please use one of GET, POST, DELETE.',
                            $this->plainTextFormatter->formatMethodNotAllowedError('PUT', ['GET', 'POST', 'DELETE'])
        );
    }

    /**
     * @test
     */
    public function formatInternalServerError()
    {
        $this->assertEquals('Internal Server Error: Error message',
                            $this->plainTextFormatter->formatInternalServerError('Error message')
        );
    }
}
