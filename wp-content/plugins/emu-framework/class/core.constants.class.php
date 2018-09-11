<?php

class emuConstants
{
    protected $emuApp;

    public $constant_groups = array();

    public function __construct( $emuApp )
    {
        $this->emuApp = $emuApp;

        $this->addConstants();
    }

    public function createConstantGroup( $name, $description )
    {
        $group = $this->emuApp->getInstance('emuConstantsGroup', array( $name, $description ) );
        $this->constant_groups[$name] = $group;
        return $group;
    }

    public function addConstants() { }

    public function getGroups()
    {
        return $this->constant_groups;
    }

}

?>