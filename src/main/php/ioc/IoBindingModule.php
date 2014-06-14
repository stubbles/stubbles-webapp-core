<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  stubbles\webapp
 */
namespace stubbles\webapp\ioc;
use stubbles\input\web\BaseWebRequest;
use stubbles\ioc\Binder;
use stubbles\ioc\module\BindingModule;
use stubbles\lang\exception\RuntimeException;
use stubbles\webapp\response\ResponseNegotiator;
/**
 * Module to configure the binder with instances for request, session and response.
 *
 * @since  1.7.0
 */
class IoBindingModule implements BindingModule
{
    /**
     * response class to be used
     *
     * @type  string
     */
    private $responseClass  = 'stubbles\webapp\response\WebResponse';
    /**
     * map of formatters for mime types
     *
     * @type  array
     */
    private $formatter      = ['application/json'    => 'stubbles\webapp\response\format\JsonFormatter',
                               'text/json'           => 'stubbles\webapp\response\format\JsonFormatter',
                               'text/html'           => 'stubbles\webapp\response\format\HtmlFormatter',
                               'text/plain'          => 'stubbles\webapp\response\format\PlainTextFormatter'
                              ];
    /**
     * map of xml formatters for mime types
     *
     * @var  array
     */
    private $xmlFormatter   = ['text/xml'            => 'stubbles\webapp\response\format\XmlFormatter',
                               'application/xml'     => 'stubbles\webapp\response\format\XmlFormatter',
                               'application/rss+xml' => 'stubbles\webapp\response\format\XmlFormatter'
                              ];
    /**
     * function that creates the session instance
     *
     * @type  callable
     */
    private $sessionCreator;

    /**
     * constructor
     *
     * The optional callable $sessionCreator can accept instances of
     * stubbles\input\web\WebRequest and stubbles\webapp\response\Response, and
     * must return an instance of stubbles\webapp\session\Session:
     * <code>
     * function(WebRequest $request, Response $response)
     * {
     *    return new MySession($request, $response);
     * }
     * </code>
     *
     * @param   callable  $sessionCreator  optional
     * @throws  RuntimeException  in case a session creator is passed and stubbles/webapp-session is not available
     */
    public function __construct(callable $sessionCreator = null)
    {
        if (null != $sessionCreator && !function_exists('stubbles\webapp\session\bind')) {
            throw new RuntimeException('Passed a session creator, but function stubbles\webapp\session\bind() can not be found. Did you install stubbles/webapp-session?');
        }

        $this->sessionCreator = $sessionCreator;
    }

    /**
     * factory method
     *
     * @return  IoBindingModule
     * @deprecated  since 4.0.0, use constructor directly, will be removed with 5.0.0
     */
    public static function createWithoutSession()
    {
        return new self();
    }

    /**
     * sets class name of response class to be used
     *
     * @param   string  $responseClass  name of response class to bind
     * @return  IoBindingModule
     * @since   1.1.0
     */
    public function setResponseClass($responseClass)
    {
        $this->responseClass = $responseClass;
        return $this;
    }

    /**
     * adds formatter class for given mime type
     *
     * @param   string  $mimeType   mime type that should be handled by given formatter class
     * @param   string  $formatter  class to handle given mime type
     * @return  IoBindingModule
     */
    public function addFormatter($mimeType, $formatter)
    {
        $this->formatter[$mimeType] = $formatter;
        return $this;
    }

    /**
     * set a callable which can create a session instance
     *
     * @param   callable  $sessionCreator  callable which creates session instance
     * @return  IoBindingModule
     * @since   2.0.0
     * @deprecated  since 4.0.0, pass the callable to the constructor instead, will be removed with 5.0.0
     */
    public function setSessionCreator(callable $sessionCreator)
    {
        $this->sessionCreator = $sessionCreator;
        return $this;
    }

    /**
     * configure the binder
     *
     * @param  Binder  $binder
     */
    public function configure(Binder $binder)
    {
        $request  = BaseWebRequest::fromRawSource();
        $response = ResponseNegotiator::negotiateHttpVersion($request, $this->responseClass);
        $binder->bind('stubbles\input\web\WebRequest')
               ->toInstance($request);
        $binder->bind('stubbles\input\Request')
               ->toInstance($request);
        $binder->bind('stubbles\webapp\response\Response')
               ->toInstance($response);
        $formatters = $this->getAvailableFormatters();
        $binder->bindConstant('stubbles.webapp.response.format.mimetypes')
               ->to(array_keys($formatters));
        foreach ($formatters as $mimeType => $formatter) {
            $binder->bind('stubbles\webapp\response\format\Formatter')
                   ->named($mimeType)
                   ->to($formatter);
        }

        if (null !== $this->sessionCreator) {
            $sessionCreator = $this->sessionCreator;
            \stubbles\webapp\session\bind(
                    $binder,
                    $sessionCreator($request, $response)
            );
        }
    }

    /**
     * returns map of available formatters
     *
     * @return  array
     */
    private function getAvailableFormatters()
    {
        $formatter = $this->formatter;
        foreach ($this->xmlFormatter as $mimeType => $xmlFormatter) {
            if (!isset($formatter[$mimeType]) && class_exists('stubbles\xml\serializer\XmlSerializerFacade')) {
                $formatter[$mimeType] = $xmlFormatter;
            }
        }

        return $formatter;
    }
}
