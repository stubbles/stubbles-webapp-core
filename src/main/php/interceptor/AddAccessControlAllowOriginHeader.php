<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace stubbles\webapp\interceptor;
use stubbles\webapp\Request;
use stubbles\webapp\Response;
/**
 * Allows to add a Access-Control-Allow-Origin header to the response.
 *
 * A header is added if the ORIGIN header in the request matches a list of
 * allowed origin hosts which can be configured in the project's properties with
 * key stubbles.webapp.origin.hosts. The following example allows origin
 * hosts to be any subdomain of example.com with any port:
 * <code>
 * stubbles.webapp.origin.hosts = "^http://[a-zA-Z0-9-\.]+example\.com(:[0-9]{4})?$"
 * </code>
 *
 * If you need more than one expression, you can separate them with |:
 * <code>
 * stubbles.webapp.origin.hosts = "^http://example\.com$|^http://example\.net$"
 * </code>
 *
 * If no origin hosts are configured no header will be added.
 *
 * @since  3.4.0
 */
class AddAccessControlAllowOriginHeader implements PostInterceptor
{
    /**
     * list of allowed origin hosts
     *
     * @var  string[]
     */
    private $allowedOriginHosts;

    /**
     * constructor
     *
     * @param   string|string[]  $allowedOriginHosts
     * @Property('stubbles.webapp.origin.hosts')
     */
    public function __construct($allowedOriginHosts)
    {
        if (is_string($allowedOriginHosts)) {
            $this->allowedOriginHosts = explode('|', $allowedOriginHosts);
        } else {
            $this->allowedOriginHosts = $allowedOriginHosts;
        }
    }

    /**
     * does the postprocessing stuff
     *
     * @param  \stubbles\webapp\Request   $request   current request
     * @param  \stubbles\webapp\Response  $response  response to send
     */
    public function postProcess(Request $request, Response $response): void
    {
        if (empty($this->allowedOriginHosts) || !$request->hasHeader('HTTP_ORIGIN')) {
            return;
        }

        $originHost = $request->readHeader('HTTP_ORIGIN')->unsecure();
        if (empty($originHost)) {
            return;
        }

        foreach ($this->allowedOriginHosts as $allowedOriginHost) {
            if (\preg_match('~' . $allowedOriginHost . '~', $originHost) === 1) {
                $response->addHeader('Access-Control-Allow-Origin', $originHost);
            }
        }
    }
}
