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
     * {@inheritDoc}
     */
    public function toString()
    {
        return self::IDENTIFIER;
    }

    /**
     * {@inheritDoc}
     */
    public function getMarkdown()
    {
        return sprintf('_There is no content for %s yet!_', $this->getName());
    }
}
