<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  stubbles\webapp
 */
namespace stubbles\webapp\response\format;
use stubbles\webapp\response\Headers;
/**
 * Formats resource as HTML.
 *
 * @since  2.0.0
 */
class HtmlFormatter implements Formatter
{
    /**
     * template to be used for output
     *
     * @type  string
     */
    private $template = '<!DOCTYPE html><html><head><title>{TITLE}</title>{META}</head><body>{CONTENT}</body></html>';
    /**
     * base title of web application
     *
     * @type  string
     */
    private $title;

    /**
     * constructor
     *
     * @param   string  $template  optional  template to be used for output
     * @param   string  $title     optional  base title of web application
     * @Inject
     * @Named{template}('stubbles.webapp.response.format.html.template')
     * @Named{title}('stubbles.webapp.response.format.html.title')
     */
    public function __construct($template = null, $title = null)
    {
        if (null !== $template) {
            $this->template = $template;
        }

        $this->title = $title;
    }

    /**
     * prepend title with base title
     *
     * @param   string  $title
     * @return  string
     */
    private function prependTitle($title)
    {
        if (null == $this->title) {
            return $title;
        }

        return $this->title . ' ' . $title;
    }

    /**
     * parses given map of data into template
     *
     * @param   array  $data
     * @return  string
     */
    private function parse(array $data)
    {
        if (!isset($data['meta'])) {
            $data['meta'] = '';
        }

        return str_replace(array_map(function($key)
                                     {
                                         return '{' . strtoupper($key) . '}';
                                     },
                                     array_keys($data)
                           ),
                           array_values($data),
                           $this->template
        );
    }

    /**
     * formats resource for response
     *
     * @param   mixed                              $resource  resource data to create a representation of
     * @param   \stubbles\webapp\response\Headers  $headers   list of headers for the response
     * @return  string
     */
    public function format($resource, Headers $headers)
    {
        if (is_array($resource)) {
            if (isset($resource['title'])) {
                $resource['title'] = $this->prependTitle($resource['title']);
            } else {
                $resource['title'] = $this->title;
            }

            return $this->parse($resource);
        }

        return $this->parse(['title'   => $this->title,
                             'content' => (string) $resource
                            ]
        );
    }

    /**
     * write error message about 403 Forbidden error
     *
     * @return  string
     */
    public function formatForbiddenError()
    {
        return $this->parse(['title'   => '403 Forbidden',
                             'meta'    => '<meta name="robots" content="noindex"/>',
                             'content' => '<h1>403 Forbidden</h1><p>You are not allowed to access this resource.</p>'
                            ]
        );
    }

    /**
     * write error message about 404 Not Found error
     *
     * @return  string
     */
    public function formatNotFoundError()
    {
        return $this->parse(['title'   => '404 Not Found',
                             'meta'    => '<meta name="robots" content="noindex"/>',
                             'content' => '<h1>404 Not Found</h1><p>The requested resource could not be found.</p>'
                            ]
        );
    }

    /**
     * write error message about 405 Method Not Allowed error
     *
     * @param   string    $requestMethod   original request method
     * @param   string[]  $allowedMethods  list of allowed methods
     * @return  string
     */
    public function formatMethodNotAllowedError($requestMethod, array $allowedMethods)
    {
        return $this->parse(['title'   => '405 Method Not Allowed',
                             'meta'    => '<meta name="robots" content="noindex"/>',
                             'content' => '<h1>405 Method Not Allowed</h1><p>The given request method ' . strtoupper($requestMethod) . ' is not valid. Please use one of ' . join(', ', $allowedMethods) . '.</p>'
                            ]
        );
    }

    /**
     * write error message about 500 Internal Server error
     *
     * @param   string  $message  error messsage to display
     * @return  string
     */
    public function formatInternalServerError($message)
    {
        return $this->parse(['title'   => '500 Internal Server Error',
                             'meta'    => '<meta name="robots" content="noindex"/>',
                             'content' => '<h1>500 Internal Server Error</h1><p>' . $message . '</p>'
                            ]
        );
    }
}
