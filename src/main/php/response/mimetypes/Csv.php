<?php
declare(strict_types=1);
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace stubbles\webapp\response\mimetypes;
use stubbles\sequence\Sequence;
use stubbles\streams\OutputStream;
use stubbles\webapp\response\Error;

use function stubbles\sequence\castToArray;
use function stubbles\values\typeOf;
/**
 * Can transform any iterable resource into csv format.
 */
class Csv extends MimeType
{
    /**
     * delimiter to be used for csv
     *
     * @type  char
     */
    private $delimiter = ',';
    /**
     * character to enclose single fields with in csv
     *
     * @type  char
     */
    private $enclosure = '"';

    /**
     * allows to change the delimiter character
     *
     * @param   char  $delimiter
     * @return  \stubbles\webapp\response\mimetypes\Csv
     */
    public function changeDelimiterTo(string $delimiter): self
    {
        $this->delimiter = $delimiter;
        return $this;
    }

    /**
     * allows to change the enclosure character
     *
     * @param   char  $enclosure
     * @return  \stubbles\webapp\response\mimetypes\Csv
     */
    public function changeEnclosureTo(string $enclosure): self
    {
        $this->enclosure = $enclosure;
        return $this;
    }

    /**
     * returns default mime type name
     *
     * @return  string
     */
    protected function defaultName(): string
    {
        return 'text/csv';
    }

    /**
     * serializes resource to output stream
     *
     * @param   mixed  $resource
     * @param   \stubbles\streams\OutputStream  $out
     * @return  \stubbles\streams\OutputStream
     */
    public function serialize($resource, OutputStream $out): OutputStream
    {
        if (is_scalar($resource) || $resource instanceof Error) {
            $out->writeLine((string) $resource);
        } elseif (is_array($resource) || $resource instanceof \Traversable) {
            $this->serializeIterable($resource, $out);
        } elseif (is_object($resource)) {
            $this->serializeIterable(castToArray($resource), $out);
        } else {
            trigger_error(
                    'Resource of type ' . typeOf($resource)
                    . ' can not be serialized to csv',
                    E_USER_ERROR
            );
        }

        return $out;
    }

    /**
     * serializes iterable to csv
     *
     * @param  iterable  $resource
     * @param  \stubbles\streams\OutputStream  $out
     */
    private function serializeIterable($resource, OutputStream $out)
    {
        $memory = fopen('php://memory', 'wb');
        if (is_array($resource) && is_scalar(current($resource))) {
            if (!is_numeric(key($resource))) {
                $out->write($this->toCsvLine(array_keys($resource), $memory));
            }

            $out->write($this->toCsvLine($resource, $memory));
        } else {
            $head = true;
            foreach (Sequence::of($resource)->map('stubbles\sequence\castToArray') as $elements) {
                if ($head && !is_numeric(key($elements))) {
                    $out->write($this->toCsvLine(array_keys($elements), $memory));
                }

                $head = false;
                $out->write($this->toCsvLine($elements, $memory));
            }
        }

        fclose($memory);
    }

    /**
     * turns given list of elements into a line suitable for csv
     *
     * @param   string[]  $elements
     * @param   resource  $memory
     * @return  string
     */
    private function toCsvLine(array $elements, $memory): string
    {
        ftruncate($memory, 0);
        rewind($memory);
        fputcsv($memory, $elements, $this->delimiter, $this->enclosure);
        rewind($memory);
        return stream_get_contents($memory);
    }
}
