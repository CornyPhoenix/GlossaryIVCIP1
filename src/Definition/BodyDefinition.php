<?php

namespace CornyPhoenix\Component\Glossaries\Definition;

use CornyPhoenix\Component\Glossaries\Glossary;

/**
 * @package CornyPhoenix\Component\Glossaries\Definition
 * @author moellers
 */
class BodyDefinition extends Definition {

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
     * @param callable $callback
     *      With params:
     *          Definition $referencedDefinition
     *          string $originalText
     *          returns string.
     * @return string
     */
    public function getParsedBody(callable $callback)
    {
        return preg_replace_callback('/=>\s*(\w*)(\{([^}]+)\}|())(\[([^\]]+)\]|())/', function (array $matches) use ($callback) {
            list(, $prefix, , $curly, , , $quadratic) = $matches;
            $name = $prefix . $curly;
            $originalText = $prefix . $quadratic ?: $name;

            $def = $this->getGlossary()->getDefinition($name);
            if (null === $def) {
                Glossary::warn("No definition for \e[1m$name\e[m.");
                return $originalText;
            }

            return $callback($def, $originalText);
        }, $this->body);
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

    /**
     * {@inheritDoc}
     */
    public function getLaTeX()
    {
        $latexParser = function (Definition $ref, $text) {
            return sprintf('\glslink{%s}{%s\textbf{%s}}', $ref->getEscapedName(), ReferenceDefinition::SYMBOL, $text);
        };

        return $this->getParsedBody($latexParser);
    }

    /**
     * {@inheritDoc}
     */
    public function getMarkdown()
    {
        $latexParser = function (Definition $ref, $text) {
            return sprintf('[%s](%s)', $text, $ref->getEscapedName());
        };

        return $this->getName() . ' ' . $this->getParsedBody($latexParser);
    }
}
