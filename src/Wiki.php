<?php

namespace CornyPhoenix\Component\Glossaries;

use CornyPhoenix\Component\Glossaries\Definition\Definition;

/**
 * @package ${NAMESPACE}
 * @author moellers
 */
class Wiki
{

    /**
     * @var Glossary
     */
    private $glossary;

    /**
     * @var string
     */
    private $directory;

    /**
     * Wiki constructor.
     * @param Glossary $glossary
     * @param string $directory
     */
    public function __construct(Glossary $glossary, $directory)
    {
        $this->glossary = $glossary;
        $this->directory = $directory;
    }

    /**
     * Flushes the wiki.
     */
    public function flush()
    {
        // Read current entries.
        $entries = $this->readCurrentWikiEntries($this->directory);

        // Write out each entry.
        $current = null;
        $next = null;
        foreach ($this->glossary->getDefinitions() as $definition) {
            if (null === $next) {
                $next = $definition;
                $current = null;
                continue;
            }

            $last = $current;
            $current = $next;
            $next = $definition;

            $entries = $this->writeWikiEntry($this->directory, $entries, $current, $last, $next);
        }
        $entries = $this->writeWikiEntry($this->directory, $entries, $next, $current);

        // Delete unused entries.
        foreach (array_keys($entries) as $entry) {
            unlink($this->directory . '/' . $entry . '.md');
        }

        $handle = fopen($this->directory . '/Home.md', 'w');
        $this->writeOutWikiHome($handle);
        fclose($handle);
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
        foreach ($this->glossary->getDefinitions() as $definition) {
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
