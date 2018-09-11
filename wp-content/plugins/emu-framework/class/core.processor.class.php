<?php

interface emuFormProcessor
{
    public function process();
}

abstract class emuProcessor implements emuFormProcessor
{
    public $error;
    public $messages = array();
    protected $button;
    protected $section;

    protected $hasRequiredFields = true;
    public $missingFields = array();
    protected $emuApp;

    public function __construct( $emuApp = null )
    {
        $this->emuApp = $emuApp;
        $this->button = request_val('e-button');
        $this->section = request_val('e-section');
        $this->init();
    }

    public function init() {}

    public function checkRequiredFields( $required_fields = null )
    {
        if( !$required_fields ) $required_fields = $this->requiredFields;

        foreach( $this->requiredFields as $field )
        {
            if( is_array( $field ) ) // then it's an OR case
            {
                $OR_match = false;

                foreach( $field as $or_field_case )
                {
                    if( strlen( trim( request_val( $or_field_case ) ) ) > 0 ) $OR_match = true;
                }

                if( !$OR_match ) // then neither of the OR cases was true so fail the OR requirement
                {
                    $this->hasRequiredFields = false;
                    $this->missingFields = array_merge( $this->missingFields, $field );
                }
            }
            else
            {
                if( request_val( $field ) === '' )
                {
                    $this->hasRequiredFields = false;
                    $this->missingFields[] = $field;
                }
            }
        }
    }

    public function sanitize( $string )
    {
        if( is_array( $string ) )
        {
            foreach( $string as $key => $value )
                $string[$key] = $this->sanitize( $value );

            return $string;
        }

        return htmlentities($string);
    }

}
?>