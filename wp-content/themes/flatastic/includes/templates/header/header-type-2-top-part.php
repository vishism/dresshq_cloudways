<!-- - - - - - - - - - - - Header Top Part- - - - - - - - - - - - - - -->

<?php if (mad_custom_get_option('header_top_part') == 'show'): ?>

<div class="h_top_part">
	<div class="container">
		<div class="row">
			<div class="col-md-4 col-sm-5 t_xs_align_c">

				<?php if (mad_is_shop_installed()): ?>

					<?php $accountPage = get_permalink(get_option('woocommerce_myaccount_page_id')); ?>

					<?php if (is_user_logged_in()): ?>

						<p>
							<?php
							global $current_user;
							get_currentuserinfo();
							$user_name = mad_get_user_name($current_user);
							?>

							<span class="welcome_username"><?php echo _e('Welcome visitor', MAD_BASE_TEXTDOMAIN) . ', ' . $user_name ?></span>
							<strong><a href="<?php echo esc_url($accountPage); ?>"><?php _e('My Account', MAD_BASE_TEXTDOMAIN) ?></a></strong>
							<span> / </span>
							<strong><a href="<?php echo wp_logout_url(esc_url(home_url())) ?>"><?php _e('Logout', MAD_BASE_TEXTDOMAIN) ?></a></strong>
						</p>

					<?php else: ?>

						<div class="bar-login">
							<a class="to-login" data-href="<?php echo esc_url($accountPage) ?>" href="<?php echo esc_url($accountPage); ?>"><?php _e('Login', MAD_BASE_TEXTDOMAIN); ?></a>
							<span> / </span>
							<a href="<?php echo esc_url($accountPage); ?>"><?php _e('Register', MAD_BASE_TEXTDOMAIN); ?></a>
						</div>

					<?php endif; ?>

				<?php else: ?>

					<?php if (is_user_logged_in()): ?>

						<p>
							<?php
							global $current_user;
							get_currentuserinfo();
							$user_name = mad_get_user_name($current_user);
							?>

							<span class="welcome_username"><?php echo _e('Welcome visitor', MAD_BASE_TEXTDOMAIN) . ', ' . $user_name ?></span>
							<a href="<?php echo wp_logout_url( esc_url(home_url()) ); ?>"><?php _e('Logout', MAD_BASE_TEXTDOMAIN) ?></a>
						</p>

					<?php else: ?>

						<p>
							<a href="<?php echo wp_login_url(); ?>"><?php _e('Login', MAD_BASE_TEXTDOMAIN) ?></a>
							<?php echo wp_register('', '', false); ?>
						</p>

					<?php endif; ?>

				<?php endif; ?>

			</div>
			<div class="col-md-4 col-sm-4 t_align_c t_xs_align_c">
				<?php if (mad_custom_get_option('show_call_us')): ?>
					<p><?php echo mad_custom_get_option('call_us', __('Call us toll free: <b>(123) 456-7890</b>', MAD_BASE_TEXTDOMAIN), true); ?></p>
				<?php endif; ?>
			</div>
			<div class="col-md-4 col-sm-3 t_align_r t_xs_align_c">

				<ul class="cart-list d_inline_b">

					<?php if (defined('ICL_LANGUAGE_CODE')): ?>
						<?php if (mad_custom_get_option('show_language')): ?>
							<li class="container3d">
								<?php echo MAD_WC_WPML_CONFIG::wpml_header_languages_list(); ?>
							</li>
						<?php endif; ?>
					<?php endif; ?>

					<?php
					$currency = '';
					if (function_exists( 'get_woocommerce_currency' )) {
						$currency = get_woocommerce_currency();
					}
					?>
					<?php if ($currency != ''): ?>
						<?php if (mad_custom_get_option('show_currency')): ?>
							<li class="container3d">
								<?php echo MAD_WC_CURRENCY_SWITCHER::output_switcher_html();  ?>
							</li>
						<?php endif; ?>
					<?php endif; ?>

				</ul><!--/ .cart-list-->

			</div>
		</div>
	</div>
</div><!--/ .h_top_part-->

<!-- - - - - - - - - - - - Header Bottom Part- - - - - - - - - - - - - - -->

<?php endif; ?>