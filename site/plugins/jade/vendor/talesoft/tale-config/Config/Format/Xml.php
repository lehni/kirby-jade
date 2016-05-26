<?php

namespace Tale\Config\Format;

use Tale\Config\FormatInterface;
use Tale\Dom\Text;
use Tale\Dom\Element;
use Exception;

class Xml implements FormatInterface
{

    public function load($path)
    {

        if (!class_exists('Tale\\Dom\\Parser'))
            throw new Exception(
                "Failed to load XML config: Please install the ".
                "`talesoft/tale-dom` package"
            );

        return $this->getOptionsFromElement(Element::fromFile($path));
    }

    private function getOptionsFromElement(Element $element)
    {

        if (!$element->hasChildren())
            return true;

        $result = [];
        foreach ($element as $child) {

            if ($child instanceof Text)
                return $child->getText();

            if ($child instanceof Element)
                $result[$child->getName()] = $this->getOptionsFromElement($child);
        }

        return $result;
    }
}