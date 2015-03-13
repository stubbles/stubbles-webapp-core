<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  stubbles\webapp
 */
namespace stubbles\webapp\response\mimetypes;
use stubbles\img\Image;
use stubbles\lang\ResourceLoader;
use stubbles\streams\OutputStream;
/**
 * Serializes resources to application/json.
 *
 * @since  5.3.0
 */
class Image extends MimeType
{
    /**
     * @type  \stubbles\lang\ResourceLoader
     */
    private $resourceLoader;

    /**
     * constructor
     *
     * @param  \stubbles\lang\ResourceLoader  $resourceLoader
     * @Inject
     */
    public function __construct(ResourceLoader $resourceLoader)
    {
        $this->resourceLoader   = $resourceLoader;
    }

    /**
     * returns default mime type name
     *
     * @return  string
     */
    protected function defaultName()
    {
        return 'image/*';
    }

    /**
     * serializes resource to output stream
     *
     * @param  mixed  $resource
     * @param  \stubbles\streams\OutputStream $out
     */
    public function serialize($resource, OutputStream $out)
    {
        if (!($resource instanceof Image)) {
            $image = $this->resourceLoader->load(
                    $resource,
                    function($fileName) { return Image::load($fileName); }
            );
        } else {
            $image = $resource;
        }

        // must use output buffering
        // PHP's image*() functions write directly to stdout
        ob_start([$out, 'write']);
        $image->display();
        ob_end_clean();
    }
}
