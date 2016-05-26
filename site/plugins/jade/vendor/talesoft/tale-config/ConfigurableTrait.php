<?php
/**
 * The Tale Config Utility Trait.
 *
 * Contains a trait that eases up configuration array handling
 * for all tale framework components
 *
 * LICENSE:
 * The code of this file is distributed under the MIT license.
 * If you didn't receive a copy of the license text, you can
 * read it here http://licenses.talesoft.io/2015/MIT.txt
 *
 * @category   Utility
 * @package    tale-config
 * @author     Torben Koehn <tk@talesoft.io>
 * @author     Talesoft <info@talesoft.io>
 * @copyright  Copyright (c) 2015 Talesoft (http://talesoft.io)
 * @license    http://licenses.talesoft.io/2015/MIT.txt MIT License
 * @version    1.0
 * @link       http://config.tale.talesoft.io/docs/files/ConfigurableTrait.html
 * @since      File available since Release 1.0
 */

namespace Tale;

use Tale\Config\FormatInterface;

/**
 * Provides some utility methods to work with configuration arrays
 *
 * @package Tale\Jade\Util
 */
trait ConfigurableTrait
{

    /**
     * The options array.
     *
     * Keys are option names, values are option values
     * @var array
     */
    protected $options = [];

    /**
     * Sets the options initially providing default- and optional user options.
     *
     * @param array      $defaults    the default options
     * @param array|null $userOptions the optional options passed by the user
     * @param bool       $recursive
     */
    public function defineOptions(array $defaults, array $userOptions = null, $recursive = false)
    {

        $this->options = $defaults;

        if ($userOptions)
            $this->mergeOptions($userOptions, $recursive);
    }

    /**
     * Returns the option array.
     *
     * @return array
     */
    public function getOptions()
    {

        return $this->options;
    }

    /**
     * Merges the current options with another option array.
     *
     * The second parameter makes this recursive.
     * The functions used are array_replace and array_replace_recursive
     *
     * Passing the third parameter reverses the merge, so you don't overwrite
     * passed options with existing one, but rather set them only if they
     * don't exist yet (defaulting)
     *
     * @param array $options    the options to merge with
     * @param bool  $recursive  should we merge recursively or not
     * @param bool  $reverse    should values be prepended rather than appended
     *
     * @return $this
     */
    public function mergeOptions(array $options, $recursive = false, $reverse = false)
    {

        $merge = 'array_replace';

        if ($recursive)
            $merge .= '_recursive';

        $this->options = $reverse
            ? $merge($options, $this->options)
            : $merge($this->options, $options);

        return $this;
    }

    /**
     * Returns a single option by its name.
     *
     * You can pass an optional default value (Default: null)
     *
     * @param string $name         the name of the option to return
     * @param mixed  $defaultValue the default value if the option is not set (Default: null)
     *
     * @return mixed
     */
    public function getOption($name, $defaultValue = null)
    {

        if (strstr($name, '.'))
            return Config::get($name, $this->options, $defaultValue);

        return isset($this->options[$name]) ? $this->options[$name] : $defaultValue;
    }

    /**
     * Replaces with all options passed, if they are not set yet.
     *
     * This is an alias to ->setOptions with the third parameter set
     * to true.
     *
     * @param array      $defaults   the array of default options
     * @param bool|false $recursive  should we merge recursively or not
     *
     * @return $this
     */
    public function setOptions(array $defaults, $recursive = false)
    {

        return $this->mergeOptions($defaults, $recursive, true);
    }

    /**
     * Replaces with all options passed, if they are not set yet.
     *
     * This is an alias to ->setOptions with the third parameter set
     * to true.
     *
     * @param array      $defaults   the array of default options
     * @param bool|false $recursive  should we merge recursively or not
     *
     * @return $this
     */
    public function setDefaults(array $defaults, $recursive = false)
    {

        return $this->mergeOptions($defaults, $recursive, true);
    }

    /**
     * Sets a single option to the passed value.
     *
     * @param string $name the name of the option
     * @param mixed $value the value of the option
     *
     * @return $this
     */
    public function setOption($name, $value)
    {

        $this->options[$name] = $value;

        return $this;
    }

    /**
     * Forwards an option to an option array.
     *
     * e.g.
     * options = [
     *      'target' => [
     *          'targetName' => null
     *      ],
     *
     *      'someOption' => 'someValue'
     * ]
     *
     * options->forwardOption('someOption', 'target', 'targetName')
     * will set ['target']['targetName'] to 'someValue'
     *
     * Notice that the third parameter can be omitted, it will
     * be set to the same name as the first parameter then.
     *
     * @param string $name       the name of the option to forward
     * @param string $target     the name of the option array to forward to
     * @param string $targetName the name of the target option name inside the target array
     */
    public function forwardOption($name, $target, $targetName = null)
    {

        $targetName = $targetName ? $targetName : $name;
        if (isset($this->options[$name]))
            $this->options[$target][$targetName] = $this->options[$name];
    }

    public function loadOptions($path, $optional = false, $recursive = false)
    {

        return $this->mergeOptions(Config::load($path, $optional), $recursive);
    }

    public function loadDefaults($path, $optional = false, $recursive = false)
    {

        return $this->setDefaults(Config::load($path, $optional), $recursive);
    }

    public function interpolateOptions(array &$source = null, $defaultValue = null)
    {

        Config::interpolateArray($this->options, $source, $defaultValue);

        return $this;
    }
}