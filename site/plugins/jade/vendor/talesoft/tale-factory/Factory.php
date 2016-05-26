<?php

namespace Tale;

use ReflectionClass;

/**
 * Dynamic class factory pattern.
 *
 * Instanciates a class based on a Base-Class and Aliases given
 *
 * Does automatic subclass-checks and alias-conversion
 *
 * @package Tale
 */
class Factory
{

    /**
     * The FQCN of the base class.
     *
     * @var string
     */
    private $baseClassName;

    /**
     * An associative array of aliases.
     *
     * @var array
     */
    private $aliases;

    /**
     * Creates a new factory instance.
     *
     * @param string     $baseClassName The base class child-classes should extend from
     * @param array|null $aliases       The aliases that can be used in favor of the FQCN (associative)
     */
    public function __construct($baseClassName = null, array $aliases = null)
    {

        $this->baseClassName = $baseClassName ?: null;
        $this->aliases = $aliases ?: [];
    }

    /**
     * Returns the base-class that all children need to extend from.
     *
     * @return string
     */
    public function getBaseClassName()
    {

        return $this->baseClassName;
    }

    /**
     * Returns the aliases that are currently registered.
     *
     * @return array
     */
    public function getAliases()
    {

        return $this->aliases;
    }

    /**
     * Resolves a a class-name or an alias to a FQCN.
     *
     * If no alias is found, it returns the class name given
     *
     * @param string $className The class-name to be converted
     *
     * @return string The usable FQCN of the class
     * @throws FactoryException
     */
    public function resolve($className)
    {

        if (isset($this->aliases[$className]))
            $className = $this->aliases[$className];

        if (!class_exists($className)
            || ($this->baseClassName
            && !is_subclass_of($className, $this->baseClassName)))
            throw new FactoryException(
                "Failed to create factory instance: ".
                "$className does not exist or is not ".
                "a valid {$this->baseClassName}"
            );

        return $className;
    }

    /**
     * Registers a new alias with a specific FQCN.
     *
     * @param string $alias     The alias the FQDN can be found under
     * @param string $className The FQCN the given alias should map to
     *
     * @return $this
     */
    public function register($alias, $className)
    {

        $this->aliases[$alias] = $className;

        return $this;
    }

    /**
     * Registers an array of aliases.
     *
     * The aliases should be the keys, the FQCNs the values of the associative array
     *
     * @param array $aliases Associative array of aliases => FQCNs
     *
     * @return $this
     */
    public function registerArray(array $aliases)
    {

        foreach ($aliases as $alias => $className)
            $this->register($alias, $className);

        return $this;
    }

    /**
     * Creates a new instance of a class based on a class name or alias given.
     *
     * If the class doesnt exist or doesnt extend the base-class of this factory,
     * an exception is thrown
     *
     * @param string $className The alias or FQCN to instantiate
     * @param array $args The arguments that should be passed to the constructor
     *
     * @return object The newly created child-class instance
     * @throws FactoryException
     */
    public function create($className, array $args = null)
    {

        $args = $args ? $args : [];
        $className = $this->resolve($className);

        return self::createInstance($className, $args);
    }

    /**
     * Creates a new instance of a class based on a class name.
     *
     * If the class doesnt exist, an exception is thrown
     *
     * @param string $className The alias or FQCN to instantiate
     * @param array $args The arguments that should be passed to the constructor
     *
     * @return object The newly created class instance
     * @throws FactoryException
     */
    public static function createInstance($className, array $args = null)
    {

        if (!class_exists($className))
            throw new FactoryException(
                "Failed to create instance: "
                ."$className does not exist."
            );

        $args = $args ? $args : [];

        //Avoid reflection in some major cases for performance reasons
        switch (count($args)) {
            case 0: return new $className();
            case 1: return new $className($args[0]);
            case 2: return new $className($args[0], $args[1]);
            case 3: return new $className($args[0], $args[1], $args[2]);
            case 4: return new $className($args[0], $args[1], $args[2], $args[3]);
            case 5: return new $className($args[0], $args[1], $args[2], $args[3], $args[4]);
        }

        $ref = new ReflectionClass($className);
        return $ref->newInstanceArgs($args);
    }
}