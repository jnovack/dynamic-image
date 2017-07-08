<?php
/**
* Class Sanitize
* http://php.net/manual/en/filter.filters.misc.php#118741
* Custom filters for filter_var, filter_var_array, filter_input, filter_input_array
*/
class Sanitize {
    private $options = array();

    public function __construct(array $options=array()){
        $this->options = $options;
    }

    /**
     * Returns the default options merged with the construction options
     * @param array $defaults
     * @return array
     */
    private function get_options(array $defaults){
        return array_merge($defaults, $this->options);
    }

    /**
     * Checks if a value is in array
     * @param mixed $val Value provided by filter_var
     * @return mixed
     */
    public function FILTER_ENUM($val){
        $options = $this->get_options(
        // default options
            array(
                "values"  => array(),
                "strict"    => false, // Value to return on fail
                "default"   => null,  // Check value for correct type
                "cast"      => false  // Cast the value in a certain type
            )
        );

        if (in_array($val, $options["values"], $options["strict"])){

            // Return default if the value cant be cast as the right type
            if ( $options["cast"] && !settype($val, $options["cast"])){
                return $options["default"];
            }

            return $val;
        } else {
            return $options["default"];
        }
    }
}
?>