<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace stubbles\webapp\routing;
use stubbles\peer\http\HttpUri;
use stubbles\webapp\Request;
use stubbles\webapp\Response;
use stubbles\webapp\Target;
use stubbles\webapp\UriPath;
/**
 * Provides possibility for simple redirects.
 *
 * @since  6.1.0
 * @ExcludeFromApiIndex
 * @DisableContentNegotiation
 */
class Redirect implements Target
{
    /**
     * @type  string
     */
    private $target;
    /**
     * @type  int
     */
    private $statusCode;

    /**
     * constructor
     *
     * If the given $target is a string it is used in different ways:
     * - if the string starts with http it is assumed to be a complete uri
     * - else it is assumed to be a path within the application
     *
     * @param   string|\stubbles\peer\http\HttpUri  $target      path or uri to redirect to
     * @param   int                                 $statusCode  status code for redirect
     * @throws  \InvalidArgumentException
     */
    public function __construct($target, int $statusCode)
    {
        if ($target instanceof HttpUri) {
            $this->target = $target;
        } elseif (is_string($target) && substr($target, 0, 4) === 'http') {
            $this->target = HttpUri::fromString($target);
        } elseif (is_string($target)) {
            $this->target = $target;
        } else {
            throw new \InvalidArgumentException(
                    'Given target must either be a string or an instance of stubbles\peer\http\HttpUri'
            );
        }

        $this->statusCode = $statusCode;
    }

    /**
     * resolves the request and returns resource data
     *
     * @param  \stubbles\webapp\Request   $request   current request
     * @param  \stubbles\webapp\Response  $response  response to send
     * @param  \stubbles\webapp\UriPath   $uriPath   information about called uri path
     */
    public function resolve(Request $request, Response $response, UriPath $uriPath)
    {
        if ($this->target instanceof HttpUri) {
            $targetUri = $this->target;
        } else {
            $targetUri = $request->uri()->withPath($this->target);
        }

        $response->redirect($targetUri, $this->statusCode);
    }
}
