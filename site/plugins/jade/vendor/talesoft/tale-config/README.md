
# Tale Config
**A Tale Framework Component**

# What is Tale Config?

A small configuration utility library.
It supports different adapters and a few utilities to handle option arrays and configurable objects

# Installation

Install via Composer

```bash
composer require "talesoft/tale-config:*"
composer install
```

# Usage

## The ConfigurableTrait
```php

use Tale\ConfigurableInterface;
use Tale\ConfigurableTrait;

class DbConnection implements ConfigurableInterface
{
    use ConfigurableTrait;
    
    public function __construct(array $options = null)
    {
    
        $this->defineOptions([
            'host' => 'localhost',
            'user' => 'root',
            'password' => '',
            'encoding' => 'utf-8',
            'databases' => [
                'db1' => 'database_1',
                'db2' => 'database_2'
            }
        ], $options);
        
        var_dump($this->getOptions()); //The current options
        
        var_dump($this->getOption('databases'); //['db1' => 'database_1', 'db2' => 'database_2']
        
        var_dump($this->getOption('databases.db1'); //database_1
        var_dump($this->getOption('databases.db2'); //database_2
    }
}
```

