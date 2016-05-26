
# Tale Factory
**A Tale Framework Component**

# What is Tale Factory?

A small and lightweight implementation of the factory pattern

# Installation

Install via Composer

```bash
composer require "talesoft/tale-factory:*"
composer install
```

# Usage

```php

use Tale\Factory;
use Tale\FactoryException;


interface FormatInterface
{
    public function getExtension();
}

class Json implements FormatInterface
{
    public function getExtension() {
        
        return '.json';
    }
}

class Xml implements FormatInterface
{
    public function getExtension() {
        
        return '.xml';
    }
}

class Yaml implements FormatInterface
{
    public function getExtension() {
        
        return '.yml';
    }
}




$factory = new Factory(FormatInterface::class, [
    'json' => Json::class,
    'xml' => Xml::class
]);

if (class_exists('Yaml'))
    $factory->register('yaml', Yaml::class);



$xml = $factory->create('xml');
var_dump($xml->getExtension()); // ".xml"

$json = $factory->create('json');
var_dump($json->getExtension()); // ".json"


$yml = $factory->create(Yaml::class);
var_dump($yml->getExtension()); // ".yml"


try {

    $factory->create('not-resolvable');
    
} catch(FactoryException $ex) {

    exit('Failed to create instance: '.$ex);
}

```

