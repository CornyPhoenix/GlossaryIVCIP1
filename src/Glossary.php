<?php

namespace CornyPhoenix\Component\Glossaries;

use CornyPhoenix\Component\Glossaries\Definition\BodyDefinition;
use CornyPhoenix\Component\Glossaries\Definition\Definition;
use CornyPhoenix\Component\Glossaries\Definition\EmptyDefinition;
use CornyPhoenix\Component\Glossaries\Definition\ReferenceDefinition;

class Glossary
{

    const TAG_IDENTIFIER = '#';

    /**
     * @var string
     */
    private $filename;

    /**
     * @var null|Definition[]
     */
    private $definitions;

    /**
     * Glossary constructor.
     * @param string $filename of the glossary file.
     */
    public function __construct($filename)
    {
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
        $this->filename = $filename;
        $this->readOutDefinitions();
        $this->writeOutDefinitions();
        $this->writeOutLaTeXFile(__DIR__ . '/../build/glossary.tex');
        $this->writeOutWiki(__DIR__ . '/../build/wiki');

        return $this;
    }

    /**
     * @return array|null
     */
    public function getDefinitions()
    {
        return $this->definitions;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        $line = '';
        foreach ($this->definitions as $name => $definition) {
            $line .= $name . ': ' . $this->implodeTags($definition->getTags()) . $definition->toString() . "\n";
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
        $line = '';
        foreach ($this->definitions as $name => $definition) {
            $escaped = $definition->getEscapedName();
            $options = [
                'name' => $name,
                'description' => $definition->getLaTeX(),
            ];

            if ($definition instanceof ReferenceDefinition) {
                $options['see'] = $this->definitions[$definition->getReferences()]->getEscapedName();
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

    public function writeOutWiki($directory)
    {
        // Read current entries.
        $entries = $this->readCurrentWikiEntries($directory);

        // Write out each entry.
        $current = null;
        $next = null;
        foreach ($this->definitions as $definition) {
            if (null === $next) {
                $next = $definition;
                $current = null;
                continue;
            }

            $last = $current;
            $current = $next;
            $next = $definition;

            $entries = $this->writeWikiEntry($directory, $entries, $current, $last, $next);
        }
        $entries = $this->writeWikiEntry($directory, $entries, $next, $current);

        // Delete unused entries.
        foreach (array_keys($entries) as $entry) {
            unlink($directory . '/' . $entry . '.md');
        }

        $handle = fopen($directory . '/Home.md', 'w');
        $this->writeOutWikiHome($handle);
        fclose($handle);
    }

    /**
     * @param string $name
     * @return Definition
     */
    public function getDefinition($name)
    {
        return $this->definitions[$name];
    }

    /**
     * Reads glossary entries.
     */
    private function readOutDefinitions()
    {
        $handle = fopen($this->filename, 'r');
        $currentName = null;
        $currentDef = null;
        $defs = [];
        while (($line = fgets($handle)) !== false) {
            // Is new word.
            if ($line !== ltrim($line)) {
                // Append to current def.
                $currentDef->appendBody($line);
                continue;
            }

            // Match colon.
            if (preg_match('#^([^:]+):(.*)$#', $line, $matches)) {
                // Write current Definition.
                if ($currentName !== null) {
                    $defs[$currentName] = $currentDef;
                }

                // Match empty def.
                list(, $currentName, $rest) = $matches;
                $trim = trim($rest);
                $tags = $this->readOutTags($trim);

                if ($trim === EmptyDefinition::IDENTIFIER) {
                    $defs[$currentName] = new EmptyDefinition($this, $currentName, $tags);
                    $currentName = null;
                    continue;
                }

                if (strpos($trim, ReferenceDefinition::IDENTIFIER) === 0) {
                    $defs[$currentName] = new ReferenceDefinition($this, $currentName, $tags, ltrim(substr($trim, 2)));
                    $currentName = null;
                    continue;
                }

                $currentDef = new BodyDefinition($this, $currentName, $tags);
            }
        }

        // Write current definition.
        if ($currentName !== null) {
            $defs[$currentName] = $currentDef;
        }

        fclose($handle);
        ksort($defs);
        $this->definitions = $defs;
        $this->warnEmptyDefinitions();
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
     * @param string[] $tags
     * @return string
     */
    private function implodeTags($tags)
    {
        return implode(
            ' ',
            array_map(
                function ($tag) {
                    return self::TAG_IDENTIFIER . $tag;
                },
                $tags
            )
        );
    }

    /**
     * Warns about empty definitions.
     */
    private function warnEmptyDefinitions()
    {
        foreach ($this->definitions as $definition) {
            if ($definition instanceof EmptyDefinition) {
                $entry = $definition->getName();
                self::warn("Entry \e[1m$entry\e[m is empty.");
            }
        }
    }

    /**
     * @param $directory
     * @return array
     */
    private function readCurrentWikiEntries($directory)
    {
        $entries = [];
        $dir = opendir($directory);

        while (false !== ($entry = readdir($dir))) {
            if ('.' === $entry[0]) {
                continue;
            }

            $entries[substr($entry, 0, -3)] = true;
        }

        closedir($dir);
        return $entries;
    }

    /**
     * @param resource $handle
     * @param Definition $definition
     * @param Definition|null $prev
     * @param Definition|null $next
     */
    private function writeOutWikiEntry(
        $handle,
        Definition $definition,
        Definition $prev = null,
        Definition $next = null
    )
    {
        fwrite($handle, '# ' . $definition->getName());
        fwrite($handle, "\n");

        if (count($definition->getTags())) {
            fwrite($handle, "> tagged with: ");
            $implode = implode(
                ', ',
                array_map(
                    function ($tag) {
                        return "`#$tag`";
                    },
                    $definition->getTags()
                )
            );
            fwrite($handle, "$implode\n");
        }

        fwrite($handle, "\n");
        fwrite($handle, $definition->getMarkdown());
        fwrite($handle, "\n\n");
        fwrite($handle, "***");
        fwrite($handle, "\n\n");
        fwrite($handle, "* [See all](Home)\n");
        if ($prev) {
            fwrite($handle, sprintf("* Previous: %s\n", $prev->getMarkdownLink()));
        }
        if ($next) {
            fwrite($handle, sprintf("* Next: %s\n", $next->getMarkdownLink()));
        }
    }

    /**
     * @param resource $handle
     */
    private function writeOutWikiHome($handle)
    {
        fwrite($handle, '# Glossary');
        fwrite($handle, "\n\n");
        foreach ($this->definitions as $definition) {
            fwrite($handle, '* ' . $definition->getMarkdownLink() . "\n");
        }
        fwrite($handle, "\n\n");
    }

    /**
     * @param string $directory
     * @param array $entries
     * @param Definition $current
     * @param Definition $last
     * @param Definition $next
     * @return array
     */
    private function writeWikiEntry(
        $directory,
        array $entries,
        Definition $current,
        Definition $last = null,
        Definition $next = null
    )
    {
        $name = $current->getEscapedName();
        if (isset($entries[$name])) {
            unset($entries[$name]);
        }

        $handle = fopen($directory . '/' . $name . '.md', 'w');
        $this->writeOutWikiEntry($handle, $current, $last, $next);
        fclose($handle);

        return $entries;
    }
}
