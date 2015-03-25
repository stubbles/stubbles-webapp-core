<?php
/**
 * This file is part of stubbles.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  stubbles\webapp
 */
namespace stubbles\webapp\response\mimetypes;
use stubbles\lang;
use stubbles\lang\Sequence;
use stubbles\streams\OutputStream;
use stubbles\webapp\response\Error;
/**
 * Can transform any iterable resource into csv format.
 */
class Csv extends MimeType
{
    /**
     * returns default mime type name
     *
     * @return  string
     */
    protected function defaultName()
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
    public function serialize($resource, OutputStream $out)
    {
        if (is_scalar($resource) || $resource instanceof Error) {
            $out->writeLine((string) $resource);
        } elseif (is_object($resource)) {
            $this->serializeIterable($this->castToArray($resource), $out);
        } elseif (is_array($resource) || $resource instanceof \Traversable) {
            $this->serializeIterable($resource, $out);
        } else {
            trigger_error(
                    'Resource of type ' . lang\getType($resource)
                    . ' can not be serialized to csv',
                    E_USER_ERROR
            );
        }

        return $out;
    }

    /**
     * serializes iterable to csv
     *
     * @param   iterable  $resource
     * @param   \stubbles\streams\OutputStream  $out
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
            foreach (Sequence::of($resource)->map([$this, 'castToArray']) as $elements) {
                if ($head && !is_numeric(key($elements))) {
                    $out->write($this->toCsvLine(array_keys($elements), $memory));
                }

                $head = false;
                $out->write($this->toCsvLine($elements, $memory));
            }
        }

        fclose($memory);
    }

    function castToArray($value)
    {
        if (is_object($value)) {
            if (method_exists($value, 'asArray')) {
                return $value->asArray();
            } elseif (method_exists($value, 'toArray')) {
                return $value->toArray();
            }

            return lang\extractObjectProperties($value);
        } elseif ($value instanceof \Traversable) {
            return iterator_to_array($value);
        } elseif (is_array($value)) {
            return $value;
        }

        return [$value];
    }

    /**
     * turns given list of elements into a line suitable for csv
     *
     * @param   string[]  $elements
     * @param   resource  $memory
     * @return  string
     */
    private function toCsvLine(array $elements, $memory)
    {
        ftruncate($memory, 0);
        rewind($memory);
        fputcsv($memory, $elements);
        rewind($memory);
        return stream_get_contents($memory);
    }
}
