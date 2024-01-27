<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
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
    private ?bool $isBot = null;
    /**
     * list of known bot user agents
     *
     * @var  array<string,string>
     */
    private array $botSignatures = [
        'google'       => '~Googlebot~',
        'msnbot'       => '~msnbot~',
        'bing'         => '~bingbot~',
        'bing preview' => '~BingPreview~',
        'slurp'        => '~Slurp~',
        'pingdom'      => '~pingdom~',
        'yandex'       => '~YandexBot~'
    ];

    /**
     * constructor
     *
     * @param  string|null           $name            name of user agent
     * @param  bool                  $acceptsCookies  whether user agent accepts cookies or not
     * @param  array<string,string>  $botSignatures   optional  additional list of bot user agent signatures
     */
    public function __construct(
        private ?string $name,
        private bool $acceptsCookies,
        array $botSignatures = []
    ) {
        $this->botSignatures  = array_merge($this->botSignatures, $botSignatures);
    }

    /**
     * returns name of user agent
     *
     * @XmlAttribute(attributeName='name')
     * @api
     */
    public function name(): ?string
    {
        return $this->name;
    }

    /**
     * returns whether user agent is a bot or not
     *
     * @XmlAttribute(attributeName='isBot')
     * @api
     */
    public function isBot(): bool
    {
        if (null === $this->name) {
            $this->isBot = false;
        } elseif (null === $this->isBot) {
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
     */
    public function acceptsCookies(): bool
    {
        return $this->acceptsCookies;
    }

    /**
     * @XmlIgnore
     */
    public function __toString(): string
    {
        return (string) $this->name;
    }
}
