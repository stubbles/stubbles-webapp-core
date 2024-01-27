<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace stubbles\helper\routing;
use stubbles\webapp\{Request, Response, Target, UriPath};
/**
 * Class with annotations for tests.
 *
 * @RequiresHttps
 * @RequiresLogin
 * @Name('Orders')
 * @Description('List of placed orders')
 * @SupportsMimeType(mimeType="text/plain")
 * @SupportsMimeType(mimeType="application/bar", class="example\\Bar")
 * @SupportsMimeType(mimeType="application/baz", class=stubbles\helper\routing\Baz.class)
 * @Status(code=200, description='Default status code')
 * @Status(code=404, description='No orders found')
 * @Parameter(name='foo', in='path', description='Some path parameter', required=true)
 * @Parameter(name='bar', in='query', description='A query parameter')
 * @Header(name='Last-Modified', description='Some explanation')
 * @Header(name='X-Binford', description='More power!')
 */
class AnnotatedProcessor implements Target
{
    /**
     * processes the request
     *
     * @param  \stubbles\webapp\Request   $request   current request
     * @param  \stubbles\webapp\Response  $response  response to send
     * @param  \stubbles\webapp\UriPath   $uriPath   information about called uri path
     */
    public function resolve(Request $request, Response $response, UriPath $uriPath): mixed
    {
        // intentionally empty
    }
}