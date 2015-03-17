<?php

abstract class TimberWPObject extends TimberCore implements TimberWPObjectInterface
{

    public $_can_edit;
    public $object_type;

    /**
     * Adds support for getting meta fields
     *
     * @return mixed
     */
    function __get( $field ) {

        if ( isset( $this->$field ) ) {
            return $this->$field;
        }

        if ( $meta_value = $this->meta( $field ) ) {
            return $this->$field = $meta_value;
        }

        if ( method_exists( $this, $field ) ) {
            return $this->$field = $this->$field();
        }
        
        return $this->$field = false;
    }

    /**
     * @param string  $key
     * @param mixed   $value
     */
    function update( $key, $value ) {
        update_metadata( $this->object_type, $this->ID, $key, $value );
    }

    /**
     * @return bool
     */
    function can_edit() {
        if ( isset( $this->_can_edit ) ) {
            return $this->_can_edit;
        }
        $this->_can_edit = false;
        if ( !function_exists( 'current_user_can' ) ) {
            return false;
        }
        if ( current_user_can( 'edit_post', $this->ID ) ) {
            $this->_can_edit = true;
        }
        return $this->_can_edit;
    }

    /**
     * @return array
     */
    function get_method_values() {
        $ret = array();
        $ret['can_edit'] = $this->can_edit();
        return $ret;
    }

}
