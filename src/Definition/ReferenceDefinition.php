<?php

namespace CornyPhoenix\Component\Glossaries\Definition;

/**
 * @package CornyPhoenix\Component\Glossaries\Definition
 * @author moellers
 */
class ReferenceDefinition extends Definition {

    const IDENTIFIER = '=>';

    /**
     * @var string
     */
    private $references;

    /**
     * Definition constructor.
     * @param string $name
     * @param array $tags
     * @param string $references
     */
    public function __construct($name, $tags, $references)
    {
        parent::__construct($name, $tags);
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
        return '\textit{\seename} \gls{' . self::escape($this->getReferences()) . '}';
    }

    /**
     * @return string
     */
    public function toString()
    {
        return self::IDENTIFIER . ' ' . $this->references;
    }
}
