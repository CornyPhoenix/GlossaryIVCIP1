<?php

namespace CornyPhoenix\Component\Glossaries\Definition;

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
     * Definition constructor.
     * @param string $name
     * @param array $tags
     */
    public function __construct($name, array $tags)
    {
        $this->name = $name;
        $this->tags = $tags;
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
