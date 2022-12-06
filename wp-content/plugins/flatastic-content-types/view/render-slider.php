<?php if (!empty($portfolio_images)): ?>

	<?php
		$slideshow = $params[0]['slideshow'];
		$slideshowSpeed = !empty($params[0]['slideshowSpeed']) ? $params[0]['slideshowSpeed'] : 5000;
	?>

	<?php if (!empty($portfolio_images)): ?>

		<div class="full-slider">

			<div class="portfolio-single-slider">
				<ul class="slides">
					<?php foreach ($portfolio_images as $image): ?>
						<li><img src="<?php echo esc_url($image['url']) ?>" alt="<?php echo esc_attr($image['title']) ?>"></li>
					<?php endforeach; ?>
				</ul><!--/ .slides-->
			</div><!--/ .portfolio-single-slider -->

			<script>

				(function ($) {

					$(function () {

						var $sl = $('.portfolio-single-slider ul.slides');

						if ($sl.length) {
							$sl.owlCarousel({
								singleItem: true,
								theme : "owl-theme",
								autoHeight: true,

								// Autoplay
								autoPlay : <?php echo ($slideshow) ? $slideshowSpeed : 'false' ?>,
								stopOnHover : true,

								// Navigation
								navigation : true,
								rewindNav : true,
								scrollPerPage : false,

								//Pagination
								pagination : true,
								paginationNumbers: false
							});

						}

					});

				})(jQuery);

			</script>

		</div><!--/ .full-slider -->

	<?php endif; ?>

<?php endif; ?>


