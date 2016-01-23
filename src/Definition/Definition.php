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
     * @param string $subject
     * @return string
     */
    protected static function escape($subject)
    {
        $map = [
            'ä' => 'ae',
            'ö' => 'oe',
            'ü' => 'ue',
            'ß' => 'ss',
        ];
        foreach ($map as $search => $replace) {
            $subject = str_replace($search, $replace, $subject);
        }
        $subject = strtolower($subject);
        return trim(preg_replace('#[^a-z]+#', '-', $subject), '-');
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
    public function getLaTeX()
    {
        return $this->toString();
    }

    /**
     * @return string
     */
    public function getMarkdown()
    {
        return $this->toString();
    }

    /**
     * @return string
     */
    public function getMarkdownLink()
    {
        return sprintf('[%s](%s)', $this->name, $this->getEscapedName());
    }

    /**
     * @return string
     */
    abstract public function toString();
}
