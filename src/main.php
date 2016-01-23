#!/usr/bin/env php
<?php

require_once __DIR__ . "/../vendor/autoload.php";

use CornyPhoenix\Component\Glossaries\Glossary;

new Glossary(__DIR__ . '/glossary.txt');
