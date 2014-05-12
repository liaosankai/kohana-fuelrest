<?php

defined('SYSPATH') or die('No direct script access.');

abstract class Controller_Hybrid extends Kohana_Controller_Rest {

    /**
     * @var  View  page template
     */
    public $template = 'template';

    /**
     * @var  boolean  auto render template
     * */
    public $auto_render = TRUE;

    /**
     * Loads the template [View] object.
     */
    public function before()
    {
        parent::before();
        // 如果不是 action 類型的，就關閉 auto_render
        if ($this->method != 'action') {
            $this->auto_render = FALSE;
        }
        if ($this->auto_render === TRUE) {
            // Load the template
            $this->template = View::factory($this->template);
        }
    }

    /**
     * Assigns the template [View] as the request response.
     */
    public function after()
    {
        if ($this->auto_render === TRUE) {
            $this->response->body($this->template->render());
        }

        parent::after();
    }

}
