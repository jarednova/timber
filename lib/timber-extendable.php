<?php
// Exit if accessed directly
if ( !defined ( 'ABSPATH' ) )
    exit;

class TimberExtendable extends TimberCore
{
    private $_context;

    public static function extend( $name, $value, $context = false ) {

        if ( !$context ) {
            $context = self::get_calling_script_dir();

            // All themes share a single context (as there can only be 1 active theme anyway)
            if ( strpos( $context, get_theme_root() ) === 0 ) {
                $context = get_theme_root();
            }

        } elseif ( 'theme' === $context ) {
            // Allow plugins to extend the theme context
            $context = get_theme_root();

        }

        if ( !isset( static::$_methods[$name] ) ) {
            static::$_methods[$name] = array();
        }

        static::$_methods[$name][$context] = $value;
    }

    /**
     * This magic method checks to see if a method has been added to the object,
     * and calls it.
     */
    public function __call( $field, $args ) {

        // First check the current class
        $class = get_called_class();
        do {

            // The root TimberExtendable *must* define the static property $_methods.
            // If a TimberExtendable does not define $_methods, all methods will be
            // assigned to the first parent TimberExtendable that has $_methods.

            if ( isset( $class::$_methods ) && isset( $class::$_methods[$field] ) ) {
                foreach( $class::$_methods[$field] as $context => $method ) {

                    // Contexts are a 'root'. So the context ABSPATH . '/wp-content'
                    // will apply to all code run from plugins or themes. Default
                    // context is THEME_ROOT.

                    if ( strpos( $this->_context, $context ) === 0 ) {
                        array_splice( $args, 0, 0, array( $this ) );
                        return call_user_func_array( $method, $args );
                    }
                }
            }
        } while( $class = get_parent_class( $class ) );

    }

    /**
     * @return boolean|string
     */
    private static function get_calling_script_dir($offset = 0) {
        $caller = self::get_calling_script_file($offset);
        if (!is_null($caller)){
            $pathinfo = pathinfo($caller);
            $dir = $pathinfo['dirname'];
            return $dir;
        }
        return null;
    }

    /**
     * @param int $offset
     * @return string|null
     * @deprecated since 0.20.0
     */
    private static function get_calling_script_file($offset = 0) {
        $caller = null;
        $backtrace = debug_backtrace();
        $i = 0;
        foreach ($backtrace as $trace) {
            if ( isset( $trace['file'] ) && strpos( $trace['file'], TIMBER_LOC ) !== 0 ) {
                $caller = $trace['file'];
                break;
            }
            $i++;
        }
        if ($offset){
            $caller = $backtrace[$i + $offset]['file'];
        }
        return $caller;
    }

    protected function __construct( $context = false ) {
        $this->_init_extendable( $context );
    }

    protected function _init_extendable( $context = false ) {
        if ( !$context ) {
            $context = self::get_calling_script_dir();
        }

        // When in doubt, assume theme context
        if ( 'theme' === $context || empty( $context ) ) {
            $context = get_stylesheet_directory();
        }

        $this->_context = $context;
    }

}