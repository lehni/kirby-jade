<?php

namespace Jade;

use Jade\Filter;

class Compiler {

    protected $xml;
    protected $parentIndents;

    protected $buffer       = array();
    protected $prettyprint  = false;
    protected $terse        = false;
    protected $withinCase   = false;
    protected $indents      = 0;

    protected $doctypes = array(
        '5'             => '<!DOCTYPE html>',
        'html'          => '<!DOCTYPE html>',
        'default'       => '<!DOCTYPE html>',
        'xml'           => '<?xml version="1.0" encoding="utf-8" ?>',
        'transitional'  => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">',
        'strict'        => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">',
        'frameset'      => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Frameset//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-frameset.dtd">',
        '1.1'           => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">',
        'basic'         => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML Basic 1.1//EN" "http://www.w3.org/TR/xhtml-basic/xhtml-basic11.dtd">',
        'mobile'        => '<!DOCTYPE html PUBLIC "-//WAPFORUM//DTD XHTML Mobile 1.2//EN" "http://www.openmobilealliance.org/tech/DTD/xhtml-mobile12.dtd">'
    );

    protected $selfClosing  = array('meta', 'img', 'link', 'input', 'source', 'area', 'base', 'col', 'br', 'hr');
    //protected $phpKeywords  = array('true','false','null','switch','case','default','endswitch','if','elseif','else','endif','while','endwhile','do','for','endfor','foreach','endforeach','as','unless');
    protected $phpOpenBlock = array('switch','elseif','if','else','while','do','foreach','for','unless');
    //protected $phpCloseBlock= array('endswitch','endif','endwhile','endfor','endforeach');

    public function __construct($prettyprint=false) {
        $this->prettyprint = $prettyprint;
    }

    public function compile($node) {
        $this->visit($node);
        return implode('', $this->buffer);
    }

    public function visit(Nodes\Node $node) {
        // TODO: set debugging info
        $this->visitNode($node);
        return $this->buffer;
    }

    protected function apply($method, $arguments){
        switch(count($arguments)) {
            case 0:
                return $this->{$method}();
                break;

            case 1:
                return $this->{$method}($arguments[0]);
                break;

            case 2:
                return $this->{$method}($arguments[0], $arguments[1]);
                break;

            case 3:
                return $this->{$method}($arguments[0], $arguments[1], $arguments[2]);
                break;

            case 4:
                return $this->{$method}($arguments[0], $arguments[1], $arguments[2], $arguments[3]);
                break;

            case 5:
                return $this->{$method}($arguments[0], $arguments[1], $arguments[2], $arguments[3], $arguments[4]);
                break;

            default:
                return call_user_func_array(array($this, $method), $arguments);
                break;
        }
    }

    protected function buffer($line, $indent=null) {
        if (($indent !== null && $indent == true) || ($indent === null && $this->prettyprint)) {
            array_push($this->buffer, $this->indent() . $line . $this->newline());
        }else{
            array_push($this->buffer, $line);
        }
    }

    protected function indent() {
        if ($this->prettyprint) {
            return str_repeat('  ', $this->indents);
        }
        return '';
    }

    protected function newline() {
        if ($this->prettyprint) {
            return "\n";
        }
        return '';
    }

