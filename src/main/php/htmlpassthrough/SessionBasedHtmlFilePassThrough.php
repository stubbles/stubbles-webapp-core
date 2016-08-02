<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  stubbles\webapp
 */
namespace stubbles\webapp\htmlpassthrough;
use stubbles\webapp\Request;
use stubbles\webapp\Response;
/**
 * Processor to pass through hole HTML pages to ensure session ids are passed in links.
 *
 * @since  4.0.0
 */
class SessionBasedHtmlFilePassThrough extends HtmlFilePassThrough
{
    /**
     * hook to modify the content before passing it to the response
     *
     * @param  \stubbles\webapp\Request   $request   current request
     * @param  \stubbles\webapp\Response  $response  response to send
     * @param   string                    $content    actual content for response
     * @param   string                    $routeName  name of the route
     * @return  string
     */
    protected function modifyContent(
            Request $request,
            Response $response,
            string $content,
            string $routeName
    ): string {
        if ($request->hasSessionAttached()) {
            $request->attachedSession()->putValue('stubbles.webapp.lastPage', $routeName);
        }

        if (!$request->userAgent()->acceptsCookies() && $request->hasSessionAttached()) {
            $session = $request->attachedSession();
            output_add_rewrite_var($session->name(), $session->id());
        }

        return $content;
    }
}
