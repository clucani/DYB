<?php

class emuConstantsGroup
{
    public $name, $description;

    protected $constants = array();

    public $value_counter = 1;

    public function __construct($name, $description = '')
    {
        $this->name = $name;

        if( $description )
            $this->description = $description;
    }

    public function addConstant( $name, $description, $value = null )
    {
        if( !$value ) $value = $name.'_'.$this->getValue();

        $this->constants[$name] = (object) array( 'description' => $description, 'value' => $value );
    }

    private function getValue()
    {
        return $this->value_counter++;
    }

    public function __get($member)
    {
        if( isset( $this->constants[$member] ) )
            return $this->constants[$member]['value'];

        $error = "Couldn't find constant $member";

        // Otherwise try and find the constant
        if( $similar = $this->findClosest($member) )
            $error .= ", did you mean - '$similar'";

        trigger_error( $error );
        return null;
    }

    public function getConstants()
    {
        return $this->constants;
    }

    private function findClosest($member)
    {

        // no shortest distance found, yet
        $shortest = -1;
        $closest = '';

        // loop through words to find the closest
        foreach ($constants as $constant_name => $value) {

            // calculate the distance between the input word,
            // and the current word
            $lev = levenshtein($member, $constant_name);

            // check for an exact match
            if ($lev == 0) {

                // closest constant_name is this one (exact match)
                $closest = $constant_name;
                $shortest = 0;

                // break out of the loop; we've found an exact match
                break;
            }

            // if this distance is less than the next found shortest
            // distance, OR if a next shortest constant_name has not yet been found
            if ($lev <= $shortest || $shortest < 0) {
                // set the closest match, and shortest distance
                $closest  = $constant_name;
                $shortest = $lev;
            }
        }

        return $closest;
    }

}

?>