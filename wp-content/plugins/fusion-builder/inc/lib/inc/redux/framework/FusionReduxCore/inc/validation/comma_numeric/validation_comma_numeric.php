<?php

	if ( ! class_exists( 'FusionRedux_Validation_comma_numeric' ) ) {
		class FusionRedux_Validation_comma_numeric {

			public $parent;
			public $field;
			public $value;
			public $current;
			public $error;

			/**
			 * Field Constructor.
			 * Required - must call the parent constructor, then assign field and value to vars, and obviously call the render field function
			 *
			 * @since FusionReduxFramework 1.0.0
			 */
			function __construct( $parent, $field, $value, $current ) {

				$this->parent       = $parent;
				$this->field        = $field;
				$this->field['msg'] = ( isset( $this->field['msg'] ) ) ? $this->field['msg'] : __( 'You must provide a comma separated list of numerical values for this option.', 'fusion-builder' );
				$this->value        = $value;
				$this->current      = $current;

				$this->validate();
			} //function

			/**
			 * Field Render Function.
			 * Takes the vars and outputs the HTML for the field in the settings
			 *
			 * @since FusionReduxFramework 1.0.0
			 */
			function validate() {

				$this->value = str_replace( ' ', '', $this->value );

				if ( ! is_numeric( str_replace( ',', '', $this->value ) ) ) {
					$this->value = ( isset( $this->current ) ) ? $this->current : '';
					$this->error = $this->field;
				}
			} //function
		} //class
	}
