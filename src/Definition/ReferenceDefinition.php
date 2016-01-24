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
     * @param string $references
     */
    public function __construct(Glossary $glossary, $name, $references)
    {
        parent::__construct($glossary, $name);
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
    public function getLaTeX()
    {
        $ref = $this->getReferences();
        return sprintf('\textit{\seename} \glslink{%s}{%s\textbf{%s}}', self::escape($ref), self::SYMBOL, $ref);
    }

    /**
     * @return string
     */
    public function getMarkdown()
    {
        return sprintf('_See_ %s', $this->getReference()->getMarkdownLink());
    }

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
    public function getPrefix()
    {
        return self::IDENTIFIER . ' ' . $this->references;
    }

    /**
     * @return Definition
     */
    public function getReference()
    {
        return $this->getGlossary()->getDefinition($this->references);
    }
}
