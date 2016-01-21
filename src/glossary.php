<?php

abstract class Definition {

    /**
     * @var string
     */
    private $name;

    /**
     * Definition constructor.
     * @param string $name
     */
    public function __construct($name)
    {
        $this->name = $name;
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

class EmptyDefinition extends Definition {
    const IDENTIFIER = '-';

    /**
     * {@inheritDoc}
     */
    public function toString()
    {
        return '-';
    }
}

class BodyDefinition extends Definition {

    /**
     * @var string
     */
    private $body;

    /**
     * BodyDefinition constructor.
     * @param string $name
     * @param string $body
     */
    public function __construct($name, $body)
    {
        parent::__construct($name);
        $this->body = $body;
    }

    /**
     * @return string
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * @param string $body
     * @return $this
     */
    public function setBody($body)
    {
        $this->body = $body;
        return $this;
    }

    /**
     * @return string
     */
    public function toString()
    {
        return "\n\t" . $this->wordWrap($this->body);
    }

    /**
     * @param string $text
     * @param int $width
     * @return string
     */
    private function wordWrap($text, $width = 80)
    {
        if (strlen($text) < $width) {
            return $text . "\n";
        }

        $rest = $text . "\n";
        $text = '';
        while (strlen($rest) > $width) {
            $i = $width;
            while (strpos(" \t\n\r\0\x0B", $rest[$i]) === false) {
                $i++;
            }

            $text .= substr($rest, 0, $i) . "\n\t";
            $rest = substr($rest, $i + 1);
        }

        return $text . $rest;
    }
}

class Reference extends Definition {

    const IDENTIFIER = '=>';

    /**
     * @var string
     */
    private $references;

    /**
     * Definition constructor.
     * @param string $name
     * @param string $references
     */
    public function __construct($name, $references)
    {
        parent::__construct($name);
        $this->references = $references;
    }

    /**
     * @return string
     */
    public function getReferences()
    {
        return $this->references;
    }

    /**
     * {@inheritDoc}
     */
    public function getDescription()
    {
        return '\textit{\seename} \gls{' . self::escape($this->getReferences()) . '}';
    }

    /**
     * @return string
     */
    public function toString()
    {
        return self::IDENTIFIER . ' ' . $this->references;
    }
}

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

            if ($definition instanceof Reference) {
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
                        $defs[$currentName] = new BodyDefinition($currentName, trim($currentDef));
                        $currentDef = '';
                    }

                    // Match empty def.
                    list(, $currentName, $rest) = $matches;
                    $trim = trim($rest);
                    if ($trim === EmptyDefinition::IDENTIFIER) {
                        $defs[$currentName] = new EmptyDefinition($currentName);
                        $currentName = null;
                    }

                    if (strpos($trim, Reference::IDENTIFIER) === 0) {
                        $defs[$currentName] = new Reference($currentName, ltrim(substr($trim, 2)));
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
            $defs[$currentName] = new BodyDefinition($currentName, trim($currentDef));
        }

        fclose($handle);
        ksort($defs);
        var_dump($defs);
        $this->definitions = $defs;
    }
}


new Glossary(__DIR__ . '/glossary.txt');
