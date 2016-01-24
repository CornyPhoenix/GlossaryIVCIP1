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
        $currentEntries = $this->readCurrentEntries();

        // Write out each entry.
        $abandonedEntries = $this->writeEntries($currentEntries);

        // Delete abandoned entries.
        $this->deleteEntries($abandonedEntries);

        // Write the home page.
        $this->writeHomePage();

        // Write the tag overview page.
        $this->writeTagsPage();

        // Write the wiki sidebar.
        $this->writeSidebar();

        // Write the tag sites.
        $this->writeTags();
    }

    /**
     * @return array
     * @internal param $directory
     */
    private function readCurrentEntries()
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
     * @param string[] $entries
     * @return string[]
     */
    private function writeEntries(array $entries)
    {
        // Build a reference map from the current glossary.
        $map = $this->glossary->buildReferenceMap();

        $current = null;
        $next = null;
        foreach ($this->glossary->getDefinitions() as $definition) {
            if (null === $next) {
                $next = $definition;
                continue;
            }

            /** @var Definition $last */
            $last = $current;
            /** @var Definition $current */
            $current = $next;
            /** @var Definition $next */
            $next = $definition;

            $refs = isset($map[$current->getName()]) ? $map[$current->getName()] : [];
            $entries = $this->writeEntry($entries, $refs, $current, $last, $next);
        }
        $refs = isset($map[$next->getName()]) ? $map[$next->getName()] : [];
        $entries = $this->writeEntry($entries, $refs, $next, $current);

        return $entries;
    }

    /**
     * @param resource $handle
     * @param Definition[] $refs
     * @param Definition $def
     * @param Definition|null $prev
     * @param Definition|null $next
     */
    private function writeOutWikiEntry($handle, array $refs, $def, $prev, $next = null)
    {
        fwrite($handle, '# ' . $def->getName());
        fwrite($handle, "\n");

        if (count($def->getTags())) {
            fwrite($handle, "> tagged with: ");
            $implode = implode(
                ', ',
                array_map(
                    function ($tag) {
                        return "[#$tag]($tag)";
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
     * Write the home site.
     */
    private function writeHomePage()
    {
        $handle = fopen($this->buildFilename('Home'), 'w');
        fwrite($handle, '# ' . $this->glossary->getMeta('title'));
        $this->nl($handle);
        fwrite($handle, '*Author:* ' . $this->glossary->getMeta('author'));
        fwrite($handle, "\n");

        $letter = null;
        foreach ($this->glossary->getDefinitions() as $definition) {
            $thisLetter = $definition->getEscapedName()[0];
            if ($letter !== $thisLetter) {
                $letter = $thisLetter;
                $this->hr($handle);
            }
            fwrite($handle, '* ' . $definition->getMarkdownLink() . "\n");
        }
        $this->nl($handle);
        fwrite($handle, '*Last updated at ' . date('Y-m-d') . '*');
        fclose($handle);
    }

    /**
     * Writes a tag overview page.
     */
    private function writeTagsPage()
    {
        $handle = fopen($this->buildFilename('Tags'), 'w');
        fwrite($handle, '# Tags');
        $this->nl($handle);
        foreach (array_keys($this->glossary->getTags()) as $tag) {
            fwrite($handle, "* [#$tag]($tag)\n");
        }

        fclose($handle);
    }

    /**
     * Writes a sidebar.
     */
    private function writeSidebar()
    {
        $handle = fopen($this->buildFilename('_Sidebar'), 'w');
        fwrite($handle, '[**Overview**](Home)');
        $this->nl($handle);
        fwrite($handle, '[**Tags**](Tags)');
        $this->nl($handle);
        foreach (array_keys($this->glossary->getTags()) as $tag) {
            fwrite($handle, "* [#$tag]($tag)\n");
        }

        fclose($handle);
    }

    /**
     * @param array $entries
     * @param array $refs
     * @param Definition $def
     * @param Definition $last
     * @param Definition $next
     * @return array
     */
    private function writeEntry(array $entries, array $refs, $def, $last = null, $next = null)
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
     * @param string[] $entries
     */
    private function deleteEntries(array $entries)
    {
        foreach (array_keys($entries) as $entry) {
            unlink($this->directory . '/' . $entry . '.md');
        }
    }

    /**
     * Writes sites for each tag.
     */
    private function writeTags()
    {
        foreach ($this->glossary->getTags() as $tag => $definitions) {
            $this->writeTag($tag, $definitions);
        }
    }

    /**
     * @param string $tagName
     * @param Definition[] $definitions
     */
    private function writeTag($tagName, $definitions)
    {
        $handle = fopen($this->directory . '/tags/' . $tagName . '.md', 'w');
        fwrite($handle, '# Tag #' . $tagName);
        $this->nl($handle);
        foreach ($definitions as $definition) {
            fwrite($handle, '* ' . $definition->getMarkdownLink() . "\n");
        }
        $this->hr($handle);
        fwrite($handle, "* [Go to Overview](Home)\n");
        fclose($handle);
    }

    /**
     * @param $handle
     */
    private function hr($handle)
    {
        fwrite($handle, "\n\n***\n\n");
    }

    /**
     * @param $handle
     */
    private function nl($handle)
    {
        fwrite($handle, "\n\n");
    }

    /**
     * @param string $relative
     * @param string $extension
     * @return string
     */
    private function buildFilename($relative, $extension = 'md')
    {
        return $this->directory . '/' . $relative . '.' . $extension;
    }
}
