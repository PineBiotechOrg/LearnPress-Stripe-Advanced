;(function ($) {

	"use strict";

	function _coupon_ready() {

		var $checkout_form = $( "#learn-press-checkout" );

		$checkout_form.on( 'click', '.coupon-code-button', function( e ) {

			e.preventDefault();

			var $coupon_code_input = $( "#learn-press-stripe-payment-coupon-code" ),
				$coupon_code_btn = $( "#learn-press-stripe-payment-coupon-code-btn" ),
				$course_price_value = $( "#learn-press-course .course-price .value" ),
				$course_order_review_table = $( ".learn-press-checkout-review-order-table" ),
				$course_total = $course_order_review_table.find( '.cart-item .course-total' ),
				$checkout_order_action_btn = $( "#checkout-order-action" ),
				$apply_coupon_input_btn = $( ".apply-coupon-input-btn" ),
				coupon_code = $.trim( $coupon_code_input.val() );

			console.log( coupon_code );

			if( coupon_code != '' ) {

				$( this ).data( 'applied_coupon', false );

				$coupon_code_btn.prop('disabled', true);

				$.ajax({
	                url: window.location.href,
	                type: 'post',
	                dataType : 'html',
	                data: {

	                	'lp-ajax' 	: 'apply_coupon',
	                	coupon_code : coupon_code

	                },
	                beforeSend: function() {

	        			$( this ).data( 'applied_coupon', false );

	        			$coupon_code_btn.addClass( "loading" ).text( 'Applying' );

	                },
	                error: function ( error, data, y) {
	                	console.log( error, data, y );
	                	$( this ).data( 'applied_coupon', false );
	                    $coupon_code_btn.removeClass('loading');
	                },
	                success: function (response) {

	                	response = LearnPress.parseJSON(response);

	                	$coupon_code_btn.prop('disabled', false);

	                	if( response.success ) {

		                	var new_price = response.final_amount_formatted,
		                		old_price = response.old_amount_formatted,
		                		savings = response.discount_percentage + ' savings';

		                    $( this ).data( 'applied_coupon', true );

		                    $coupon_code_btn.removeClass('loading').text( 'Apply coupon' );
		                    $course_price_value.addClass( "has-origin" ).html( "<span class='course-origin-price'>"+old_price+"</span>" + new_price );
		                    $course_total.html( new_price );

		                    $( '.coupon-savings' ).remove();

		                    $checkout_order_action_btn.append( '<span class="coupon-savings"> ' + savings + '</span>' );
		                    $apply_coupon_input_btn.append( '<span class="coupon-savings"> ' + savings + '</span>' );

		                    // $view_cart.removeClass('hide-if-js');
		                    // if (typeof response.redirect === 'string') {
		                    //     window.location.href = response.redirect;
		                    // }

	                	} else {

	                		var data = LearnPress.parseJSON( response.data ),
	                			old_price = data.old_amount_formatted;

	                		console.log( response );

	                		console.log( data );

	                		$( this ).data( 'applied_coupon', false );

		                    $coupon_code_btn.removeClass('loading').text( 'Apply coupon' );
		                    $course_price_value.removeClass( "has-origin" ).html( old_price );
		                    $course_total.html( old_price );

		                    $( '.coupon-savings' ).remove();

	        				$coupon_code_btn.removeClass( "loading" ).text( 'Apply coupon' );

	                	}

	                }
	            });

			} else {

				console.log( 'Coupon is blank' );

			}

		});

	}

	 $(document).ready(_coupon_ready);

})(jQuery);