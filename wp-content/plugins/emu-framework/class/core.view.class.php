<?php

class emuView
{
    public $emuApp;
    public $vars = array();

    public function __construct($emuApp = null)
    {
        if( $emuApp ) $this->emuApp = $emuApp;
        $this->init();
    }

    public function init() {}

    public function __get( $member )
    {
        global $wpdb;

        switch( $member )
        {
            default:

                if( !isset( $this->vars[ $member ] ) ) return null;

                return $this->vars[ $member ];
        }
    }

    public function build()
    {

    }

    public function setVars($vars = array())
    {
        $this->vars = array_merge($this->vars, $vars);
    }
}


?>