<?php

namespace CornyPhoenix\Component\Glossaries;

use CornyPhoenix\Component\Glossaries\Definition\BodyDefinition;
use CornyPhoenix\Component\Glossaries\Definition\Definition;
use CornyPhoenix\Component\Glossaries\Definition\EmptyDefinition;
use CornyPhoenix\Component\Glossaries\Definition\ReferenceDefinition;

class Glossary
{

    const TAG_IDENTIFIER = '#';
    const IMAGE_IDENTIFIER = '!';

    /**
     * @var string
     */
    private $filename;

    /**
     * @var null|Definition[]
     */
    private $definitions;

    /**
     * @var string[]
     */
    private $tags;

    /**
     * @var string[]
     */
    private $meta;

    /**
     * Glossary constructor.
     * @param string $filename of the glossary file.
     */
    public function __construct($filename)
    {
        $this->meta = [];
        $this->wiki = new Wiki($this, __DIR__ . '/../build/wiki');
        $this->setFilename($filename);
    }

    /**
     * @param string $text
     */
    public static function warn($text)
    {
        error_log("\e[1;33mWARN:\e[m $text");
    }

    /**
     * @param string $text
     */
    public static function err($text)
    {
        error_log("\e[1;31mERR:\e[m $text");
    }

    /**
     * @param array $array
     * @param $prefix
     * @return string
     */
    private static function prefix(array $array, $prefix)
    {
        return array_map(
            function ($image) use ($prefix) {
                return $prefix . $image;
            },
            $array
        );
    }

    /**
     * @return string
     */
    public function getFilename()
    {
        return $this->filename;
    }

    /**
     * @param string $filename
     * @return $this
     */
    public function setFilename($filename)
    {
        if ($this->readOutDefinitions($filename)) {
            $this->filename = $filename;
            $this->writeOutDefinitions();
            $this->writeOutLaTeXFile(__DIR__ . '/../build/glossary.tex');
            $this->flushWiki();
        }

        return $this;
    }

    /**
     * @return Definition[]
     */
    public function getDefinitions()
    {
        return $this->definitions;
    }

    /**
     * @return string[]
     */
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * @return array
     */
    public function buildReferenceMap()
    {
        $map = [];
        foreach ($this->definitions as $definition) {
            if ($definition instanceof BodyDefinition) {
                $definition->getParsedBody(function (Definition $def) use ($definition, &$map) {
                    if (!isset($map[$def->getName()])) {
                        $map[$def->getName()] = [$definition->getName() => $definition];
                    } else {
                        $map[$def->getName()][$definition->getName()] = $definition;
                    }

                    return '';
                });
            }
        }

        return $map;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        $line = '';
        $line .= $this->writeFrontMatter();

        $empty = [];
        foreach ($this->definitions as $definition) {
            if ($definition instanceof EmptyDefinition) {
                $empty[] = $definition;
                continue;
            }

            $line = $this->writeDefinition($line, $definition);
        }

        foreach ($empty as $definition) {
            $line = $this->writeDefinition($line, $definition);
        }

        return $line;
    }

    /**
     * Writes out definitions in file.
     */
    public function writeOutDefinitions()
    {
        $handle = fopen($this->filename, 'w');
        fwrite($handle, $this->__toString());
        fclose($handle);
    }

    /**
     * Generates a LaTeX string from the defintions.
     *
     * @return string
     */
    public function writeOutLaTeXString()
    {
        $line = $this->writeFrontMatterLaTeX();

        foreach ($this->definitions as $name => $definition) {
            $escaped = $definition->getEscapedName();
            $options = [
                'name' => LaTeX::escape($name),
                'description' => $definition->getLaTeX(),
            ];

            if ($definition instanceof ReferenceDefinition) {
                $options['see'] = $this->getDefinition($definition->getReferences())->getEscapedName();
            }

            $parsedOptions = [];
            foreach ($options as $key => $value) {
                $parsedOptions[] = $key . '={' . $value . '}';
            }
            $line .= sprintf("\\newglossaryentry{%s}{%s}\n", $escaped, implode(',', $parsedOptions));
        }

        return $line;
    }