    protected function isConstant($str, $attr = false) {
        //  This pattern matches against string constants, some php keywords, number constants and a empty string
        //
        //  the pattern without php escaping:
        //
        //      [ \t]*((['"])(?:\\.|[^'"\\])*\g{-1}|true|false|null|[0-9]+|\b\b)[ \t]*
        //
        //  pattern explained:
        //
        //      [ \t]* - we ignore spaces at the beginning and at the end: useful for the recursive pattern bellow
        //
        //      the first part of the unamed subpattern matches strings:
        //          (['"]) - matches a string opening, inside a group because we use a backreference
        //
        //          unamed group to catch the string content:
        //              \\.     - matches any escaped character, including ', " and \
        //              [^'"\\] - matches any character, except the ones that have a meaning
        //
        //          \g{-1}  - relative backreference - http://codesaway.info/RegExPlus/backreferences.html#relative
        //                  - used for two reasons:
        //                      1. reference the same character used to open the string
        //                      2. the pattern is used twice inside the array regex, so cant used absolute or named
        //
        //      the rest of the pattern:
        //          true|false|null - language constants
        //          0-9             - number constants
        //          \b\b            - matches a empty string: useful for a empty array
        $const_regex = '[ \t]*(([\'"])(?:\\\\.|[^\'"\\\\])*\g{-1}|true|false|null|undefined|[0-9]+|\b\b)[ \t]*';
        $str= trim($str);
        $ok = preg_match("/^{$const_regex}$/", $str);

        // test agains a array of constants
        if (!$attr && !$ok && (0 === strpos($str,'array(') || 0 === strpos($str,'['))) {

            // This pattern matches against array constants: useful for "data-" attributes (see test attrs-data.jade)
            //
            // simpler regex                - explanation
            //
            // arrray\(\)                   - matches against the old array construct
            // []                           - matches against the new/shorter array construct
            // (const=>)?const(,recursion)  - matches against the value list, values can be a constant or a new array built of constants
            if (preg_match("/array[ \t]*\((?R)\)|\\[(?R)\\]|({$const_regex}=>)?{$const_regex}(,(?R))?/", $str, $matches)) {
                // cant use ^ and $ because the patter is recursive
                if (strlen($matches[0]) == strlen($str)) {
                    $ok = true;
                }
            }
        }

        return $ok;
    }

    protected function createPhpBlock($code, $statements = null) {

        if ($statements == null) {
            return '<?php ' . $code . ' ?>';
        }

        $code_format= array_pop($statements);
        array_unshift($code_format, $code);

        if (count($statements) == 0) {
            $php_string = call_user_func_array('sprintf', $code_format);
            return '<?php ' . $php_string . ' ?>';
        }

        $stmt_string= '';
        foreach ($statements as $stmt) {
            $stmt_string .= $this->newline() . $this->indent() . $stmt . ';';
        }

        $stmt_string .= $this->newline() . $this->indent();
        $stmt_string .= call_user_func_array('sprintf', $code_format);

        $php_str = '<?php ';
        $php_str .= $stmt_string;
        $php_str .= $this->newline() . $this->indent() . ' ?>';

        return $php_str;
    }

    protected function createCode($code) {

        if (func_num_args()>1) {
            $arguments = func_get_args();
            array_shift($arguments); // remove $code
            return $this->createPhpBlock($code, array($arguments));
        }

        return $this->createPhpBlock($code);
    }

    protected function interpolate($text) {
        $ok = preg_match_all('/(\\\\)?([#!]){(.*?)}/', $text, $matches, PREG_SET_ORDER);

        if (!$ok) {
            return $text;
        }

        $i=1; // str_replace need a pass-by-ref
        foreach ($matches as $m) {

            // \#{dont_do_interpolation}
            if (mb_strlen($m[1]) == 0) {
                if ($m[2] == '!') {
                    $code_str = $this->createCode('echo %s',$m[3]);
                }else{
                    $code_str = $this->createCode('echo htmlspecialchars(%s)',$m[3]);
                }
                $text = str_replace($m[0], $code_str, $text, $i);
            }
        }

        return str_replace('\\#{', '#{', $text);
    }

    protected function visitNode(Nodes\Node $node) {
        $fqn = get_class($node);
        $parts = explode('\\',$fqn);
        $name = $parts[count($parts)-1];
        $method = 'visit' . ucfirst(strtolower($name));
        return $this->$method($node);
    }

