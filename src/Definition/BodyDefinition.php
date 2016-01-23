<?php

namespace CornyPhoenix\Component\Glossaries\Definition;

/**
 * @package CornyPhoenix\Component\Glossaries\Definition
 * @author moellers
 */
class BodyDefinition extends Definition {

    /**
     * @var string
     */
    private $body;

    /**
     * BodyDefinition constructor.
     * @param string $name
     * @param array $tags
     */
    public function __construct($name, array $tags)
    {
        parent::__construct($name, $tags);
        $this->body = '';
    }

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
        return "\n\t" . $this->wordWrap($this->body);
    }

    /**
     * @param string $text
     * @param int $width
     * @return string
     */
    private function wordWrap($text, $width = 80)
    {
        if (strlen($text) < $width) {
            return $text . "\n";
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

        return $text . $rest;
    }
}
