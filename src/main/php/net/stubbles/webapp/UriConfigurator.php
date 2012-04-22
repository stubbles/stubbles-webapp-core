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
     * @type  string
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
     * @param   string  $defaultProcessor  class name of fallback processor
     */
    public function __construct($defaultProcessor)
    {
        $this->defaultProcessor = $defaultProcessor;
    }

    /**
     * static constructor, see constructor above
     *
     * @api
     * @param   string  $defaultProcessor  class name of fallback processor
     * @return  UriConfigurator
     */
    public static function create($defaultProcessor )
    {
        return new self($defaultProcessor);
    }

    /**
     * creates configuration with stubbles' xml processor as default
     *
     * @api
     * @return  UriConfigurator
     */
    public static function createWithXmlProcessorAsDefault()
    {
        return new self('net\\stubbles\\webapp\\xml\\XmlProcessor');
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
     * @param   string  $preInterceptorClassName  pre interceptor class to add
     * @param   string  $uriCondition             uri pattern under which interceptor should be executed
     * @return  UriConfigurator
     */
    public function preIntercept($preInterceptorClassName, $uriCondition = null)
    {
        $this->preInterceptors[$preInterceptorClassName] = $uriCondition;
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
     * @param   string  $processorClass  name of processor class
     * @param   string  $uriCondition    uri pattern under which interceptor should be executed
     * @return  UriConfigurator
     * @throws  IllegalArgumentException
     */
    public function process($processorClass, $uriCondition)
    {
        if (empty($uriCondition)) {
            throw new IllegalArgumentException('$uriCondition can not be empty.');
        }

        $this->processors[$uriCondition] = $processorClass;
        return $this;
    }

    /**
     * process requests with stubbles' xml/xsl view engine
     *
     * @api
     * @return  UriConfigurator
     */
    public function provideXml()
    {
        $this->process('net\\stubbles\\webapp\\xml\\XmlProcessor', '^/xml/');
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
     * @param   string  $postInterceptorClassName  post interceptor class to add
     * @param   string  $uriCondition              uri pattern under which post interceptor should be executed
     * @return  UriConfiguration
     */
    public function postIntercept($postInterceptorClassName, $uriCondition = null)
    {
        $this->postInterceptors[$postInterceptorClassName] = $uriCondition;
        return $this;
    }

    /**
     * adds etag post interceptor to uri configuration
     *
     * @api
     * @param   string  $uriCondition  uri pattern under which post interceptor should be executed
     * @return  UriConfigurator
     */
    #public function addEtagPostInterceptor($uriCondition = null)
    #{
    #    return $this->postIntercept('net\\stubbles\\webapp\\interceptor\\EtagPostInterceptor', $uriCondition);
    #}

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