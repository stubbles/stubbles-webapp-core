<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  stubbles\webapp
 */
namespace stubbles\webapp\response\format;
use stubbles\webapp\response\Headers;
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
 * Tests for stubbles\webapp\response\format\PlainTextFormatter.
 *
 * @since  1.1.2
 * @group  response
 * @group  format
 */
class PlainTextFormatterTest extends \PHPUnit_Framework_TestCase
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
        $this->markTestSkipped();
        $this->plainTextFormatter = new PlainTextFormatter();
    }

    /**
     * @test
     */
    public function returnsPlainText()
    {
        $this->assertEquals('This is a response',
                            $this->plainTextFormatter->format('This is a response', new Headers())
        );
    }

    /**
     * @test
     */
    public function returnsPlainTextForNumbers()
    {
        $this->assertEquals('303',
                            $this->plainTextFormatter->format(303, new Headers())
        );
    }

    /**
     * @test
     */
    public function returnsPlainTextForBoolean()
    {
        $this->assertEquals('true',
                            $this->plainTextFormatter->format(true, new Headers())
        );
        $this->assertEquals('false',
                            $this->plainTextFormatter->format(false, new Headers())
        );
    }

    /**
     * @test
     */
    public function usesVarExportForArrays()
    {
        $this->assertEquals("array (\n  303 => 'cool',\n)",
                            $this->plainTextFormatter->format([303 => 'cool'], new Headers())
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
                            $this->plainTextFormatter->format($stdClass, new Headers())
        );
    }

    /**
     * @test
     */
    public function castsObjectWithToStringMethod()
    {
        $this->assertEquals('converted to string',
                            $this->plainTextFormatter->format(new StringConversionTestHelper(), new Headers())
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
