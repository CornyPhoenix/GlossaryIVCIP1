<?php

namespace CornyPhoenix\Component\Glossaries\Definition;

/**
 * @package CornyPhoenix\Component\Glossaries\Definition
 * @author moellers
 */
class BodyDefinition extends Definition
{

    /**
     * @var string
     */
    private $body = '';

    /**
     * @return string
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * @param string $body
     * @return $this
     */
    public function setBody($body)
    {
        $this->body = $body;
        return $this;
    }

    /**
     * @param callable $callback
     *      With params:
     *          Definition $referencedDefinition
     *          string $originalText
     *          returns string.
     * @return string
     * @throws \Exception
     */
    public function getParsedBody(callable $callback)
    {
        $string = '';
        $offset = 0;
        $body = $this->body;
        while (($pos = strpos($body, '=>', $offset)) !== false) {
            $string .= substr($body, $offset, $pos - $offset);
            for ($i = $pos + 2; $i < strlen($body); $i++) {
                if ($body[$i] !== ' ') {
                    break;
                }
            }

            $link = '';
            $text = '';

            while (true) {
                $nextChar = $body[$i];
                switch ($nextChar) {
                    case '{':
                        $start = $i;
                        for (; $i < strlen($body); $i++) {
                            if ('}' === $body[$i]) {
                                $link .= substr($body, $start + 1, $i - $start - 1);
                                $i++;
                                break 2;
                            }
                        }
                        throw new \Exception('Syntax error.');
                        break;
                    case '[':
                        $start = $i;
                        for (; $i < strlen($body); $i++) {
                            if (']' === $body[$i]) {
                                $text .= substr($body, $start + 1, $i - $start - 1);
                                $i++;
                                break 2;
                            }
                        }
                        throw new \Exception('Syntax error.');
                        break;
                    case ' ':
                    case '.':
                    case ',':
                    case ';':
                    case '?':
                    case '!':
                    case '"':
                    case '*':
                    case '(':
                    case ')':
                    case ']':
                    case '}':
                    case "\t":
                    case "\n":
                    case "\r":
                        $pos = $i;
                        break 2;
                    default:
                        $link .= $nextChar;
                        $text .= $nextChar;
                        $i++;
                }
            }

            // If text is empty, use the Link.
            if (!$text) {
                $text = $link;
            }

            $def = $this->getGlossary()->getDefinition($link);
            if (null === $def) {
                $string .= $text;
            } else {
                $string .= $callback($def, $text);
            }
            $offset = $pos;
        }

        $string .= substr($body, $offset);

        return $string;
    }

    /**
     * @param string $body
     * @return $this
     */
    public function appendBody($body)
    {
        $this->body .= trim($body) . ' ';
        return $this;
    }

    /**
     * @return string
     */
    public function toString()
    {
        return "\n\t" . $this->wordWrap($this->body) . "\n";
    }

    /**
     * {@inheritDoc}
     */
    public function getLaTeX()
    {
        $latexParser = function (Definition $ref, $text) {
            return sprintf('\glslink{%s}{%s\textbf{%s}}', $ref->getEscapedName(), ReferenceDefinition::SYMBOL, $text);
        };

        $body = $this->getParsedBody($latexParser);

        // Format paragraphs.
        $body = str_replace('<p/>', '\\\\', $body);

        return preg_replace('/\*([^\*]+)\*/', '\textbf{$1}', $body);
    }

    /**
     * {@inheritDoc}
     */
    public function getMarkdown()
    {
        $latexParser = function (Definition $ref, $text) {
            return sprintf('[%s](%s)', $text, $ref->getEscapedName());
        };

        $body = $this->getParsedBody($latexParser);

        // Format equations.
        $body = preg_replace_callback('/\$([^\$]+)\$/', function (array $matches) {
            $equation = $matches[1];
            return '![' . $equation . '](https://latex.codecogs.com/gif.latex?' . rawurlencode($equation) . ')';
        }, $body);

        // Format paragraphs.
        $body = str_replace('<p/>', "\n\n", $body);

        return $this->getName() . ' ' . $body;
    }

    /**
     * @param string $text
     * @param int $width
     * @return string
     */
    private function wordWrap($text, $width = 80)
    {
        if (strlen($text) < $width) {
            return trim($text);
        }

        $rest = $text . "\n";
        $text = '';
        while (strlen($rest) > $width) {
            $i = $width;
            while (strpos(" \t\n\r\0\x0B", $rest[$i]) === false) {
                $i++;
            }

            $text .= substr($rest, 0, $i) . "\n\t";
            $rest = substr($rest, $i + 1);
        }

        return rtrim($text . $rest);
    }
}
