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
namespace stubbles\webapp\request;
/**
 * Value object for user agents.
 *
 * @since  1.2.0
 * @XmlTag(tagName='userAgent')
 */
class UserAgent
{
    /**
     * name of user agent
     *
     * @type  string
     */
    private $name;
    /**
     * whether user agent is a bot or not
     *
     * @type  bool
     */
    private $isBot = null;
    /**
     * list of known bot user agents
     *
     * @type  array
     */
    private $botSignatures = [
            'google'       => '~Googlebot~',
            'msnbot'       => '~msnbot~',
            'bing'         => '~bingbot~',
            'bing preview' => '~BingPreview~',
            'slurp'        => '~Slurp~',
            'pingdom'      => '~pingdom~',
            'yandex'       => '~YandexBot~'
    ];
    /**
     * whether user agent accepts cookies or not
     *
     * @type  bool
     */
    private $acceptsCookies;

    /**
     * constructor
     *
     * @param  string    $name            name of user agent
     * @param  bool      $acceptsCookies  whether user agent accepts cookies or not
     * @param  string[]  $botSignatures   optional  additional list of bot user agent signatures
     */
    public function __construct($name, bool $acceptsCookies, array $botSignatures = [])
    {
        $this->name           = $name;
        $this->acceptsCookies = $acceptsCookies;
        $this->botSignatures  = array_merge($this->botSignatures, $botSignatures);
    }

    /**
     * returns name of user agent
     *
     * @XmlAttribute(attributeName='name')
     * @api
     * @return  string|null
     */
    public function name()
    {
        return $this->name;
    }

    /**
     * returns whether user agent is a bot or not
     *
     * @XmlAttribute(attributeName='isBot')
     * @api
     * @return  bool
     */
    public function isBot(): bool
    {
        if (null === $this->isBot) {
            $this->isBot = false;
            foreach ($this->botSignatures as $botSignature) {
                if (preg_match($botSignature, $this->name) === 1) {
                    $this->isBot = true;
                    break;
                }
            }
        }

        return $this->isBot;

    }

    /**
     * returns whether user agent accepts cookies or not
     *
     * @XmlAttribute(attributeName='acceptsCookies')
     * @api
     * @since   2.0.0
     * @return  bool
     */
    public function acceptsCookies(): bool
    {
        return $this->acceptsCookies;
    }

    /**
     * returns a string representation of the class
     *
     * @XmlIgnore
     * @return  string
     */
    public function __toString(): string
    {
        return (string) $this->name;
    }
}
