<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  stubbles\webapp
 */
namespace stubbles\webapp\response\format;
use stubbles\xml\serializer\XmlSerializerFacade;
/**
 * Formats resource in XML.
 *
 * The XML formatter uses the XML serializer provided by Stubbles XML. This
 * allows to customize XML serialization of result objects with annotations from
 * the XML serializer package.
 *
 * @since  1.1.0
 */
class XmlFormatter implements Formatter
{
    /**
     * serializer to be used
     *
     * @type  XmlSerializerFacade
     */
    private $xmlSerializerFacade;

    /**
     * constructor
     *
     * @param  XmlSerializerFacade  $xmlSerializerFacade
     * @Inject
     */
    public function __construct(XmlSerializerFacade $xmlSerializerFacade)
    {
        $this->xmlSerializerFacade = $xmlSerializerFacade;
    }

    /**
     * formats resource for response
     *
     * @param   mixed   $resource
     * @return  string
     */
    public function format($resource)
    {
        return $this->xmlSerializerFacade->serializeToXml($resource);
    }

    /**
     * write error message about 403 Forbidden error
     *
     * @return  string
     */
    public function formatForbiddenError()
    {
        return $this->xmlSerializerFacade->serializeToXml(['error' => 'You are not allowed to access this resource.']);
    }

    /**
     * write error message about 404 Not Found error
     *
     * @return  string
     */
    public function formatNotFoundError()
    {
        return $this->xmlSerializerFacade->serializeToXml(['error' => 'Given resource could not be found.']);
    }

    /**
     * write error message about 405 Method Not Allowed error
     *
     * @param   string    $requestMethod   original request method
     * @param   string[]  $allowedMethods  list of allowed methods
     * @return  string
     */
    public function formatMethodNotAllowedError($requestMethod, array $allowedMethods)
    {
        return $this->xmlSerializerFacade->serializeToXml(['error' => 'The given request method ' . strtoupper($requestMethod) . ' is not valid. Please use one of ' . join(', ', $allowedMethods) . '.']);
    }

    /**
     * write error message about 500 Internal Server error
     *
     * @param   string  $message
     * @return  string
     */
    public function formatInternalServerError($message)
    {
        return $this->xmlSerializerFacade->serializeToXml(['error' => 'Internal Server Error: ' . $message]);
    }
}
