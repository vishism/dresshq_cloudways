<?php

if (!mad_custom_get_option('share-posts-enable'))
	return;

// Social Share Page
$image = esc_url(wp_get_attachment_url( get_post_thumbnail_id() ));
$permalink = esc_url( apply_filters( 'the_permalink', get_permalink() ) );
$title = esc_attr(get_the_title());
$extra_attr = 'target="_blank" ';
?>

<div class="share-links-wrapper v_centered">

	<span class="title"><?php _e('Share this', MAD_BASE_TEXTDOMAIN) ?>:</span>

	<div class="share-links">

		<?php if (mad_custom_get_option('share-posts-facebook')): ?>
			<a href="http://www.facebook.com/sharer.php?u=<?php echo $permalink ?>&amp;text=<?php echo $title ?>&amp;images=<?php echo $image ?>" <?php echo $extra_attr ?> title="<?php _e('Facebook', MAD_BASE_TEXTDOMAIN) ?>" class="share-facebook"><?php _e('Facebook', MAD_BASE_TEXTDOMAIN) ?></a>
		<?php endif; ?>

		<?php if (mad_custom_get_option('share-posts-twitter')): ?>
			<a href="https://twitter.com/intent/tweet?text=<?php echo $title ?>&amp;url=<?php echo $permalink ?>" <?php echo $extra_attr ?> title="<?php _e('Twitter', MAD_BASE_TEXTDOMAIN) ?>" class="share-twitter"><?php _e('Twitter', MAD_BASE_TEXTDOMAIN) ?></a>
		<?php endif; ?>

		<?php if (mad_custom_get_option('share-posts-linkedin')): ?>
			<a href="https://www.linkedin.com/shareArticle?mini=true&amp;url=<?php echo $permalink ?>&amp;title=<?php echo $title ?>" <?php echo $extra_attr ?> title="<?php _e('LinkedIn', MAD_BASE_TEXTDOMAIN) ?>" class="share-linkedin"><?php _e('LinkedIn', MAD_BASE_TEXTDOMAIN) ?></a>
		<?php endif; ?>

		<?php if (mad_custom_get_option('share-posts-googleplus')): ?>
			<a href="https://plus.google.com/share?url=<?php echo $permalink ?>" <?php echo $extra_attr ?> title="<?php _e('Google +', MAD_BASE_TEXTDOMAIN) ?>" class="share-googleplus"><?php _e('Google +', MAD_BASE_TEXTDOMAIN) ?></a>
		<?php endif; ?>

		<?php if (mad_custom_get_option('share-posts-pinterest')): ?>
			<a href="https://pinterest.com/pin/create/link/?url=<?php echo $permalink ?>&amp;media=<?php echo $image ?>" <?php echo $extra_attr ?> title="<?php _e('Pinterest', MAD_BASE_TEXTDOMAIN) ?>" class="share-pinterest"><?php _e('Pinterest', MAD_BASE_TEXTDOMAIN) ?></a>
		<?php endif; ?>

		<?php if (mad_custom_get_option('share-posts-vk')): ?>
			<a href="https://vk.com/share.php?url=<?php echo $permalink ?>&amp;title=<?php echo $title ?>&amp;image=<?php echo $image ?>&amp;noparse=true" <?php echo $extra_attr ?> title="<?php _e('VK', MAD_BASE_TEXTDOMAIN) ?>" class="share-vk"><?php _e('VK', MAD_BASE_TEXTDOMAIN) ?></a>
		<?php endif; ?>

		<?php if (mad_custom_get_option('share-posts-tumblr')): ?>
			<a href="http://www.tumblr.com/share/link?url=<?php echo $permalink ?>&amp;name=<?php echo urlencode($title) ?>&amp;description=<?php echo urlencode(get_the_excerpt()) ?>" <?php echo $extra_attr ?> title="<?php _e('Tumblr', MAD_BASE_TEXTDOMAIN) ?>" class="share-tumblr"><?php _e('Tumblr', MAD_BASE_TEXTDOMAIN) ?></a>
		<?php endif; ?>

		<?php if (mad_custom_get_option('share-posts-reddit')): ?>
			<a href="http://www.reddit.com/submit?url=<?php echo $permalink ?>&amp;title=<?php echo $title ?>" <?php echo $extra_attr ?> title="<?php _e('Reddit', MAD_BASE_TEXTDOMAIN) ?>" class="share-reddit"><?php _e('Reddit', MAD_BASE_TEXTDOMAIN) ?></a>
		<?php endif; ?>

		<?php if (mad_custom_get_option('share-posts-xing')): ?>
			<a href="https://www.xing-share.com/app/user?op=share;sc_p=xing-share;url=<?php echo $permalink ?>" <?php echo $extra_attr ?> title="<?php _e('Xing', MAD_BASE_TEXTDOMAIN) ?>" class="share-xing"><?php _e('Xing', MAD_BASE_TEXTDOMAIN) ?></a>
		<?php endif; ?>

	</div><!--/ .share-links-->

</div><!--/ .share-links-wrapper-->
