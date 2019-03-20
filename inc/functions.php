<?php

// Prevent loading this file directly
defined( 'ABSPATH' ) || exit;

function learn_press_get_coupon_codes() {

	return apply_filters( 'learn_press/stripe-add-on/coupons', array(

				'pinebio' => array(

					'name'				=> 'Pine Bio Code',
					'description'		=> 'Lorem Ipsum',
					'slug'				=> 'pinebio',
					'discount'			=> 0.5

				),
				'totalfall' => array(

					'name'				=> 'Total fall',
					'description'		=> 'Lorem Ipsum',
					'slug'				=> 'totalfall',
					'discount'			=> 1

				),
				'tbiospecial' => array(

					'name'				=> 'T-Bio Special',
					'description'		=> 'Lorem Ipsum',
					'slug'				=> 'tbiospecial',
					'discount'			=> 0.3333333339

				)

	) );

}