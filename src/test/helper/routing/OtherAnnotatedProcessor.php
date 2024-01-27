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
 * @RequiresRole('superadmin')
 * @DisableContentNegotiation
 */
class OtherAnnotatedProcessor implements Target
{
    /**
     * processes the request
     *
     * @param  \stubbles\webapp\Request   $request   current request
     * @param  \stubbles\webapp\Response  $response  response to send
     * @param  \stubbles\webapp\UriPath           $uriPath   information about called uri path
     */
    public function resolve(Request $request, Response $response, UriPath $uriPath): mixed
    {
        // intentionally empty
    }
}