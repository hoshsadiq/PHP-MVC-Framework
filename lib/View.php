<?php

/**
 * Template class
 *
 * @package overheard
 * @author Hosh Sadiq, Simon Stac
 * @copyright 2013
 */

class View
{
    private $files = array();
    private $vars = array();

    public function vars($vars)
    {
        // add variables regardless of whether any are already present
        $this->vars = array_merge($this->vars, $vars);
    }

    public function do_head()
    {
        Event::fire('head');
    }

    public function do_footer()
    {
        Event::fire('footer');
    }

    public function render($name, $vars = array())
    {
        // errors are available universally 
        $this->vars['errors'] = array_map('html', Session::error());
        $this->vars['messages'] = array_map('html', Session::message());
        // extract template vars.
        extract($this->vars);
        extract($vars);

        require ABSPATH . DS . 'html' . DS . 'header.tpl.php';
        require ABSPATH . DS . 'html' . DS . $name . '.tpl.php';
        require ABSPATH . DS . 'html' . DS . 'footer.tpl.php';
    }

    public function partial_render($name, $vars = array())
    {
        // errors are available universally 
        $this->vars['errors'] = array_map('html', Session::error());
        $this->vars['messages'] = array_map('html', Session::message());
        // extract template vars.
        extract($this->vars);
        extract($vars);

        require ABSPATH . DS . 'html' . DS . $name . '.tpl.php';
    }

    public function contents($name, $vars = array())
    {
        ob_start();
        $this->render($name, $vars);
        return ob_get_clean();
    }

    public function partial_contents($name, $vars = array())
    {
        ob_start();
        $this->partial_render($name, $vars);
        return ob_get_clean();
    }
}
