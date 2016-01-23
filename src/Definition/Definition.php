<?php

namespace CornyPhoenix\Component\Glossaries\Definition;

use CornyPhoenix\Component\Glossaries\Glossary;

abstract class Definition {

    /**
     * @var string
     */
    private $name;

    /**
     * @var string[]
     */
    private $tags;

    /**
     * @var Glossary
     */
    private $glossary;

    /**
     * Definition constructor.
     * @param Glossary $glossary
     * @param string $name
     * @param array $tags
     */
    public function __construct(Glossary $glossary, $name, array $tags)
    {
        $this->name = $name;
        $this->tags = $tags;
        $this->glossary = $glossary;
    }

    /**
     * @param $name
     * @return string
     */
    protected static function escape($name)
    {
        return preg_replace('#[^a-z]+#', '-', strtolower($name));
    }

    /**
     * @return Glossary
     */
    public function getGlossary()
    {
        return $this->glossary;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string[]
     */
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * @return string
     */
    public function getEscapedName()
    {
        return self::escape($this->name);
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->toString();
    }

    /**
     * @return string
     */
    abstract public function toString();
}
