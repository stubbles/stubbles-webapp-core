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
use stubbles\webapp\Request;
use stubbles\webapp\Response;
/**
 * Tests for stubbles\webapp\routing\Interceptors.
 *
 * @since  2.2.0
 * @group  routing
 */
class InterceptorsTest extends \PHPUnit_Framework_TestCase
{
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
     * mocked injector instance
     *
     * @type  \PHPUnit_Framework_MockObject_MockObject
     */
    private $injector;

    /**
     * set up test environment
     */
    public function setUp()
    {
        $this->request  = $this->getMock('stubbles\webapp\Request');
        $this->response = $this->getMock('stubbles\webapp\Response');
        $this->injector = $this->getMockBuilder('stubbles\ioc\Injector')
                ->disableOriginalConstructor()
                ->getMock();
    }

    /**
     * creates instance to test
     *
     * @param   array         $preInterceptors
     * @param   array         $postInterceptors
     * @return  Interceptors
     */
    private function createInterceptors(array $preInterceptors = [], array $postInterceptors = [])
    {
        return new Interceptors($this->injector, $preInterceptors, $postInterceptors);
    }

    /**
     * a callback
     *
     * @param  \stubbles\webapp\Request   $request
     * @param  \stubbles\webapp\Response  $response
     */
    public function callableMethod(Request $request, Response $response)
    {
        $response->addHeader('X-Binford', '6100 (More power!)');
    }

    /**
     * @test
     */
    public function respondsWithInternalServerErrorIfPreInterceptorDoesNotImplementInterface()
    {
        $this->injector->method('getInstance')
                ->with(equalTo('some\PreInterceptor'))
                ->will(returnValue(new \stdClass()));
        $this->response->expects(once())->method('internalServerError');
        $this->response->expects(once())->method('write');
        assertFalse(
                $this->createInterceptors(
                        ['some\PreInterceptor',
                         'other\PreInterceptor'
                        ]
                )->preProcess($this->request, $this->response)
        );
    }

    /**
     * @test
     */
    public function doesNotCallOtherPreInterceptorsIfOneReturnsFalse()
    {
        $preInterceptor = $this->getMock('stubbles\webapp\interceptor\PreInterceptor');
        $preInterceptor->method('preProcess')
                ->with(equalTo($this->request), equalTo($this->response))
                ->will(returnValue(false));
        $this->injector->method('getInstance')
                ->with(equalTo('some\PreInterceptor'))
                ->will(returnValue($preInterceptor));
        assertFalse(
                $this->createInterceptors(
                        ['some\PreInterceptor',
                         'other\PreInterceptor'
                        ]
                )->preProcess($this->request, $this->response)
        );
    }

    /**
     * @test
     */
    public function returnsTrueWhenNoPreInterceptorReturnsFalse()
    {
        $preInterceptor = $this->getMock('stubbles\webapp\interceptor\PreInterceptor');
        $preInterceptor->expects(exactly(2))
                ->method('preProcess')
                ->with(equalTo($this->request), equalTo($this->response));
        $this->injector->method('getInstance')
                ->with(equalTo('some\PreInterceptor'))
                ->will(returnValue($preInterceptor));
        $this->response->expects(once())->method('setStatusCode');
        $this->response->expects(once())->method('addHeader');
        assertTrue(
                $this->createInterceptors(
                        ['some\PreInterceptor',
                         $preInterceptor,
                         function(Request $request, Response $response)
                         {
                             $response->setStatusCode(418);
                         },
                         [$this, 'callableMethod']
                        ]
                )->preProcess($this->request, $this->response)
        );
    }

    /**
     * @test
     */
    public function respondsWithInternalServerErrorIfPostInterceptorDoesNotImplementInterface()
    {
        $this->injector->method('getInstance')
                ->with(equalTo('some\PostInterceptor'))
                ->will(returnValue(new \stdClass()));
        $this->response->expects(once())->method('internalServerError');
        $this->response->expects(once())->method('write');
        assertFalse(
                $this->createInterceptors(
                        [],
                        ['some\PostInterceptor',  'other\PostInterceptor']
                )->postProcess($this->request, $this->response)
        );
    }

    /**
     * @test
     */
    public function doesNotCallOtherPostInterceptorsIfOneReturnsFalse()
    {
        $postInterceptor = $this->getMock('stubbles\webapp\interceptor\PostInterceptor');
        $postInterceptor->expects(once())
                ->method('postProcess')
                ->with(equalTo($this->request), equalTo($this->response))
                ->will(returnValue(false));
        $this->injector->method('getInstance')
                ->with(equalTo('some\PostInterceptor'))
                ->will(returnValue($postInterceptor));
        assertFalse(
                $this->createInterceptors(
                        [],
                        ['some\PostInterceptor', 'other\PostInterceptor']
                )->postProcess($this->request, $this->response)
        );
    }

    /**
     * @test
     */
    public function returnsTrueWhenNoPostInterceptorReturnsFalse()
    {
        $postInterceptor = $this->getMock('stubbles\webapp\interceptor\PostInterceptor');
        $postInterceptor->expects(exactly(2))
                ->method('postProcess')
                ->with(equalTo($this->request), equalTo($this->response));
        $this->injector->method('getInstance')
                ->with(equalTo('some\PostInterceptor'))
                ->will(returnValue($postInterceptor));
        $this->response->expects(once())->method('setStatusCode');
        $this->response->expects(once())->method('addHeader');
        assertTrue(
                $this->createInterceptors(
                        [],
                        ['some\PostInterceptor',
                         $postInterceptor,
                         function(Request $request, Response $response)
                         {
                             $response->setStatusCode(418);
                         },
                         [$this, 'callableMethod']
                        ]
                )->postProcess($this->request, $this->response)
        );
    }

}
