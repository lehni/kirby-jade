<?php

namespace Tale\Config\Format;

use Tale\Config\FormatInterface;

class Ini implements FormatInterface
{

    public function load($path)
    {

        return $this->getOptionsFromIniArray(parse_ini_file($path, true));
    }

    private function getOptionsFromIniArray(array $array)
    {

        //TODO: improve this shit.
        $result = [];
        foreach ($array as $key => $value) {

            if ($key === '') {

                $result = array_replace_recursive(
                    $result,
                    $this->getOptionsFromIniArray($value)
                );
                continue;
            }

            $target = &$result;

            if (strstr($key, '.')) {

                $parts = explode('.', $key);
                $len = count($parts);
                for ($i = 0; $i < $len - 1; $i++) {

                    $subKey = $parts[$i];

                    if (!isset($target[$subKey]))
                        $target[$subKey] = [];
                    else if (!is_array($target[$subKey]))
                        $target[$subKey] = ['value' => $target[$subKey]];

                    $target = &$target[$subKey];
                }

                $key = $parts[$len - 1];
            }

            if (is_array($value)) {

                $target[$key] = $this->getOptionsFromIniArray($value);
                continue;
            }

            $target[$key] = $value;
        }

        return $result;
    }
}