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
     * If the given $target is a string it is used in different ways:
     * - if the string starts with http it is assumed to be a complete uri
     * - else it is assumed to be a path within the application
     *
     * @param  string|HttpUri  $target      path or uri to redirect to
     * @param  int             $statusCode  status code for redirect
     */
    public function __construct(
        private string|HttpUri $target,
        private int $statusCode)
    { }

    public function resolve(Request $request, Response $response, UriPath $uriPath): null
    {
        if ($this->target instanceof HttpUri) {
            $targetUri = $this->target;
        } else {
            $targetUri = $request->uri()->withPath($this->target);
        }

        $response->redirect($targetUri, $this->statusCode);
        return null;
    }
}
