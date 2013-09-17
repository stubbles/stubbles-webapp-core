<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  net\stubbles\webapp
 */
namespace net\stubbles\webapp\ioc;
use net\stubbles\lang;
use net\stubbles\peer\http\HttpUri;
/**
 * Tests for net\stubbles\webapp\ioc\IoBindingModule.
 *
 * @since  2.0.0
 * @group  ioc
 */
class RoutingProviderTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function createsRouting()
    {
        $mockRequest = $this->getMock('net\stubbles\input\web\WebRequest');
        $mockRequest->expects($this->once())
                    ->method('getUri')
                    ->will($this->returnValue(HttpUri::fromString('http://example.net/hello')));
        $mockRequest->expects($this->any())
                    ->method('getMethod')
                    ->will($this->returnValue('GET'));
        $routingProvider = new RoutingProvider($mockRequest,
                                               $this->getMockBuilder('net\stubbles\ioc\Injector')
                                                    ->disableOriginalConstructor()
                                                    ->getMock()
                           );
        $this->assertInstanceOf('net\stubbles\webapp\Routing',
                                $routingProvider->get()
        );
    }

    /**
     * @test
     */
    public function isDefaultProviderForRouting()
    {
        $refClass = lang\reflect('net\stubbles\webapp\Routing');
        $this->assertTrue($refClass->hasAnnotation('ProvidedBy'));
        $this->assertEquals('net\stubbles\webapp\ioc\RoutingProvider',
                            $refClass->getAnnotation('ProvidedBy')
                                     ->getValue()
                                     ->getName()
        );
    }
}
?>