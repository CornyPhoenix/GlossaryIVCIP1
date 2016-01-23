<?php

namespace CornyPhoenix\Component\Glossaries;

use CornyPhoenix\Component\Glossaries\Definition\BodyDefinition;
use CornyPhoenix\Component\Glossaries\Definition\Definition;
use CornyPhoenix\Component\Glossaries\Definition\EmptyDefinition;
use CornyPhoenix\Component\Glossaries\Definition\ReferenceDefinition;

class Glossary {

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
     * @param string $filename
     * @return $this
     */
    public function setFilename($filename)
    {
        $this->filename = $filename;
        $this->readOutDefinitions();
        $this->writeOutDefinitions();
        $this->writeOutLaTeXFile(__DIR__ . '/../build/glossary.tex');

        return $this;
    }

    /**
     * @return string
     */
    public function getFilename()
    {
        return $this->filename;
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
            $line .= $name . ': ' . $definition->toString() . "\n";
        }

        return $line;
    }

    public function writeOutDefinitions()
    {
        $handle = fopen($this->filename, 'w');
        fwrite($handle, $this->__toString());
        fclose($handle);
    }

    public function writeOutLaTeXString()
    {
        $line = '';
        foreach ($this->definitions as $name => $definition) {
            $escaped = $definition->getEscapedName();
            $options = [
                'name' => $name,
                'description' => $definition->getDescription(),
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

    /**
     * Reads glossary entries.
     */
    private function readOutDefinitions()
    {
        $handle = fopen($this->filename, 'r');
        $currentName = null;
        $currentDef = '';
        $defs = [];
        while (($line = fgets($handle)) !== false) {

            // Is new word.
            if ($line === ltrim($line)) {
                // Match colon.
                if (preg_match('#^([^:]+):(.*)$#', $line, $matches)) {
                    // Write current Definition.
                    if ($currentName !== null) {
                        $defs[$currentName] = new BodyDefinition($currentName, [], trim($currentDef));
                        $currentDef = '';
                    }

                    // Match empty def.
                    list(, $currentName, $rest) = $matches;
                    $trim = trim($rest);
                    if ($trim === EmptyDefinition::IDENTIFIER) {
                        $defs[$currentName] = new EmptyDefinition($currentName, []);
                        $currentName = null;
                    }

                    if (strpos($trim, ReferenceDefinition::IDENTIFIER) === 0) {
                        $defs[$currentName] = new ReferenceDefinition($currentName, [], ltrim(substr($trim, 2)));
                        $currentName = null;
                    }
                }

                continue;
            }

            // Append to current def.
            $currentDef .= trim($line) . ' ';
        }

        // Write current definition.
        if ($currentName !== null) {
            $defs[$currentName] = new BodyDefinition($currentName, [], trim($currentDef));
        }

        fclose($handle);
        ksort($defs);
        var_dump($defs);
        $this->definitions = $defs;
    }
}
