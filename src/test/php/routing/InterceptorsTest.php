<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace stubbles\webapp\routing;

use bovigo\callmap\ClassProxy;
use bovigo\callmap\NewInstance;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
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
 */
#[Group('routing')]
class InterceptorsTest extends TestCase
{
    private Request&ClassProxy $request;
    private Response&ClassProxy $response;
    private Injector&ClassProxy $injector;

    protected function setUp(): void
    {
        $this->request  = NewInstance::of(Request::class);
        $this->response = NewInstance::of(Response::class);
        $this->injector = NewInstance::stub(Injector::class);
    }

    /**
     * @param  mixed[]  $preInterceptors
     * @param  mixed[]  $postInterceptors
     */
    private function createInterceptors(
        array $preInterceptors = [],
        array $postInterceptors = []
    ): Interceptors {
        return new Interceptors($this->injector, $preInterceptors, $postInterceptors);
    }

    public function callableMethod(Request $request, Response $response): bool
    {
        $response->addHeader('X-Binford', '6100 (More power!)');
        return true;
    }

    #[Test]
    public function respondsWithInternalServerErrorIfPreInterceptorDoesNotImplementInterface(): void
    {
        $this->response->returns(['internalServerError' => Error::internalServerError('')]);
        $this->injector->returns(['getInstance' => new \stdClass()]);
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

    #[Test]
    public function doesNotCallOtherPreInterceptorsIfOneReturnsFalse(): void
    {
        $preInterceptor = NewInstance::of(PreInterceptor::class)
            ->returns(['preProcess' => false]);
        $this->injector->returns(['getInstance' => $preInterceptor]);
        assertFalse(
            $this->createInterceptors([
                'some\PreInterceptor',
                'other\PreInterceptor'
            ])->preProcess($this->request, $this->response)
        );
        verify($preInterceptor, 'preProcess')->wasCalledOnce();
    }

    #[Test]
    public function returnsTrueWhenNoPreInterceptorReturnsFalse(): void
    {
        $preInterceptor = NewInstance::of(PreInterceptor::class)
            ->returns(['preProcess' => true]);
        $this->injector->returns(['getInstance' => $preInterceptor]);
        assertTrue(
            $this->createInterceptors([
                'some\PreInterceptor',
                $preInterceptor,
                function(Request $request, Response $response): bool
                {
                    $response->setStatusCode(418);
                    return true;
                },
                [$this, 'callableMethod']
            ])->preProcess($this->request, $this->response)
        );
        verify($preInterceptor, 'preProcess')->wasCalled(2);
    }

    #[Test]
    public function respondsWithInternalServerErrorIfPostInterceptorDoesNotImplementInterface(): void
    {
        $this->response->returns(['internalServerError' => Error::internalServerError('')]);
        $this->injector->returns(['getInstance' => new \stdClass()]);
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

    #[Test]
    public function doesNotCallOtherPostInterceptorsIfOneReturnsFalse(): void
    {
        $postInterceptor = NewInstance::of(PostInterceptor::class)
            ->returns(['postProcess' => false]);
        $this->injector->returns(['getInstance' => $postInterceptor]);
        assertFalse(
            $this->createInterceptors(
                [],
                ['some\PostInterceptor', 'other\PostInterceptor']
            )->postProcess($this->request, $this->response)
        );
        verify($postInterceptor, 'postProcess')->wasCalledOnce();
    }

    #[Test]
    public function returnsTrueWhenNoPostInterceptorReturnsFalse(): void
    {
        $postInterceptor = NewInstance::of(PostInterceptor::class)
            ->returns(['postProcess' => true]);
        $this->injector->returns(['getInstance' => $postInterceptor]);
        assertTrue(
            $this->createInterceptors(
                [],
                [
                    'some\PostInterceptor',
                    $postInterceptor,
                    function(Request $request, Response $response): bool
                    {
                        $response->setStatusCode(418);
                        return true;
                    },
                    [$this, 'callableMethod']
                ]
            )->postProcess($this->request, $this->response)
        );
        verify($postInterceptor, 'postProcess')->wasCalled(2);
    }

}
