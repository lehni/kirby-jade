<?php

namespace Tale\Config;

use Tale\Config;
use Tale\ConfigurableInterface;

/**
 * Class DelegateTrait
 *
 * @package Tale\Config
 */
trait DelegateTrait
{

    /**
     * @return array
     */
    public function getOptions()
    {

        $nameSpace = $this->getOptionNameSpace();
        if (empty($nameSpace))
            return $this->getTargetConfigurableObject()
                        ->getOptions();

        return $this->getTargetConfigurableObject()
                    ->getOption($nameSpace);
    }

    /**
     * @param array $options
     * @param bool  $recursive
     * @param bool  $reverse
     *
     * @return $this
     */
    public function mergeOptions(array $options, $recursive = false, $reverse = false)
    {

        $nameSpace = $this->getOptionNameSpace();
        if (!empty($nameSpace)) {

            $o = [];
            Config::set($nameSpace, $options, $o);
            $options = $o;
        }

        $this->getTargetConfigurableObject()
            ->mergeOptions($options, $recursive, $reverse);

        return $this;
    }

    /**
     * @param array $options
     * @param bool  $recursive
     *
     * @return $this
     */
    public function setOptions(array $options, $recursive = false)
    {

        return $this->mergeOptions($options, $recursive);
    }

    /**
     * @param array $options
     * @param bool  $recursive
     *
     * @return $this
     */
    public function setDefaults(array $options, $recursive = false)
    {

        return $this->mergeOptions($options, $recursive, true);
    }

    /**
     * @param string $name
     * @param mixed $default
     *
     * @return mixed
     */
    public function getOption($name, $default = null)
    {

        return $this->getTargetConfigurableObject()->getOption(
            $this->getOptionName($name),
            $default
        );
    }

    /**
     * @param string $key
     * @param mixed $value
     *
     * @return $this
     */
    public function setOption($key, $value)
    {

        $this->getTargetConfigurableObject()
            ->setOption($this->getOptionName($key), $value);

        return $this;
    }

    /**
     * @param string $name
     *
     * @return string
     */
    protected function getOptionName($name)
    {

        $nameSpace = $this->getOptionNameSpace();
        if (empty($nameSpace))
            return $name;

        return $nameSpace.'.'.$name;
    }

    protected function getOptionNameSpace()
    {

        return '';
    }

    /**
     * @return ConfigurableInterface
     */
    abstract protected function getTargetConfigurableObject();
}