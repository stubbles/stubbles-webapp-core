<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace stubbles\webapp\routing;
use stubbles\ioc\Injector;
use stubbles\webapp\Request;
use stubbles\webapp\Response;
use stubbles\webapp\interceptor\PreInterceptor;
use stubbles\webapp\interceptor\PostInterceptor;
/**
 * Interceptor handler.
 */
class Interceptors
{
    /**
     * @param  array<class-string<PreInterceptor>|callable|PreInterceptor>    $preInterceptors
     * @param  array<class-string<PostInterceptor>|callable|PostInterceptor>  $postInterceptors
     */
    public function __construct(
        private Injector $injector,
        private array $preInterceptors,
        private array $postInterceptors
    ) { }

    /**
     * apply pre interceptors
     *
     * Returns false if one of the pre interceptors cancels the request.
     */
    public function preProcess(Request $request, Response $response): bool
    {
        foreach ($this->preInterceptors as $preInterceptor) {
            if (false === $this->executePreInterceptor($preInterceptor, $request, $response)) {
                return false;
            }
        }

        return true;
    }

    /**
     * executes pre interceptor
     *
     * @param  class-string<PreInterceptor>|callable|PreInterceptor  $preInterceptor
     */
    private function executePreInterceptor(
        string|callable|PreInterceptor $preInterceptor,
        Request $request,
        Response $response
    ): bool {
        if (is_callable($preInterceptor)) {
            return $preInterceptor($request, $response);
        }

        $instance = $this->instanceOf($preInterceptor, PreInterceptor::class);
        if (null === $instance) {
            $response->write(
                $response->internalServerError(
                    sprintf(
                        'Configured pre interceptor %s is not an instance of %s',
                        $preInterceptor,
                        PreInterceptor::class
                    )
                    
                )
            );
            return false;
        }

        return $instance->preProcess($request, $response);
    }

    /**
     * apply post interceptors
     */
    public function postProcess(Request $request, Response $response): bool
    {
        foreach ($this->postInterceptors as $postInterceptor) {
            if (false === $this->executePostInterceptor($postInterceptor, $request, $response)) {
                return false;
            }
        }

        return true;
    }

    /**
     * executes post interceptor
     *
     * @param  class-string<PostInterceptor>|callable|PostInterceptor  $postInterceptor
     */
    private function executePostInterceptor(
        string|callable|PostInterceptor $postInterceptor,
        Request $request,
        Response $response
    ): bool {
        if (is_callable($postInterceptor)) {
            return $postInterceptor($request, $response);
        }

        $instance = $this->instanceOf($postInterceptor, PostInterceptor::class);
        if (null === $instance) {
            $response->write(
                $response->internalServerError(
                    sprintf(
                        'Configured post interceptor %s is not an instance of %s',
                        $postInterceptor,
                        PostInterceptor::class
                    )
                )
            );
            return false;
        }

        return $instance->postProcess($request, $response);
    }

    private function instanceOf(
        string|PreInterceptor|PostInterceptor $interceptor,
        string $expectedClass
    ): null|PreInterceptor|PostInterceptor {
        if (!is_string($interceptor)) {
            return $interceptor;
        }

        $instance = $this->injector->getInstance($interceptor);
        if (!($instance instanceof $expectedClass)) {
            return null;
        }

        return $instance;
    }
}
