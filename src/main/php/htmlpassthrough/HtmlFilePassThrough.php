<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace stubbles\webapp\htmlpassthrough;
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
     * @var  string
     */
    private $routePath;

    /**
     * constructor
     *
     * @param  string  $routePath  path to html files
     * @Named('stubbles.pages.path')
     */
    public function __construct(string $routePath)
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

        $fileContents = @file_get_contents($this->routePath . $routeName);
        if (false === $fileContents) {
            return $response->internalServerError('Could not read contents of ' . $routeName);
        }

        return $this->modifyContent(
                $request,
                $response,
                $fileContents,
                $routeName
        );
    }

    /**
     * hook to modify the content before passing it to the response
     *
     * @param   \stubbles\webapp\Request   $request   current request
     * @param   \stubbles\webapp\Response  $response  response to send
     * @param   string                     $content    actual content for response
     * @param   string                     $routeName  name of the route
     * @return  string
     */
    protected function modifyContent(
            Request $request,
            Response $response,
            string $content,
            string $routeName
    ): string {
        return $content;
    }
}