    protected function visitCasenode(Nodes\CaseNode $node) {
        $within = $this->withinCase;
        $this->withinCase = true;

        // TODO: fix the case hack
        // php expects that the first case statement will be inside the same php block as the switch
        $code_str = 'switch (%s) { '.$this->newline().$this->indent().'case "__phphackhere__": break;';
        $code = $this->createCode($code_str,$node->expr);
        $this->buffer($code);

        $this->indents++;
        $this->visit($node->block);
        $this->indents--;

        $code = $this->createCode('}');
        $this->buffer($code);
        $this->withinCase = $within;
    }

    protected function visitWhen(Nodes\When $node) {
        if ('default' == $node->expr) {
            $code = $this->createCode('default:');
            $this->buffer($code);
        }else{
            $code = $this->createCode('case %s:',$node->expr);
            $this->buffer($code);
        }

        $this->visit($node->block);

        $code = $this->createCode('break;');
        $this->buffer( $code . $this->newline());
    }

    protected function visitLiteral(Nodes\Literal $node) {
        $str = preg_replace('/\\n/','\\\\n',$node->string);
        $this->buffer($str);
    }

    protected function visitBlock(Nodes\Block $block) {
        foreach ($block->nodes as $k => $n) {
            $this->visit($n);
        }
    }

    protected function visitDoctype(Nodes\Doctype $doctype=null) {
        if (isset($this->hasCompiledDoctype)) {
            throw new \Exception ('Revisiting doctype');
        }
        $this->hasCompiledDoctype = true;

        if (empty($doctype->value) || $doctype == null || !isset($doctype->value)) {
            $doc = 'default';
        }else{
            $doc = strtolower($doctype->value);
        }

        if (isset($this->doctypes[$doc])) {
            $str = $this->doctypes[$doc];
        }else{
            $str = "<!DOCTYPE {$doc}>";
        }

        $this->buffer( $str . $this->newline());

        if (strtolower($str) == '<!doctype html>') {
            $this->terse = true;
        }

        $this->xml = false;
        if ($doc == 'xml') {
            $this->xml = true;
        }
    }

    protected function visitMixin(Nodes\Mixin $mixin) {
        $name       = preg_replace('/-/', '_', $mixin->name) . '_mixin';
        $arguments  = $mixin->arguments;
        $block      = $mixin->block;
        $attributes = $mixin->attributes;

        if ($mixin->call) {

            if (!count($attributes)) {
                $attributes = "(isset(\$attributes)) ? \$attributes : array()";
            }else{
                $_attr = array();
                foreach ($attributes as $data) {
                    if ($data['escaped'] === true) {
                        $_attr[$data['name']] = htmlspecialchars($data['value']);
                    }else{
                        $_attr[$data['name']] = $data['value'];
                    }
                }

                //TODO: this adds extra escaping, tests mixin.* failed.
                $attributes = var_export($_attr, true);
                $attributes = "array_merge({$attributes}, (isset(\$attributes)) ? \$attributes : array())";
            }

            if ($arguments === null || empty($arguments)) {
                $code = $this->createPhpBlock("{$name}({$attributes})");
            }else{

                if (!empty($arguments) && !is_array($arguments)) {
                    //$arguments = array($arguments);
                    $arguments = explode(',', $arguments);
                }

                array_unshift($arguments, $attributes);
                $statements= array($arguments);

                $variables = array_pop($statements);
                $variables = implode(', ', $variables);
                array_push($statements, $variables);

                $arguments = $statements;
                $code_format = "{$name}(%s)";
                array_unshift($arguments, $code_format);

                $code = $this->apply('createCode', $arguments);
            }
            $this->buffer($code);

        }else{
            if ($arguments === null || empty($arguments)) {
                $arguments = array();
            }
            else
            if (!is_array($arguments)) {
                $arguments = array($arguments);
            }

            //TODO: assign nulls to all varargs for remove php warnings
            array_unshift($arguments, '$attributes');
            $code = $this->createCode("function {$name} (%s) {", implode(',',$arguments));

            $this->buffer($code);
            $this->indents++;
            $this->visit($block);
            $this->indents--;
            $this->buffer($this->createCode('}'));
        }
    }

