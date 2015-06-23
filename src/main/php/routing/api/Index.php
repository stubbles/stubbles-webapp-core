<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  stubbles\webapp
 */
namespace stubbles\webapp\routing\api;
use stubbles\webapp\Request;
use stubbles\webapp\Response;
use stubbles\webapp\Target;
use stubbles\webapp\UriPath;
use stubbles\webapp\routing\Routes;
/**
 * Provides an index over all available resources.
 *
 * @since  6.1.0
 * @Name('Index')
 * @Description('List of available resources.')
 * @SupportsMimeType(mimeType='application/xml')
 * @SupportsMimeType(mimeType='application/json')
 */
class Index implements Target
{
    /**
     * @type  \stubbles\webapp\routing\Routes
     */
    private $routes;
    /**
     * @type  string[]
     */
    private $globalMimeTypes;

    /**
     * constructor
     *
     * @param  \stubbles\webapp\routing\Routes  $routes           list of available routes
     * @param  string[]                         $globalMimeTypes  list of globally supported mime types
     */
    public function __construct(Routes $routes, array $globalMimeTypes)
    {
        $this->routes          = $routes;
        $this->globalMimeTypes = $globalMimeTypes;
    }

    /**
     * resolves the request and returns resource data
     *
     * @param   \stubbles\webapp\Request   $request   current request
     * @param   \stubbles\webapp\Response  $response  response to send
     * @param   \stubbles\webapp\UriPath   $uriPath   information about called uri path
     * @return  \stubbles\webapp\routing\api\Resources
     */
    public function resolve(Request $request, Response $response, UriPath $uriPath)
    {
        $uri = $request->uri();
        $resources = new Resources();
        foreach ($this->routes as $route) {
            /* @var $route \stubbles\webapp\routing\Route */
            if (!$route->shouldBeIgnoredInApiIndex()) {
                $resources->add($route->asResource($uri, $this->globalMimeTypes));
            }
        }

        return $resources;
    }
}

