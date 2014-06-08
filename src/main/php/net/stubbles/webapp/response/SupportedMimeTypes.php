<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  net\stubbles\webapp
 */
namespace net\stubbles\webapp\response;
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
     * map of formatters
     *
     * @type  array
     */
    private $formatter;

    /**
     * constructor
     *
     * @param  string[]  $mimeTypes
     * @param  array     $formatter
     */
    public function __construct(array $mimeTypes, array $formatter = array())
    {
        $this->mimeTypes = $mimeTypes;
        $this->formatter = $formatter;
    }

    /**
     * creates instance which denotes that content negotation is disabled
     *
     * @return  SupportedMimeTypes
     */
    public static function createWithDisabledContentNegotation()
    {
        $self = new self(array());
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
     * @param   AcceptHeader  $acceptedMimeTypes
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
     * checks if a special formatter was defined for given mime type
     *
     * @param   string  $mimeType
     * @return  bool
     * @since   3.2.0
     */
    public function hasFormatter($mimeType)
    {
        return isset($this->formatter[$mimeType]);
    }

    /**
     * returns special formatter was defined for given mime type or null if none defined
     *
     * @param   string  $mimeType
     * @return  string
     * @since   3.2.0
     */
    public function getFormatter($mimeType)
    {
        if ($this->hasFormatter($mimeType)) {
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