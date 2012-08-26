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
 * Interface to configure which interceptors and processors should respond to which uri.
 *
 * @since  1.7.0
 */
class UriConfigurator extends BaseObject
{
    /**
     * fallback if none of the configured processors applies
     *
     * @type  string|Closure
     */
    private $defaultProcessor;
    /**
     * list of pre interceptors with their uri condition
     *
     * @type  array
     */
    private $preInterceptors   = array();
    /**
     * list of processor names with their uri condition
     *
     * @type  array
     */
    private $processors        = array();
    /**
     * list of post interceptors with their uri condition
     *
     * @type  array
     */
    private $postInterceptors  = array();
    /**
     * list of rest uri conditions and handlers
     *
     * @type  array
     */
    private $resourceHandler   = array();
    /**
     * list of allowed mime types for given uri condition
     *
     * @type  array
     */
    private $resourceMimeTypes = array();

    /**
     * constructor
     *
     * @param   string|Closure  $defaultProcessor  class name of fallback processor
     */
    public function __construct($defaultProcessor)
    {
        $this->defaultProcessor = $defaultProcessor;
    }

    /**
     * static constructor, see constructor above
     *
     * @api
     * @param   string|Closure  $defaultProcessor  class name of fallback processor
     * @return  UriConfigurator
     */
    public static function create($defaultProcessor )
    {
        return new self($defaultProcessor);
    }

    /**
     * creates configuration with stubbles' rest processor as default
     *
     * @api
     * @return  UriConfigurator
     */
    public static function createWithRestProcessorAsDefault()
    {
        return new self('net\\stubbles\\webapp\\rest\\RestProcessor');
    }

    /**
     * pre intercept request with given pre interceptor
     *
     * Adding the same pre interceptor class twice will overwrite the uri
     * condition set with the first registration.
     *
     * @api
     * @param   string|Closure  $preInterceptor  pre interceptor class to add
     * @param   string          $uriCondition    uri pattern under which interceptor should be executed
     * @return  UriConfigurator
     */
    public function preIntercept($preInterceptor, $uriCondition = null)
    {
        $this->preInterceptors[] = array('interceptor'  => $preInterceptor,
                                         'uriCondition' => $uriCondition
                                   );
        return $this;
    }

    /**
     * process request with given processor
     *
     * The uri condition must not be empty. If you want to configure a processor
     * which is called for all requests you should configure it as the default
     * processor.
     *
     * Adding a different processor for the same uri condition will overwrite
     * the first processor.
     *
     * @api
     * @param   string|Closure  $processor     name of processor class
     * @param   string          $uriCondition  uri pattern under which interceptor should be executed
     * @return  UriConfigurator
     * @throws  IllegalArgumentException
     */
    public function process($processor, $uriCondition)
    {
        if (empty($uriCondition)) {
            throw new IllegalArgumentException('$uriCondition can not be empty.');
        }

        $this->processors[$uriCondition] = $processor;
        return $this;
    }

    /**
     * adds resource handler for given uri condition
     *
     * @api
     * @param   string    $handlerClass      rest handler class to add for this uri pattern
     * @param   string    $uriCondition      uri pattern under which handler should be executed
     * @param   string[]  $allowedMimeTypes  list of allowed mime types, if not set all configured are allowed
     * @return  UriConfigurator
     * @throws  IllegalArgumentException
     */
    public function addResourceHandler($handlerClass, $uriCondition, array $allowedMimeTypes = array())
    {
        if (empty($uriCondition)) {
            throw new IllegalArgumentException('$uriCondition can not be empty.');
        }

        $this->process('net\\stubbles\\webapp\\rest\\RestProcessor', $uriCondition);
        $this->resourceHandler[$uriCondition]   = $handlerClass;
        $this->resourceMimeTypes[$uriCondition] = $allowedMimeTypes;
        return $this;
    }

    /**
     * returns list of rest uri conditions and handlers
     *
     * @return  array
     */
    public function getResourceHandler()
    {
        return $this->resourceHandler;
    }

    /**
     * returns list of rest uri conditions and handlers
     *
     * @return  array
     * @since   2.0.0
     */
    public function getResourceMimeTypes()
    {
        return $this->resourceMimeTypes;
    }

    /**
     * post intercept request with given post interceptor
     *
     * Adding the same post interceptor class twice will overwrite the uri
     * condition set with the first registration.
     *
     * @api
     * @param   string|Closure  $postInterceptor  post interceptor class to add
     * @param   string          $uriCondition     uri pattern under which post interceptor should be executed
     * @return  UriConfiguration
     */
    public function postIntercept($postInterceptor, $uriCondition = null)
    {
        $this->postInterceptors[] = array('interceptor'  => $postInterceptor,
                                          'uriCondition' => $uriCondition
                                    );
        return $this;
    }

    /**
     * returns finished configuration
     *
     * @return  UriConfiguration
     */
    public function getConfig()
    {
        return new UriConfiguration($this->defaultProcessor,
                                    $this->preInterceptors,
                                    $this->processors,
                                    $this->postInterceptors
        );
    }
}
?>