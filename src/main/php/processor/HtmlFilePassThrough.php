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
use stubbles\input\web\WebRequest;
use stubbles\webapp\Processor;
use stubbles\webapp\UriPath;
use stubbles\webapp\response\Response;
/**
 * Processor to pass through hole HTML pages.
 *
 * @since  4.0.0
 */
class HtmlFilePassThrough implements Processor
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
        $this->routePath = $routePath;
    }

    /**
     * processes the request
     *
     * @param  \stubbles\input\web\WebRequest      $request   current request
     * @param  \stubbles\webapp\response\Response  $response  response to send
     * @param  \stubbles\webapp\UriPath            $uriPath   information about called uri path
     */
    public function process(WebRequest $request, Response $response, UriPath $uriPath)
    {
        $routeName = $uriPath->remaining('index.html');
        if (!file_exists($this->routePath . DIRECTORY_SEPARATOR . $routeName)) {
            $response->notFound();
            return;
        }

        $response->write($this->modifyContent(file_get_contents($this->routePath . DIRECTORY_SEPARATOR . $routeName), $routeName));
    }

    /**
     * hook to modify the content before passing it to the response
     *
     * @param   string  $content    actual content for response
     * @param   string  $routeName  name of the route
     * @return  string
     */
    protected function modifyContent($content, $routeName)
    {
        return $content;
    }
}