    protected function visitTag(Nodes\Tag $tag) {
        if (!isset($this->hasCompiledDoctype) && 'html' == $tag->name) {
            $this->visitDoctype();
        }

        $self_closing = (in_array(strtolower($tag->name), $this->selfClosing) || $tag->selfClosing) && !$this->xml;

        if ($tag->name == 'pre') {
            $pp = $this->prettyprint;
            $this->prettyprint = false;
        }

        if (count($tag->attributes)) {
            $open = '';
            $close= '';

            if ($self_closing) {
                $open = '<' . $tag->name . ' ';
                $close = ($this->terse) ? '>' : '/>';
            }else{
                $open = '<' . $tag->name . ' ';
                $close = '>';
            }

            $this->buffer($this->indent() . $open, false);
            $this->visitAttributes($tag->attributes);
            $this->buffer($close . $this->newline(), false);
        }else{
            $html_tag = '';

            if ($self_closing) {
                $html_tag = '<' . $tag->name . (($this->terse) ? '>' : '/>');
            }else{
                $html_tag = '<' . $tag->name . '>';
            }

            $this->buffer($html_tag);
        }

        if (!$self_closing) {
            $this->indents++;
            if (isset($tag->code)) {
                $this->visitCode($tag->code);
            }
            $this->visit($tag->block);
            $this->indents--;

            $this->buffer('</'. $tag->name . '>');
        }

        if ($tag->name == 'pre') {
            $this->prettyprint = $pp;
        }
    }

    protected function visitFilter(Nodes\Filter $node) {
        $filter = $node->name;

        // filter:
        if ($node->isASTFilter) {
            $str = Filter::$filter($node->block, $this, $node->attributes);
            // :filter
        }else{
            $str = '';
            foreach ($this->block->nodes as $n) {
                $str .= $n->value . "\n";
            }
            $str = Filter::$filter($str, $node->attributes);
        }
        $this->buffer($str);
    }

    protected function visitText(Nodes\Text $text) {
        $this->buffer($this->interpolate($text->value));
    }

    protected function visitComment(Nodes\Comment $comment) {
        if (!$comment->buffer) {
            return;
        }

        $this->buffer('<!--' . $comment->value . '-->');
    }

    protected function visitBlockComment(Nodes\BlockComment $comment) {
        if (!$comment->buffer) {
            return;
        }

        if (strlen($comment->value) && 0 === strpos(trim($comment->value), 'if')) {
            $this->buffer('<!--[' . trim($comment->value) . ']>');
            $this->visit($comment->block);
            $this->buffer('<![endif]-->');
        }else{
            $this->buffer('<!--' . $comment->value);
            $this->visit($comment->block);
            $this->buffer('-->');
        }
    }

    protected function visitCode(Nodes\Code $node) {
        $block  = !$node->buffer;
        $code   = trim($node->value);

        if ($node->buffer) {

            if ($node->escape) {
                $this->buffer($this->createCode('echo htmlspecialchars(%s)',$code));
            }else{
                $this->buffer($this->createCode('echo %s',$code));
            }
        }else{

            $php_open = implode('|',$this->phpOpenBlock);

            if (preg_match("/^[[:space:]]*({$php_open})(.*)/", $code, $matches)) {

                $code = trim($matches[2],'; ');
                while (($len = strlen($code)) > 1 && ($code[0] == '(' || $code[0] == '{') && ord($code[0]) == ord(substr($code, -1)) - 1) {
                    $code = trim(substr($code, 1, $len - 2));
                }

                $index       = count($this->buffer)-1;
                $conditional = '';


                if (isset($this->buffer[$index]) && false !== strpos($this->buffer[$index], $this->createCode('}'))) {
                    // the "else" statement needs to be in the php block that closes the if
                    $this->buffer[$index] = null;
                    $conditional .= '} ';
                }

                $conditional .= '%s';

                if (strlen($code) > 0) {
                    $conditional .= '(%s) {';
                    if($matches[1] == 'unless') {
                        $conditional = sprintf($conditional, 'if', '!(%s)');
                    } else {
                        $conditional = sprintf($conditional, $matches[1], '%s');
                    }

                    $this->buffer($this->createCode($conditional, $code));
                }else{
                    $conditional .= ' {';
                    $conditional = sprintf($conditional, $matches[1]);

                    $this->buffer($this->createCode($conditional));
                }

            }else{
                $this->buffer($this->createCode('%s', $code));
            }
        }

        if (isset($node->block)) {
            $this->indents++;
            $this->visit($node->block);
            $this->indents--;

            if (!$node->buffer) {
                $this->buffer($this->createCode('}'));
            }
        }
    }

