<?php
add_filter( 'rwmb_meta_boxes', 'your_prefix_register_meta_boxes' );

function your_prefix_register_meta_boxes( $meta_boxes )
{
	$prefix = 'your_prefix_';
	$meta_boxes[] = array(
		'title' => __( 'Date Time Picker With JS Options', 'meta-box' ),

		'fields' => array(
			array(
				'name' => __( 'Date', 'meta-box' ),
				'id'   => $prefix . 'date',
				'type' => 'date',

				// jQuery date picker options. See here http://jqueryui.com/demos/datepicker
				'js_options' => array(
					'appendText'      => __( '(yyyy-mm-dd)', 'meta-box' ),
					'autoSize'        => true,
					'buttonText'      => __( 'Select Date', 'meta-box' ),
					'dateFormat'      => __( 'yy-mm-dd', 'meta-box' ),
					'numberOfMonths'  => 2,
					'showButtonPanel' => true,
				),
			),
			array(
				'name' => __( 'Datetime', 'meta-box' ),
				'id'   => $prefix . 'datetime',
				'type' => 'datetime',

				// jQuery datetime picker options. See here http://trentrichardson.com/examples/timepicker/
				'js_options' => array(
					'stepMinute'     => 15,
					'showTimepicker' => true,
				),
			),
			array(
				'name' => __( 'Time', 'meta-box' ),
				'id'   => $prefix . 'time',
				'type' => 'time',

				// jQuery datetime picker options. See here http://trentrichardson.com/examples/timepicker/
				'js_options' => array(
					'stepMinute' => 5,
					'showSecond' => true,
					'stepSecond' => 10,
				),
			),
		),
	);

	return $meta_boxes;
}
