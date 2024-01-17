<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace stubbles\webapp\response\mimetypes;
use stubbles\img\Image as ImageSource;
use stubbles\values\ResourceLoader;
use stubbles\streams\OutputStream;
use stubbles\webapp\response\Error;
use Throwable;

/**
 * Can handle images.
 *
 * @since  6.0.0
 */
class Image extends MimeType
{
    /**
     * @var  \stubbles\values\ResourceLoader
     */
    private $resourceLoader;
    /**
     * image to be displayed in case of errors
     *
     * @var  string
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
            string $errorImgResource = 'pixel.png'
    ) {
        $this->resourceLoader   = $resourceLoader;
        $this->errorImgResource = $errorImgResource;
    }

    /**
     * returns default mime type name
     *
     * @return  string
     */
    protected function defaultName(): string
    {
        return 'image/*';
    }

    /**
     * serializes resource to output stream
     */
    public function serialize(mixed $resource, OutputStream $out): OutputStream
    {
        if (null === $resource) {
            return $out;
        }

        if ($resource instanceof Error) {
            $image = $this->loadImage($this->errorImgResource);
        } elseif (!($resource instanceof ImageSource)) {
            $image = $this->loadImage($resource);
        } else {
            $image = $resource;
        }

        if (!empty($image)) {
            $out->write($image->contentForDisplay());
        }

        return $out;
    }

    private function loadImage(string $resource): ?ImageSource
    {
        try {
            return $this->resourceLoader->loadWith(
                $resource,
                fn(string $fileName): ImageSource => ImageSource::load($fileName)
            );
        } catch (Throwable $t) {
            // not allowed to throw exceptions, as we are outside any catching
            // mechanism
            trigger_error(
                sprintf(
                    'Can not load image "%s": %s',
                    $resource,
                    $t->getMessage()
                ),
                E_USER_ERROR
            );
            return null;
        }
    }
}
