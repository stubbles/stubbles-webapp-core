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
 * Can transform any traversable into csv format.
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
        if (is_scalar($resource) || ($resource instanceof Error)) {
            $out->writeLine((string) $resource);
        } elseif (is_array($resource) || $resource instanceof \Traversable) {
            $head   = true;
            $memory = fopen('php://memory', 'wb');
            foreach (Sequence::of($resource)->map([$this, 'castToArray']) as $elements) {
                if ($head && !is_numeric(key($elements))) {
                    $out->writeLine($this->toCsvLine($elements, $memory));
                }

                $head = false;
                $out->writeLine($this->toCsvLine($elements, $memory));
            }

            fclose($memory);
        } else {
            trigger_error(
                    'Resource of type ' . lang\getType($resource)
                    . ' can not be serialized to csv',
                    E_USER_ERROR
            );
        }

        return $out;
    }

    function castToArray($elements)
    {
        if (is_object($elements)) {
            if (method_exists($elements, 'asArray')) {
                return $elements->asArray();
            } elseif (method_exists($elements, 'toArray')) {
                return $elements->toArray();
            }

            return lang\extractObjectProperties($elements);
        } elseif ($elements instanceof \Traversable) {
            return iterator_to_array($elements);
        } elseif (is_array($elements)) {
            return $elements;
        }

        return [$elements];
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
        ftruncate($memory);
        rewind($memory);
        fputcsv($memory, $elements);
        rewind($memory);
        return stream_get_contents($memory);
    }
}
