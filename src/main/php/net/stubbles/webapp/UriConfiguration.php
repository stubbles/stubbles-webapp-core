<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  net\stubbles\webapp
 */
namespace net\stubbles\webapp;
use net\stubbles\lang\BaseObject;
use net\stubbles\lang\exception\IllegalArgumentException;
/**
 * Container which holds the uri configuration for the web app.
 *
 * @since  1.7.0
 */
class UriConfiguration extends BaseObject
{
    /**
     * fallback if none of the configured processors applies
     *
     * @type  string
     */
    private $defaultProcessor;
    /**
     * list of pre interceptors with their uri condition
     *
     * @type  array
     */
    private $preInterceptors  = array();
    /**
     * list of processors with their uri condition
     *
     * @type  array
     */
    private $processors       = array();
    /**
     * list of post interceptors with their uri condition
     *
     * @type  array
     */
    private $postInterceptors = array();

    /**
     * constructor
     *
     * @param  string  $defaultProcessor  fallback if none of the configured processors applies
     * @param  array   $preInterceptors   list of pre interceptors with their uri condition
     * @param  array   $processors        list of processors with their uri condition
     * @param  array   $postInterceptors  list of post interceptors with their uri condition
     */
    public function __construct($defaultProcessor,
                                array $preInterceptors,
                                array $processors,
                                array $postInterceptors)
    {
        $this->defaultProcessor = $defaultProcessor;
        $this->preInterceptors  = $preInterceptors;
        $this->processors       = $processors;
        $this->postInterceptors = $postInterceptors;
    }

    /**
     * returns class name list of pre interceptors applicable to called uri
     *
     * @param   UriRequest  $calledUri  current request uri
     * @return  string[]|Closure[]
     */
    public function getPreInterceptors(UriRequest $calledUri)
    {
        return $this->getApplicable($calledUri, $this->preInterceptors);
    }

    /**
     * returns processor applicable to called uri
     *
     * Only one processor will be applied. If there is more than one processor
     * which is configured with an url condition which satifies the called uri
     * only the first of them will be used.
     *
     * If no processor has a satisfieing uri condition the default processor will
     * be returned.
     *
     * @param   UriRequest  $calledUri  current request uri
     * @return  string|Closure
     */
    public function getProcessorForUri(UriRequest $calledUri)
    {
        foreach ($this->processors as $uriCondition => $processor) {
            if ($calledUri->satisfies($uriCondition)) {
                $calledUri->setProcessorUriCondition($uriCondition);
                return $processor;
            }
        }

        return $this->defaultProcessor;
    }

    /**
     * returns class name list of post interceptors applicable to called uri
     *
     * @param   UriRequest  $calledUri  current request uri
     * @return  string[]|Closure[]
     */
    public function getPostInterceptors(UriRequest $calledUri)
    {
        return $this->getApplicable($calledUri, $this->postInterceptors);
    }

    /**
     * calculates which interceptors are applicable for called uri based on uri condition
     *
     * @param   UriRequest  $calledUri     current request uri
     * @param   array       $interceptors  map of pre/post interceptor classes to check
     * @return  string[]|Closure[]
     */
    private function getApplicable(UriRequest $calledUri, array $interceptors)
    {
        $applicable = array();
        foreach ($interceptors as  $interceptor) {
            if ($calledUri->satisfies($interceptor['uriCondition'])) {
                $applicable[] = $interceptor['interceptor'];
            }
        }

        return $applicable;
    }
}
?>