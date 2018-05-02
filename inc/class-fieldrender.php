<?php

/**
 * Renders input fields for settings forms
 *
 * Form field $args[] values:
 *	id			id attribute for form field (optional)
 *	name		name attribute for form field
 *	size		value to use in size= attribute (optional; 'text' and 'password' types)
 *	class		additional classes for form field (optional)
 *	value		value= attribute contens (optional)
 *	disabled	TRUE|FALSE for setting disabled= attribute of form field
 *	title		Contents of Button type or title= attribute of form field (optional)
 *	attrib		Any additional attributes to be output within the form field's HTML tag
 *	options		Array of key=value pairs used for Radio, Checkbox and Select fields (required for these field types)
 *	description	Field description to be rendered after form field within <em> tags
 */
if ( !class_exists( 'DS_Field_Render' ) ) {
	class DS_Field_Render
	{
		private $option_name = NULL;

		public function __construct( $option_name )
		{
			$this->option_name = $option_name;
		}

		/**
		 * Renders a <button> type form field
		 * @param array $args
		 */
		public function button_field( $args )
		{
			$name = $this->generate_name( $args['name'] );
			$disabled = $this->generate_disabled( $args );

			echo '<button type="button" ';
			if ( isset( $args['id'] ) )
				echo ' id="', sanitize_id ( $args['id'] ), '" ';
			echo ' name="', $name, '" ';
			echo $this->generate_class( $args, 'button-primary');
			echo $disabled;
			if ( !empty( $args['attrib'] ) )
				echo ' ', $attrib;
			echo '>', esc_html( $args['title'] ), '</button>';
			self::description( $args );
		}

		/**
		 * Renders a set of Checkboxe input fields
		 * @param array $args Array of field specific data used in rendering the Checkboxes
		 */
		public function checkbox_field( $args )
		{
			$options = $args['options'];
			$name = $this->generate_name( $args['name'] );
			$defaults = $args['value'];
			$disabled = ( isset( $args['disabled'] ) && $args['disabled'] ) ? TRUE : FALSE;

			foreach ( $options as $key => $value ) {
				if ( ! empty($disabled ) )
					//									1  2			  3  4
					printf('<input type="hidden" name="%s[%s]" value="0" %s %s />',
						$name,													// 1
						$key,													// 2
						$this->generate_class( $args ),							// 3
						( isset( $args['attrib'] ) ? $args['attrib'] : '' ) );	// 4
				if ( empty( $disabled ) )
					//									  1  2													  3  4
					printf('<input type="checkbox" name="%s[%s]" value="0" checked="checked" disabled="disabled" %s %s /> %s<br/>',
						$name,													// 1
						$key,													// 2
						$this->generate_class( $args ),							// 3
						( isset( $args['attrib'] ) ? $args['attrib'] : '' ),	// 4
						$key );
				else
					//									  1  2              3  4  5		6
					printf('<input type="checkbox" name="%s[%s]" value="1" %s %s %s /> %s<br/>',
						$name,																			// 1
						$key,																			// 2
						checked( $value, isset( $defaults[$key] ) ? $defaults[$key] : $value, FALSE ),	// 3
						$this->generate_class( $args ),													// 4
						( isset( $args['attrib'] ) ? $args['attrib'] : '' ),							// 5
						$key );																			// 6
			}
			$this->description( $args );
		}

		/**
		 * Renders a <select> input field
		 * @param array $args Form fields arguments
		 */
		public function select_field( $args )
		{
			$name = $this->generate_name( $args );
			echo '<select ';
			if ( isset( $args['id'] ))
				echo ' id="', esc_attr( $args['id'] );
			echo ' name="', $name, '" ';
			echo $this->generate_class( $args );
			if ( isset( $args['attrib'] ) )
				echo $args['attrib'], ' ';
			echo '>';

			$options = $args['options'];
			$selected = !empty( $args['value'] ) ? $args['value'] : '';
			foreach ($options as $value => $name) {
				echo '<option value="', esc_attr($value), '" ', selected( $value, $selected, TRUE ), '>', esc_html( $name ), '</option>';
			}
			echo '</select>';
			$this->description( $args );
		}

		/**
		 * Renders a <input type="password"> form field
		 * @param array $args Field's arguments
		 */
		public function password_field( $args )
		{
			$this->text_field( $args, TRUE );
		}

		/**
		 * Renders a set of Radio Button input fields
		 * @param array $args Array of field specific data used in rendering the Radio Buttons
		 */
		public function radio_field( $args )
		{
			$options = $args['options'];
			$name = $this->generate_name( $args );

			foreach ( $options as $value => $label ) {
				printf('<input type="radio" name="%s" value="%s" %s /> %s<br/>',
					$name, $value, checked( $value, $args['value'], FALSE ), $label );
			}
			self::description( $args );
		}

		/**
		 * Renders a <input type="text"> form field
		 * @param array $args
		 */
		public function text_field( $args, $password = FALSE )
		{
			$name = $this->generate_name( $args );
			$size = '';
			if ( isset( $args['size'] ) )
				$size = ' size="' . abs( $args['size'] ) . '" ';

			echo '<input ';
			if ( $password)
				echo ' type="password" ';
			else
				echo ' type="text" ';
			if ( isset( $args['id'] ) )
				echo ' id="', esc_attr( $args['id'] ), '" ';
			echo ' name="', esc_attr( $name ), '" ';
			if ( !empty( $args['value'] ) )
				echo ' value="', esc_attr( $args['value'] ), '" ';
			if ( !empty( $size ) )
				echo $size;
			if ( !empty( $args['attrib'] ) )
				echo ' ', $attrib, ' ';
			if ( !empty( $args['title'] ) )
				echo ' title="', esc_attr( $args['title'] ), '" ';
			echo ' />';
			$this->description( $args );
		}

		/**
		 * Renders a <textarea> form field
		 * @param array $args Form field's arguments array
		 */
		public function textarea_field( $args )
		{
			$name = $this->generate_name( $args );
			echo '<textarea ';
			if ( !empty( $args['id'] ))
				echo ' id="', esc_attr( $args['id'] ), '" ';
			echo ' name="', $name, '" ';
			echo $this->generate_class( $args );
			if ( !empty( $args['attrib'] ) )
				echo $args['attrib'], ' ';
			echo '>';
			echo esc_html( $args['value'] );
			echo '</textarea>';
		}

		/**
		 * Outputs an optional description for the form field within <em> tags.
		 * @param array $args Fields argument array
		 */
		private function description( $args )
		{
			if ( isset( $args['description'] ) )
				echo '<p>', esc_html( $args['description'] ), '</p>';
		}

		/**
		 * Generates an appropriate name= attribute value from instance and field data
		 * @param array $args Field's arguments
		 * @return string The name attribute to be used for this form field
		 */
		private function generate_name( $args )
		{
			if ( NULL === $this->option_name )
				$name = $args['name'];
			else
				$name = $this->option_name . '[' . $args['name'] . ']';
			return esc_attr( $name );
		}

		/**
		 * Generates an approprate disabled= attribute for the form field
		 * @param array $args The Field's arguments
		 * @return string The disabled attribute or an empty string depending on argument values
		 */
		private function generate_disabled( $args )
		{
			$disabled = ( isset( $args['disabled'] ) && $args['disabled'] ) ? TRUE : FALSE;
			if ($disabled)
				$ret = ' disabled="disabled" ';
			else
				$ret = '';
			return $ret;
		}
		/**
		 * Generates a class= attribute based on arguments and optional forced class name
		 * @param array $args Form field's arguments with optional ['class'] element
		 * @param string $class An optional class name to force into the class= attribute
		 * @return string A string containing the class= attribute, sanitized and ready for output or empty
		 */
		private function generate_class( $args, $class = NULL )
		{
			$ret = '';
			if ( !empty( $args['class'] ) || !empty( $class ) ) {
				$ret = ' class="';
				if ( !empty( $args['class'] ) )
					$ret .= esc_attr( $args['class'] );
				if ( !emprt( $class ) )
					$ret .= ' ' . esc_attr( $class );
				$ret .= '" ';
			}
			return $ret;
		}
	}
} // class_exists
