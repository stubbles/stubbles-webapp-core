<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  stubbles\webapp
 */
namespace stubbles\webapp\routing;
use stubbles\peer\http\AcceptHeader;
use stubbles\webapp\response\mimetypes\Csv;
use stubbles\webapp\response\mimetypes\Image;
use stubbles\webapp\response\mimetypes\Json;
use stubbles\webapp\response\mimetypes\PassThrough;
use stubbles\webapp\response\mimetypes\TextPlain;
use stubbles\webapp\response\mimetypes\Xml;
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
     * map of mime types classes
     *
     * @type  array
     */
    private static $supported = ['application/json' => Json::class,
                                 'text/json'        => Json::class,
                                 'text/plain'       => TextPlain::class,
                                 'text/html'        => PassThrough::class,
                                 'text/csv'         => Csv::class
                                ];
    /**
     * map of xml mime type classes
     *
     * @type  array
     */
    private static $xml = ['text/xml'            => Xml::class,
                           'application/xml'     => Xml::class,
                           'application/rss+xml' => Xml::class
                          ];
    /**
     * map of image mime type classes
     *
     * @type  array
     */
    private static $image = ['image/png' => Image::class];
    /**
     * map of available mime types classes
     *
     * @type  array
     */
    private $mimeTypeClasses = [];

    /**
     * constructor
     *
     * @param  string[]  $mimeTypes
     * @param  array     $mimeTypeClasses
     */
    public function __construct(array $mimeTypes, array $mimeTypeClasses = [])
    {
        $this->mimeTypes       = $mimeTypes;
        $this->mimeTypeClasses = array_merge(self::$supported, $mimeTypeClasses);
        if (class_exists('stubbles\xml\serializer\XmlSerializerFacade')) {
            $this->mimeTypeClasses = array_merge(self::$xml, $this->mimeTypeClasses);
        }

        if (class_exists('stubbles\img\Image')) {
            $this->mimeTypeClasses = array_merge(self::$image, $this->mimeTypeClasses);
        }
    }

    /**
     * creates instance which denotes that content negotation is disabled
     *
     * @return  \stubbles\webapp\routing\SupportedMimeTypes
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
     * sets a default mime type class for given mime type
     *
     * @param  string  $mimeType
     * @param  string  $mimeTypeClass
     * @since  5.1.1
     */
    public static function setDefaultMimeTypeClass($mimeType, $mimeTypeClass)
    {
        self::$supported[$mimeType] = $mimeTypeClass;
    }

    /**
     * removes default mime type class for given mime type
     *
     * @param  string  $mimeType
     * @since  5.1.1
     */
    public static function removeDefaultMimeTypeClass($mimeType)
    {
        if (isset(self::$supported[$mimeType])) {
            unset(self::$supported[$mimeType]);
        }
    }

    /**
     * checks if a default class is known for the given mime type
     *
     * @param   string  $mimeType
     * @return  bool
     * @since   5.0.0
     */
    public static function provideDefaultClassFor($mimeType)
    {
        if (in_array($mimeType, array_keys(self::$supported))) {
            return true;
        }

        if (class_exists('stubbles\xml\serializer\XmlSerializerFacade') && in_array($mimeType, array_keys(self::$xml))) {
            return true;
        }

        if (class_exists('stubbles\img\Image') && in_array($mimeType, array_keys(self::$image))) {
            return true;
        }

        return false;
    }

    /**
     * checks if a special class was defined for given mime type
     *
     * @param   string  $mimeType
     * @return  bool
     * @since   3.2.0
     */
    public function provideClass($mimeType)
    {
        return isset($this->mimeTypeClasses[$mimeType]);
    }

    /**
     * returns special class which was defined for given mime type or null if none defined
     *
     * @param   string  $mimeType
     * @return  string
     * @since   3.2.0
     */
    public function classFor($mimeType)
    {
        if ($this->provideClass($mimeType)) {
            return $this->mimeTypeClasses[$mimeType];
        }

        return null;
    }

    /**
     * returns list of supported mime types
     *
     * @return  string[]
     */
    public function asArray()
    {
        return $this->mimeTypes;
    }
}