    protected function visitEach($node) {

        //if (is_numeric($node->obj)) {
        //if (is_string($node->obj)) {
        //$serialized = serialize($node->obj);
        if (isset($node->alternative)) {
            $code = $this->createCode('if (isset(%s) && %s) {',$node->obj,$node->obj);
            $this->buffer($code);
            $this->indents++;
        }

        if (isset($node->key) && mb_strlen($node->key) > 0) {
            $code = $this->createCode('foreach (%s as %s => %s) {',$node->obj,$node->key,$node->value);
        }else{
            $code = $this->createCode('foreach (%s as %s) {',$node->obj,$node->value);
        }

        $this->buffer($code);

        $this->indents++;
        $this->visit($node->block);
        $this->indents--;

        $this->buffer($this->createCode('}'));

        if (isset($node->alternative)) {
                $this->indents--;
                $this->buffer($this->createCode('} else {'));
                $this->indents++;

                $this->visit($node->alternative);
                $this->indents--;

                $this->buffer($this->createCode('}'));
       }
    }

    protected function visitAttributes($attributes) {
        $items = array();
        $classes = array();

        foreach ($attributes as $attr) {
            $key = trim($attr['name']);
            $value = trim($attr['value']);

            if ($this->isConstant($value, $key == 'class')) {
                $value = trim($value,' \'"');
                if($value === 'undefined')
                    $value = 'null';
            }else{
                $json = json_decode(preg_replace("/'([^']*?)'/", '"$1"', $value));

                if ($json !== null && is_array($json) && $key == 'class') {
                    $value = implode(' ', $json);
                }
                elseif (in_array($key, array("checked", "selected", "readonly", "disabled"))) {
                    // boolean handling
                    $items[] = $this->createCode(" if (%1\$s) { echo \"$key='$key'\"; }", $value);
                    continue;
                }
                else{
                    // inline this in the tag
                    $pp = $this->prettyprint;
                    $this->prettyprint = false;

                    if ($key == 'class') {
                        $value = $this->createCode('$__ = %1$s; echo is_array($__) ? implode(" ", $__) : $__', $value);
                    }
                    elseif (strpos($key, 'data-') !== false) {
                        $value = $this->createCode('$__ = %1$s; echo is_array($__) ? json_encode($__) : $__', $value);
                    }else{
                        $value = $this->createCode('echo %1$s', $value);
                    }

                    $this->prettyprint = $pp;
                }
            }
            if ($key == 'class') {
                if($value !== 'false' && $value !== 'null' && $value !== 'undefined' && $value !== '')
                    array_push($classes, $value);
            }
            elseif ($value == 'true' || $attr['value'] === true) {
                if ($this->terse) {
                    $items[] = $key;
                }else{
                    $items[] = "{$key}='{$key}'";
                }
            }elseif ($value !== 'false' && $value !== 'null' && $value !== 'undefined') {
                $items[] = "{$key}='{$value}'";
            }
        }

        if (count($classes)) {
            $items[] = 'class=\'' . implode(' ', $classes) . '\'';
        }

        $this->buffer(implode(' ', $items), false);
    }
}
