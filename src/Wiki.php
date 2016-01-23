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
        $map = $this->glossary->buildReferenceMap();

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

            $refs = isset($map[$current->getName()]) ? $map[$current->getName()] : [];
            $entries = $this->writeWikiEntry($entries, $refs, $current, $last, $next);
        }
        $refs = isset($map[$next->getName()]) ? $map[$next->getName()] : [];
        $entries = $this->writeWikiEntry($entries, $refs, $next, $current);

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
     * @param Definition[] $refs
     * @param Definition $def
     * @param Definition|null $prev
     * @param Definition|null $next
     */
    private function writeOutWikiEntry($handle, array $refs, Definition $def, Definition $prev = null, Definition $next = null)
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
        $this->nl($handle);

        foreach ($def->getImages() as $image) {
            fwrite($handle, sprintf('![%1$s](img/%1$s.png)', $image));
            $this->nl($handle);
        }

        $this->hr($handle);
        $this->nl($handle);
        fwrite($handle, "* [Go to Overview](Home)\n");
        foreach ($refs as $ref) {
            fwrite($handle, sprintf("* See also %s\n", $ref->getMarkdownLink()));
        }
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
        fwrite($handle, '# ' . $this->glossary->getMeta('title'));
        $this->nl($handle);
        fwrite($handle, '*Author:* ' . $this->glossary->getMeta('author'));
        fwrite($handle, "\n");

        $letter = null;
        foreach ($this->glossary->getDefinitions() as $definition) {
            $thisLetter = $definition->getEscapedName()[0];
            if ($letter !== $thisLetter) {
                $letter = $thisLetter;
                $this->nl($handle);
                $this->hr($handle);
                $this->nl($handle);
            }
            fwrite($handle, '* ' . $definition->getMarkdownLink() . "\n");
        }
        $this->nl($handle);
        fwrite($handle, '*Last updated at ' . date('Y-m-d') . '*');
    }

    /**
     * @param array $entries
     * @param array $refs
     * @param Definition $def
     * @param Definition $last
     * @param Definition $next
     * @return array
     */
    private function writeWikiEntry(array $entries, array $refs, Definition $def, Definition $last = null, Definition $next = null)
    {
        $name = $def->getEscapedName();
        if (isset($entries[$name])) {
            unset($entries[$name]);
        }

        $handle = fopen($this->directory . '/' . $name . '.md', 'w');
        $this->writeOutWikiEntry($handle, $refs, $def, $last, $next);
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

    /**
     * @param $handle
     */
    private function hr($handle)
    {
        fwrite($handle, "***");
    }

    /**
     * @param $handle
     * @return int
     */
    private function nl($handle)
    {
        return fwrite($handle, "\n\n");
    }
}
