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
use stubbles\webapp\response\Error;
/**
 * Tests for stubbles\webapp\routing\NotFound.
 *
 * @since  2.2.0
 * @group  routing
 */
class NotFoundTest extends \PHPUnit_Framework_TestCase
{
    /**
     * instance to test
     *
     * @type  \stubbles\webapp\routing\NotFound
     */
    private $notFound;
    /**
     * mocked request instance
     *
     * @type  \PHPUnit_Framework_MockObject_MockObject
     */
    private $request;
    /**
     * mocked response instance
     *
     * @type  \PHPUnit_Framework_MockObject_MockObject
     */
    private $response;

    /**
     * set up test environment
     */
    public function setUp()
    {
        $this->notFound = new NotFound(
                $this->getMockBuilder('stubbles\ioc\Injector')
                        ->disableOriginalConstructor()
                        ->getMock(),
                new CalledUri('http://example.com/hello/world', 'GET'),
                $this->getMockBuilder('stubbles\webapp\routing\Interceptors')
                        ->disableOriginalConstructor()
                        ->getMock(),
                new SupportedMimeTypes([])
        );
        $this->request  = $this->getMock('stubbles\webapp\Request');
        $this->response = $this->getMock('stubbles\webapp\Response');
    }

    /**
     * @test
     */
    public function doesNotRequireSwitchToHttps()
    {
        assertFalse($this->notFound->requiresHttps());
    }

    /**
     * @test
     */
    public function triggers404NotFoundResponse()
    {
        $error = Error::notFound();
        $this->response->method('notFound')->will(returnValue($error));
        assertSame(
                $error,
                $this->notFound->resolve(
                        $this->request,
                        $this->response
                )
        );
    }
}