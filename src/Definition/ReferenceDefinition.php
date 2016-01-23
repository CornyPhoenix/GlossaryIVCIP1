<?php

namespace CornyPhoenix\Component\Glossaries\Definition;

use CornyPhoenix\Component\Glossaries\Glossary;

/**
 * @package CornyPhoenix\Component\Glossaries\Definition
 * @author moellers
 */
class ReferenceDefinition extends Definition {

    const IDENTIFIER = '=>';
    const SYMBOL = '\ding{222}~';

    /**
     * @var string
     */
    private $references;

    /**
     * Definition constructor.
     * @param Glossary $glossary
     * @param string $name
     * @param array $tags
     * @param string $references
     */
    public function __construct(Glossary $glossary, $name, $tags, $references)
    {
        parent::__construct($glossary, $name, $tags);
        $this->references = $references;
    }

    /**
     * @return string
     */
    public function getReferences()
    {
        return $this->references;
    }

    /**
     * {@inheritDoc}
     */
    public function getDescription()
    {
        $ref = $this->getReferences();
        return sprintf('\textit{\seename} \glslink{%s}{%s\textbf{%s}}', self::escape($ref), self::SYMBOL, $ref);
    }

    /**
     * @return string
     */
    public function toString()
    {
        return self::IDENTIFIER . ' ' . $this->references;
    }
}
