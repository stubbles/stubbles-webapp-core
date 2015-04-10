<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  stubbles\webapp
 */
namespace stubbles\webapp\routing;
use bovigo\callmap\NewInstance;
/**
 * Tests for stubbles\webapp\routing\ResourceOptions.
 *
 * @since  2.2.0
 * @group  routing
 */
class ResourceOptionsTest extends \PHPUnit_Framework_TestCase
{
    /**
     * instance to test
     *
     * @type  \stubbles\webapp\routing\ResourceOptions
     */
    private $resourceOptions;

    /**
     * set up test environment
     */
    public function setUp()
    {
        $this->resourceOptions = new ResourceOptions(
                NewInstance::stub('stubbles\ioc\Injector'),
                new CalledUri('http://example.com/hello/world', 'GET'),
                NewInstance::stub('stubbles\webapp\routing\Interceptors'),
                new SupportedMimeTypes([]),
                new MatchingRoutes([], ['GET', 'POST', 'HEAD'])
        );
    }

    /**
     * @test
     */
    public function doesNotRequireSwitchToHttps()
    {
        assertFalse($this->resourceOptions->requiresHttps());
    }

    /**
     * @test
     */
    public function addsAllowHeader()
    {
        $response = NewInstance::of('stubbles\webapp\Response');
        $response->mapCalls(['addHeader' => $response]);

        $this->resourceOptions->resolve(
                NewInstance::of('stubbles\webapp\Request'),
                $response
        );
        assertEquals(
                ['Allow', 'GET, POST, HEAD, OPTIONS'],
                $response->argumentsReceived('addHeader', 1)
        );
    }

    /**
     * @test
     */
    public function addsAllowMethodsHeader()
    {
        $response = NewInstance::of('stubbles\webapp\Response');
        $response->mapCalls(['addHeader' => $response]);

        $this->resourceOptions->resolve(
                NewInstance::of('stubbles\webapp\Request'),
                $response
        );
        assertEquals(
                ['Access-Control-Allow-Methods', 'GET, POST, HEAD, OPTIONS'],
                $response->argumentsReceived('addHeader', 2)
        );
    }
}