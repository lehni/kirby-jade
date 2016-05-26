<?php

use Tale\Jade;

include('vendor/autoload.php');

class JadeRenderer extends Kirby\Component\Template {
  private $renderer;
  
  public function __construct(Kirby $kirby) {
    parent::__construct($kirby);
    
    $this->renderer = new Jade\Renderer([
        'cache_path' => $this->kirby->roots()->cache(),
        'paths' => [$this->kirby->roots()->templates()],
        'pretty' => true
    ]);
  }
  
  public function file($name) {
    $extensions = ['.jade', '.jd', '.php'];
    
    foreach ($extensions as $ext) {
      $file = $this->kirby->roots()->templates() . DS . str_replace('/', DS, $name) . $ext;
      
      if (file_exists($file))
        return $file;
    }
  }
  
  public function render($template, $data = [], $return = true) {
    
    // Use PHP logic if the template is PHP
    if (pathinfo($template->templateFile(), PATHINFO_EXTENSION) == 'php') {
      return parent::render($template, $data, $return);
    }
    
    if ($template instanceof Page) {
      $page = $template;
      $file = $page->template();
      $data = $this->data($page, $data);
    } else {
      $file = $template;
      $data = $this->data(null, $data);
    }

    // check for an existing template
    if(!file_exists($page->templateFile())) {
      throw new Exception('The template could not be found');
    }
    
    // merge and register the template data globally
    tpl::$data = array_merge(tpl::$data, $data);
    
    // load the template
    return $this->renderer->render($file, tpl::$data);
  }
}

$kirby->set('component', 'template', 'JadeRenderer');


