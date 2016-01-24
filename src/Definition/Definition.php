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
     * @var string[]
     */
    private $images;

    /**
     * @var Glossary
     */
    private $glossary;

    /**
     * Definition constructor.
     * @param Glossary $glossary
     * @param string $name
     */
    public function __construct(Glossary $glossary, $name)
    {
        $this->name = $name;
        $this->tags = [];
        $this->images = [];
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
     * @param string[] $tags
     * @return $this
     */
    public function setTags(array $tags)
    {
        $this->tags = $tags;
        return $this;
    }

    /**
     * @param string $tag
     * @return $this
     */
    public function addTag($tag)
    {
        $this->tags[] = $tag;
        return $this;
    }

    /**
     * @return string[]
     */
    public function getImages()
    {
        return $this->images;
    }

    /**
     * @param string[] $images
     * @return $this
     */
    public function setImages(array $images)
    {
        $this->images = $images;
        return $this;
    }

    /**
     * @param string $image
     * @return $this
     */
    public function addImage($image)
    {
        $this->images[] = $image;
        return $this;
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
    public abstract function getLaTeX();

    /**
     * @return string
     */
    public abstract function getMarkdown();

    /**
     * @return string
     */
    public abstract function toString();

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
    public function getPrefix()
    {
        return '';
    }
}
