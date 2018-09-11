<?php

class emuHelper
{
    protected $emuApp;

    public function __construct( $emuApp )
    {
        $this->emuApp = $emuApp;
        $this->init();
    }

    public function init() {}

}

?>