<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  stubbles\webapp
 */
namespace stubbles\webapp\processor;
use stubbles\webapp\Target;
use stubbles\webapp\Request;
use stubbles\webapp\Response;
use stubbles\webapp\UriPath;
/**
 * Processor to pass through hole HTML pages.
 *
 * @since  4.0.0
 */
class HtmlFilePassThrough implements Target
{
    /**
     * path to html files
     *
     * @type  string
     */
    private $routePath;

    /**
     * constructor
     *
     * @param  string  $routePath  path to html files
     * @Inject
     * @Named('stubbles.pages.path')
     */
    public function __construct($routePath)
    {
        $this->routePath = $routePath . DIRECTORY_SEPARATOR;
    }

    /**
     * processes the request
     *
     * @param   \stubbles\webapp\Request   $request   current request
     * @param   \stubbles\webapp\Response  $response  response to send
     * @param   \stubbles\webapp\UriPath   $uriPath   information about called uri path
     * @return  string|\stubbles\webapp\response\Error
     */
    public function resolve(Request $request, Response $response, UriPath $uriPath)
    {
        $routeName = $uriPath->remaining('index.html');
        if (!file_exists($this->routePath . $routeName)) {
            return $response->notFound();
        }

        return $this->modifyContent(
                $request,
                $response,
                file_get_contents($this->routePath . $routeName),
                $routeName
        );
    }

    /**
     * hook to modify the content before passing it to the response
     *
     * @param  \stubbles\webapp\Request   $request   current request
     * @param  \stubbles\webapp\Response  $response  response to send
     * @param   string                    $content    actual content for response
     * @param   string                    $routeName  name of the route
     * @return  string
     */
    protected function modifyContent(Request $request, Response $response, $content, $routeName)
    {
        return $content;
    }
}
