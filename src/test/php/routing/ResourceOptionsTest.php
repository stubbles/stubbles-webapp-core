<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace stubbles\webapp\routing;
use bovigo\callmap\NewInstance;
use PHPUnit\Framework\TestCase;
use stubbles\ioc\Injector;
use stubbles\webapp\{Request, Response};

use function bovigo\assert\assertFalse;
use function bovigo\callmap\verify;
/**
 * Tests for stubbles\webapp\routing\ResourceOptions.
 *
 * @since  2.2.0
 * @group  routing
 */
class ResourceOptionsTest extends TestCase
{
    /**
     * instance to test
     *
     * @type  \stubbles\webapp\routing\ResourceOptions
     */
    private $resourceOptions;

    protected function setUp(): void
    {
        $this->resourceOptions = new ResourceOptions(
                NewInstance::stub(Injector::class),
                new CalledUri('http://example.com/hello/world', 'GET'),
                NewInstance::stub(Interceptors::class),
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
        $response = NewInstance::of(Response::class);
        $this->resourceOptions->resolve(
                NewInstance::of(Request::class),
                $response
        );
        verify($response, 'addHeader')
                ->received('Allow', 'GET, POST, HEAD, OPTIONS');
    }

    /**
     * @test
     */
    public function addsAllowMethodsHeader()
    {
        $response = NewInstance::of(Response::class);
        $this->resourceOptions->resolve(
                NewInstance::of(Request::class),
                $response
        );
        verify($response, 'addHeader')->receivedOn(
                2,
                'Access-Control-Allow-Methods',
                'GET, POST, HEAD, OPTIONS'
        );
    }
}
