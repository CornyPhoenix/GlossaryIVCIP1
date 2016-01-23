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
        // Empties the image directory.
        $this->emptyImages();

        // Read current entries.
        $entries = $this->readCurrentWikiEntries();

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

            $entries = $this->writeWikiEntry($entries, $current, $last, $next);
        }
        $entries = $this->writeWikiEntry($entries, $next, $current);

        // Delete unused entries.
        foreach (array_keys($entries) as $entry) {
            unlink($this->directory . '/' . $entry . '.md');
        }

        $handle = fopen($this->directory . '/Home.md', 'w');
        $this->writeOutWikiHome($handle);
        fclose($handle);
    }

    /**
     * @return array
     * @internal param $directory
     */
    private function readCurrentWikiEntries()
    {
        $entries = [];
        $dir = opendir($this->directory);

        while (false !== ($entry = readdir($dir))) {
            if ('.' === $entry[0]) {
                continue;
            }
            if ('.md' !== substr($entry, -3)) {
                continue;
            }

            $entries[substr($entry, 0, -3)] = true;
        }

        closedir($dir);
        return $entries;
    }

    /**
     * @param resource $handle
     * @param Definition $def
     * @param Definition|null $prev
     * @param Definition|null $next
     */
    private function writeOutWikiEntry($handle, Definition $def, Definition $prev = null, Definition $next = null)
    {
        fwrite($handle, '# ' . $def->getName());
        fwrite($handle, "\n");

        if (count($def->getTags())) {
            fwrite($handle, "> tagged with: ");
            $implode = implode(
                ', ',
                array_map(
                    function ($tag) {
                        return "`#$tag`";
                    },
                    $def->getTags()
                )
            );
            fwrite($handle, "$implode\n");
        }

        fwrite($handle, "\n");
        fwrite($handle, $def->getMarkdown());
        fwrite($handle, "\n\n");

        foreach ($def->getImages() as $image) {
            fwrite($handle, sprintf('![%1$s](img/%1$s.png)', $image));
            fwrite($handle, "\n\n");
        }

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
     * @param array $entries
     * @param Definition $def
     * @param Definition $last
     * @param Definition $next
     * @return array
     */
    private function writeWikiEntry(array $entries, Definition $def, Definition $last = null, Definition $next = null)
    {
        $name = $def->getEscapedName();
        if (isset($entries[$name])) {
            unset($entries[$name]);
        }

        $handle = fopen($this->directory . '/' . $name . '.md', 'w');
        $this->writeOutWikiEntry($handle, $def, $last, $next);
        fclose($handle);

        $this->copyImages($def);

        return $entries;
    }

    /**
     * Copies images of a definition to the wiki.
     *
     * @param Definition $definition
     */
    private function copyImages(Definition $definition)
    {
        $dir = __DIR__ . '/../img/';
        foreach ($definition->getImages() as $image) {
            copy($dir . $image . '.png', $this->directory . '/img/' . $image . '.png');
        }
    }

    /**
     * Copies all images to the wiki.
     */
    private function emptyImages()
    {
        $dir = $this->directory . '/img/';
        $imageDir = opendir($dir);
        while (false !== ($entry = readdir($imageDir))) {
            if ('.' === $entry[0]) {
                continue;
            }

            unlink($dir . $entry);
        }
        closedir($imageDir);
    }
}
