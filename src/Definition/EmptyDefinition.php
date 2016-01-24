<?php

namespace CornyPhoenix\Component\Glossaries\Definition;

/**
 * @package CornyPhoenix\Component\Glossaries\Definition
 * @author moellers
 */
class EmptyDefinition extends Definition
{

    const IDENTIFIER = '-';

    /**
     * @return string
     */
    public function toString()
    {
        return '';
    }

    /**
     * @return string
     */
    public function getMarkdown()
    {
        return sprintf('_There is no content for %s yet!_', $this->getName());
    }

    /**
     * @return string
     */
    public function getLaTeX()
    {
        return '\ding{55}';
    }

    /**
     * @return string
     */
    public function getPrefix()
    {
        return self::IDENTIFIER;
    }
}