    /**
     * @param string $filename
     */
    public function writeOutLaTeXFile($filename)
    {
        $handle = fopen($filename, 'w');
        fwrite($handle, $this->writeOutLaTeXString());
        fclose($handle);
    }

    /**
     * Flushes the wiki to hard disk.
     */
    public function flushWiki()
    {
        $this->wiki->flush();
    }

    /**
     * @param string $name
     * @return Definition
     */
    public function getDefinition($name)
    {
        if (!isset($this->definitions[$name])) {
            Glossary::warn("No definition for \e[1m$name\e[m.");
            return null;
        }

        return $this->definitions[$name];
    }

    /**
     * @param string $string
     * @return null|string
     */
    public function getMeta($string)
    {
        if (!isset($this->meta[$string])) {
            Glossary::warn("No meta entry for \e[1m$string\e[m.");
            return null;
        }

        return $this->meta[$string];
    }

    /**
     * @return Definition[][]
     */
    public function getTaggedDefinitions()
    {
        $map = [];
        foreach ($this->definitions as $definition) {
            foreach ($definition->getTags() as $tag) {
                if (!isset($map[$tag])) {
                    $map[$tag] = [];
                }
                $map[$tag][] = $definition;
            }
        }

        return $map;
    }

    /**
     * Reads glossary entries.
     * @param string $filename
     * @return bool
     */
    private function readOutDefinitions($filename)
    {
        $handle = fopen($filename, 'r');
        /** @var Definition|BodyDefinition $currentDef */
        $currentDef = null;
        $defs = [];
        $tags = [];
        while (($line = fgets($handle)) !== false) {
            if (preg_match('#^([^:]+):\s*(.*)$#', $line, $matches)) {
                list(, $key, $value) = $matches;
                $this->meta[$key] = $value;
                continue;
            }

            break;
        }

        while (($line = fgets($handle)) !== false) {
            // Match key line.
            if (preg_match('#^([^\s:][^:]*):(.*)$#', $line, $matches)) {
                // Write current Definition.
                if ($currentDef !== null) {
                    if ($currentDef instanceof BodyDefinition && $currentDef->isEmpty()) {
                        $defs[$currentDef->getName()] = $currentDef->makeEmpty();
                    } else {
                        $defs[$currentDef->getName()] = $currentDef;
                    }
                }

                // Match empty def.
                list(, $currentName, $rest) = $matches;

                if (isset($defs[$currentName])) {
                    self::err(sprintf("Duplicate entry: \e[1m%s\e[m", $currentName));
                    return false;
                }

                $currentDef = $this->createDef($currentName, $rest);
                $currentDef->setTags($this->readOutTags($rest));
                $currentDef->setImages($this->readOutImages($rest));

                $tags = array_merge($tags, $currentDef->getTags());

                continue;
            }

            // Append to current body def.
            if ($currentDef instanceof BodyDefinition) {
                $currentDef->appendBody($line);
                continue;
            }
        }

        // Write current definition.
        if ($currentDef !== null) {
            if ($currentDef instanceof BodyDefinition && $currentDef->isEmpty()) {
                $defs[$currentDef->getName()] = $currentDef->makeEmpty();
            } else {
                $defs[$currentDef->getName()] = $currentDef;
            }
        }

        fclose($handle);
        $this->definitions = $this->sortDefinitions($defs);
        $this->tags = $this->sortDefinitions(array_values(array_unique($tags)));
        $this->warnEmptyDefinitions();

        return true;
    }

    /**
     * Creates a new Definition from a name and a rest key line.
     *
     * @param $currentName
     * @param string $rest
     * @return Definition
     */
    private function createDef($currentName, $rest)
    {
        $trim = trim($rest);

        if (strpos($trim, ReferenceDefinition::IDENTIFIER) === 0) {
            return new ReferenceDefinition($this, $currentName, ltrim(substr($trim, 2)));
        }

        return new BodyDefinition($this, $currentName);
    }

