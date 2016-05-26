# Kirby-Jade

Jade rendering plugin for Kirby.

## Install

1. Drop the folder into your plugin directory.
2. Copy the example templates to your template folder if needed.


### Caveats
Conditional attributes work only if wrapped in braces: ``li(class=$page->isActive() ? 'highlight' : '')`` should become ``li(class=($child->isAncestorOf($page) ? 'highlight' : ''))``
