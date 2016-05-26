<?php

namespace Tale\Config\Format;

use Tale\Config\FormatInterface;
use Symfony\Component\Yaml\Yaml as Parser;
use Exception;

class Yaml implements FormatInterface
{

    public function load($path)
    {

        if (!class_exists(Parser::class))
            throw new Exception(
                "Failed to load YAML config: Please install the ".
                "`symfony/yaml` package"
            );

        return Parser::parse(file_get_contents($path));
    }
}