    /**
     * @param string $string
     * @return string[]
     */
    private function readOutTags($string)
    {
        if (!preg_match_all(sprintf('/%s(\w+)/', self::TAG_IDENTIFIER), $string, $matches)) {
            return [];
        }

        return $matches[1];
    }

    /**
     * @param $string
     * @return string[]
     */
    private function readOutImages($string)
    {
        if (!preg_match_all(sprintf('/%s(\w+)/', self::IMAGE_IDENTIFIER), $string, $matches)) {
            return [];
        }

        return self::checkImagesExist($matches[1]);
    }

    /**
     * @param Definition $definition
     * @return string[]
     */
    private function formatTags(Definition $definition)
    {
        return self::prefix($definition->getTags(), self::TAG_IDENTIFIER);
    }

    /**
     * @param Definition $definition
     * @return string[]
     */
    private function formatImages(Definition $definition)
    {
        return self::prefix($definition->getImages(), self::IMAGE_IDENTIFIER);
    }

    /**
     * Warns about empty definitions.
     */
    private function warnEmptyDefinitions()
    {
        foreach ($this->definitions as $definition) {
            if ($definition instanceof EmptyDefinition) {
                if (count($definition->getImages())) {
                    continue;
                }

                $entry = $definition->getName();
                self::warn("Entry \e[1m$entry\e[m is empty.");
            }
        }
    }

    /**
     * Filters out non existing images.
     *
     * @param string[] $images
     * @return string[]
     */
    private function checkImagesExist(array $images)
    {
        return array_filter(
            $images,
            function ($image) {
                $path = realpath(__DIR__ . '/../img/' . $image . '.png');

                if (!file_exists($path)) {
                    self::warn("Image \e[1m$image\e[m is missing. Is it a PNG?");
                    return false;
                }

                return true;
            }
        );
    }

    /**
     * @return string
     */
    private function writeFrontMatter()
    {
        $line = '';
        foreach ($this->meta as $key => $value) {
            $line .= $key;
            $line .= ': ';
            $line .= $value;
            $line .= "\n";
        }
        $line .= "---\n";
        return $line;
    }

    /**
     * @return string
     */
    private function writeFrontMatterLaTeX()
    {
        $line = '';
        foreach ($this->meta as $key => $value) {
            $line .= sprintf('\%s{%s}', $key, $value);
            $line .= "\n";
        }
        return $line;
    }

    /**
     * Sorts given definitions.
     *
     * @param Definition[] $defs
     * @return Definition[]
     */
    private function sortDefinitions(array $defs)
    {
        uksort($defs, function ($subject1, $subject2) {
            $subject1 = $this->escapeSort($subject1);
            $subject2 = $this->escapeSort($subject2);

            if ($subject1 < $subject2) {
                return -1;
            }

            if ($subject1 > $subject2) {
                return 1;
            }

            return 0;
        });

        return $defs;
    }

    /**
     * @param string $line
     * @param Definition $definition
     * @return string
     */
    private function writeDefinition($line, Definition $definition)
    {
        $name = $definition->getName();
        $line .= $name . ': ';
        $annotations = array_merge(
            $definition->getPrefix() ? [$definition->getPrefix()] : [],
            $this->formatTags($definition),
            $this->formatImages($definition)
        );
        $line .= implode(' ', $annotations);
        $line .= $definition->toString();
        $line .= "\n";

        return $line;
    }

    /**
     * @param string $subject
     * @return string
     */
    private function escapeSort($subject)
    {
        $map = [
            'ä' => 'a',
            'ö' => 'o',
            'ü' => 'u',
            'Ä' => 'a',
            'Ö' => 'o',
            'Ü' => 'u',
            'ß' => 's',
        ];
        foreach ($map as $search => $replace) {
            $subject = str_replace($search, $replace, $subject);
        }
        $subject = strtolower($subject);
        return preg_replace('/[^0-9a-z]+/', '', $subject);
    }
}
