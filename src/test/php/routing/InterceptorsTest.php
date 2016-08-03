<?php
declare(strict_types=1);
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
use stubbles\ioc\Injector;
use stubbles\webapp\{Request, Response};
use stubbles\webapp\interceptor\{PreInterceptor, PostInterceptor};
use stubbles\webapp\response\Error;

use function bovigo\assert\assertFalse;
use function bovigo\assert\assertTrue;
use function bovigo\callmap\verify;
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
     * @type  \bovigo\callmap\Proxy
     */
    private $request;
    /**
     * mocked response instance
     *
     * @type  \bovigo\callmap\Proxy
     */
    private $response;
    /**
     * mocked injector instance
     *
     * @type  \bovigo\callmap\Proxy
     */
    private $injector;

    /**
     * set up test environment
     */
    public function setUp()
    {
        $this->request  = NewInstance::of(Request::class);
        $this->response = NewInstance::of(Response::class);
        $this->injector = NewInstance::stub(Injector::class);
    }

    private function createInterceptors(
            array $preInterceptors = [],
            array $postInterceptors = []
    ): Interceptors {
        return new Interceptors($this->injector, $preInterceptors, $postInterceptors);
    }

    public function callableMethod(Request $request, Response $response)
    {
        $response->addHeader('X-Binford', '6100 (More power!)');
    }

    /**
     * @test
     */
    public function respondsWithInternalServerErrorIfPreInterceptorDoesNotImplementInterface()
    {
        $this->response->mapCalls(['internalServerError' => Error::internalServerError('')]);
        $this->injector->mapCalls(['getInstance' => new \stdClass()]);
        assertFalse(
                $this->createInterceptors([
                        'some\PreInterceptor',
                        'other\PreInterceptor'

                ])->preProcess($this->request, $this->response)
        );
        verify($this->response, 'internalServerError')->received(
                'Configured pre interceptor some\PreInterceptor is not an instance of '
                . PreInterceptor::class
        );
        verify($this->response, 'write')->wasCalledOnce();
    }

    /**
     * @test
     */
    public function doesNotCallOtherPreInterceptorsIfOneReturnsFalse()
    {
        $preInterceptor = NewInstance::of(PreInterceptor::class);
        $preInterceptor->mapCalls(['preProcess' => false]);
        $this->injector->mapCalls(['getInstance' => $preInterceptor]);
        assertFalse(
                $this->createInterceptors([
                        'some\PreInterceptor',
                        'other\PreInterceptor'
                ])->preProcess($this->request, $this->response)
        );
        verify($preInterceptor, 'preProcess')->wasCalledOnce();
    }

    /**
     * @test
     */
    public function returnsTrueWhenNoPreInterceptorReturnsFalse()
    {
        $preInterceptor = NewInstance::of(PreInterceptor::class);
        $this->injector->mapCalls(['getInstance' => $preInterceptor]);
        assertTrue(
                $this->createInterceptors([
                        'some\PreInterceptor',
                        $preInterceptor,
                        function(Request $request, Response $response)
                        {
                            $response->setStatusCode(418);
                        },
                        [$this, 'callableMethod']
                ])->preProcess($this->request, $this->response)
        );
        verify($preInterceptor, 'preProcess')->wasCalled(2);
    }

    /**
     * @test
     */
    public function respondsWithInternalServerErrorIfPostInterceptorDoesNotImplementInterface()
    {
        $this->response->mapCalls(['internalServerError' => Error::internalServerError('')]);
        $this->injector->mapCalls(['getInstance' => new \stdClass()]);
        assertFalse(
                $this->createInterceptors(
                        [],
                        ['some\PostInterceptor',  'other\PostInterceptor']
                )->postProcess($this->request, $this->response)
        );
        verify($this->response, 'internalServerError')->received(
                'Configured post interceptor some\PostInterceptor is not an instance of '
                . PostInterceptor::class
        );
        verify($this->response, 'write')->wasCalledOnce();
    }

    /**
     * @test
     */
    public function doesNotCallOtherPostInterceptorsIfOneReturnsFalse()
    {
        $postInterceptor = NewInstance::of(PostInterceptor::class);
        $postInterceptor->mapCalls(['postProcess' => false]);
        $this->injector->mapCalls(['getInstance' => $postInterceptor]);
        assertFalse(
                $this->createInterceptors(
                        [],
                        ['some\PostInterceptor', 'other\PostInterceptor']
                )->postProcess($this->request, $this->response)
        );
        verify($postInterceptor, 'postProcess')->wasCalledOnce();
    }

    /**
     * @test
     */
    public function returnsTrueWhenNoPostInterceptorReturnsFalse()
    {
        $postInterceptor = NewInstance::of(PostInterceptor::class);
        $this->injector->mapCalls(['getInstance' => $postInterceptor]);
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
        verify($postInterceptor, 'postProcess')->wasCalled(2);
    }

}
