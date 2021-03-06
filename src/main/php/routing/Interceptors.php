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
     * list of global pre interceptors and to which request method they respond
     *
     * @var  array<class-string<PreInterceptor>|callable|PreInterceptor>
     */
    private $preInterceptors;
    /**
     * list of global post interceptors and to which request method they respond
     *
     * @var  array<class-string<PostInterceptor>|callable|PostInterceptor>
     */
    private $postInterceptors;
    /**
     * injector instance
     *
     * @var  \stubbles\ioc\Injector
     */
    private $injector;

    /**
     * constructor
     *
     * @param  \stubbles\ioc\Injector                                         $injector
     * @param  array<class-string<PreInterceptor>|callable|PreInterceptor>    $preInterceptors
     * @param  array<class-string<PostInterceptor>|callable|PostInterceptor>  $postInterceptors
     */
    public function __construct(Injector $injector, array $preInterceptors, array $postInterceptors)
    {
        $this->injector         = $injector;
        $this->preInterceptors  = $preInterceptors;
        $this->postInterceptors = $postInterceptors;
    }

    /**
     * apply pre interceptors
     *
     * Returns false if one of the pre interceptors cancels the request.
     *
     * @param   \stubbles\webapp\Request   $request   current request
     * @param   \stubbles\webapp\Response  $response  response to send
     * @return  bool
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
     * @param   class-string<PreInterceptor>|callable|PreInterceptor  $preInterceptor
     * @param   \stubbles\webapp\Request                              $request         current request
     * @param   \stubbles\webapp\Response                             $response        response to send
     * @return  bool|null
     */
    private function executePreInterceptor($preInterceptor, Request $request, Response $response)
    {
        if (is_callable($preInterceptor)) {
            return $preInterceptor($request, $response);
        }

        if ($preInterceptor instanceof PreInterceptor) {
            return $preInterceptor->preProcess($request, $response);
        }

        $instance = $this->injector->getInstance($preInterceptor);
        if (!($instance instanceof PreInterceptor)) {
            $response->write(
                    $response->internalServerError(
                            'Configured pre interceptor ' . $preInterceptor
                            . ' is not an instance of ' . PreInterceptor::class
                    )
            );
            return false;
        }

        return $instance->preProcess($request, $response);
    }

    /**
     * apply post interceptors
     *
     * @param   \stubbles\webapp\Request   $request   current request
     * @param   \stubbles\webapp\Response  $response  response to send
     * @return  bool
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
     * @param   class-string<PostInterceptor>|callable|PostInterceptor  $postInterceptor
     * @param   \stubbles\webapp\Request                                $request          current request
     * @param   \stubbles\webapp\Response                               $response         response to send
     * @return  bool|null
     */
    private function executePostInterceptor($postInterceptor, Request $request, Response $response)
    {
        if (is_callable($postInterceptor)) {
            return $postInterceptor($request, $response);
        }

        if ($postInterceptor instanceof PostInterceptor) {
            return $postInterceptor->postProcess($request, $response);
        }

        $instance = $this->injector->getInstance($postInterceptor);
        if (!($instance instanceof PostInterceptor)) {
            $response->write(
                    $response->internalServerError(
                            'Configured post interceptor ' . $postInterceptor
                            . ' is not an instance of ' . PostInterceptor::class
                    )
            );
            return false;
        }

        return $instance->postProcess($request, $response);
    }
}
