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
use stubbles\img\Image as ImageSource;
use stubbles\values\ResourceLoader;
use stubbles\streams\OutputStream;
use stubbles\webapp\response\Error;
/**
 * Can handle images.
 *
 * @since  6.0.0
 */
class Image extends MimeType
{
    /**
     * @type  \stubbles\lang\ResourceLoader
     */
    private $resourceLoader;
    /**
     * image to be displayed in case of errors
     *
     * @type  string
     */
    private $errorImgResource;

    /**
     * constructor
     *
     * @param  \stubbles\values\ResourceLoader  $resourceLoader
     * @param  string                           $errorImgResource  optional  image to be displayed in case of errors
     * @Property{errorImgResource}('stubbles.img.error')
     */
    public function __construct(
            ResourceLoader $resourceLoader,
            $errorImgResource = 'pixel.png')
    {
        $this->resourceLoader   = $resourceLoader;
        $this->errorImgResource = $errorImgResource;
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
     * @param   mixed  $resource
     * @param   \stubbles\streams\OutputStream  $out
     * @return  \stubbles\streams\OutputStream
     */
    public function serialize($resource, OutputStream $out)
    {
        if ($resource instanceof Error) {
            $image = $this->loadImage($this->errorImgResource);
        } elseif (!($resource instanceof ImageSource)) {
            $image = $this->loadImage($resource);
        } else {
            $image = $resource;
        }

        if (!empty($image)) {
            // must use output buffering
            // PHP's image*() functions write directly to stdout
            ob_start();
            $image->display();
            $result = ob_get_contents();
            // end output buffering first before writing to output stream
            // because it might be captured by output buffering as well
            ob_end_clean();
            $out->write($result);
        }

        return $out;
    }

    /**
     * loads image from resource pathes
     *
     * @param   string  $resource
     * @return  \stubbles\img\Image
     */
    private function loadImage($resource)
    {
        try {
            return $this->resourceLoader->load(
                    $resource,
                    function($fileName) { return ImageSource::load($fileName); }
            );
        } catch (\Exception $e) {
            // not allowed to throw exceptions, as we are outside any catching
            // mechanism
            trigger_error(
                    'Can not load image "' . $resource . '": ' . $e->getMessage(),
                    E_USER_ERROR
            );
            return null;
        }
    }
}
