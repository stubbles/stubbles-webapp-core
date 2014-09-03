<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  stubbles\webapp
 */
namespace stubbles\webapp\response;
use stubbles\peer\http\AcceptHeader;
/**
 * Handles list of supported mime types.
 *
 * @since  2.2.0
 */
class SupportedMimeTypes
{
    /**
     * list of supported mime types
     *
     * @type  string[]
     */
    private $mimeTypes;
    /**
     * whether content negotation is disabled or not
     *
     * @type  bool
     */
    private $disableContentNegotation = false;
    /**
     * map of formatters for mime types
     *
     * @type  array
     */
    private static $supported = ['application/json'    => 'stubbles\webapp\response\format\JsonFormatter',
                                 'text/json'           => 'stubbles\webapp\response\format\JsonFormatter',
                                 'text/html'           => 'stubbles\webapp\response\format\HtmlFormatter',
                                 'text/plain'          => 'stubbles\webapp\response\format\PlainTextFormatter'
                                 ];
    /**
     * map of xml formatters for mime types
     *
     * @var  array
     */
    private static $xmlFormatter = ['text/xml'            => 'stubbles\webapp\response\format\XmlFormatter',
                                    'application/xml'     => 'stubbles\webapp\response\format\XmlFormatter',
                                    'application/rss+xml' => 'stubbles\webapp\response\format\XmlFormatter'
                                   ];
    /**
     * map of formatters for mime types
     *
     * @type  array
     */
    private $formatter = [];

    /**
     * constructor
     *
     * @param  string[]  $mimeTypes
     * @param  array     $formatter
     */
    public function __construct(array $mimeTypes, array $formatter = [])
    {
        $this->mimeTypes = $mimeTypes;
        $this->formatter = array_merge(self::$supported, $formatter);
    }

    /**
     * creates instance which denotes that content negotation is disabled
     *
     * @return  \stubbles\webapp\response\SupportedMimeTypes
     */
    public static function createWithDisabledContentNegotation()
    {
        $self = new self([]);
        $self->disableContentNegotation = true;
        return $self;
    }

    /**
     * checks whether content negotation is disabled
     *
     * @return  bool
     */
    public function isContentNegotationDisabled()
    {
        return $this->disableContentNegotation;
    }

    /**
     * finds best matching mime type based on accept header
     *
     * @param   \stubbles\peer\http\AcceptHeader  $acceptedMimeTypes
     * @return  string
     */
    public function findMatch(AcceptHeader $acceptedMimeTypes)
    {
        if (count($this->mimeTypes) === 0) {
            return 'text/html';
        }

        if (count($acceptedMimeTypes) === 0) {
            reset($this->mimeTypes);
            return current($this->mimeTypes);
        }

        return $acceptedMimeTypes->findMatchWithGreatestPriority($this->mimeTypes);
    }

    /**
     * checks if a default formatter is known for the given mime type
     *
     * @param   string  $mimeType
     * @return  bool
     * @since   5.0.0
     */
    public static function provideDefaultFormatterFor($mimeType)
    {
        if (in_array($mimeType, array_keys(self::$supported))) {
            return true;
        }

        if (class_exists('stubbles\xml\serializer\XmlSerializerFacade') && in_array($mimeType, array_keys(self::$xmlFormatter))) {
            return true;
        }

        return false;
    }

    /**
     * checks if a special formatter was defined for given mime type
     *
     * @param   string  $mimeType
     * @return  bool
     * @since   3.2.0
     */
    public function provideFormatter($mimeType)
    {
        if (isset($this->formatter[$mimeType])) {
            return true;
        }

        if (class_exists('stubbles\xml\serializer\XmlSerializerFacade')) {
            return isset(self::$xmlFormatter[$mimeType]);
        }

        return false;
    }

    /**
     * returns special formatter was defined for given mime type or null if none defined
     *
     * @param   string  $mimeType
     * @return  string
     * @since   3.2.0
     */
    public function formatterFor($mimeType)
    {
        if ($this->provideFormatter($mimeType)) {
            return $this->formatter[$mimeType];
        }

        return null;
    }

    /**
     * returns supported mime types as list of strings
     *
     * @return  string[]
     */
    public function asArray()
    {
        return $this->mimeTypes;
    }
}