<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @package    productsize-chart-for-woocommerce
 * @subpackage productsize-chart-for-woocommerce/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    productsize-chart-for-woocommerce
 * @subpackage productsize-chart-for-woocommerce/public
 * @author     Nabaraj Chapagain <nabarajc6@gmail.com>
 */
class PRODUCT_SIZE_CHART_FOR_WOOCOMMERCE_Public {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.2
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.2
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

		/**
		 * The elements or assets of plugin.
		 *
		 * @since    1.0.2
		 * @access   private
		 * @var      array    $assets    The assets/settings of plugin like color,content etc.
		 */
	private $assets = array();

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.2
	 * @param      string $plugin_name       The name of the plugin.
	 * @param      string $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version     = $version;

	}

	/**
	 * convert hex to rgba
	 *
	 * @since    1.0.2
	 * @param      string $color      color code for e.g. #000000
	 * @param      string $opacity    opacity for rgba
	 */
	public function productsize_chart_hex2rgba( $color, $opacity = false ) {

		$default = 'rgb(0,0,0)';
		// Return default if no color provided
		if ( empty( $color ) ) {
			return $default;
		}
		// Sanitize $color if "#" is provided
		if ( $color[0] == '#' ) {
			$color = substr( $color, 1 );
		}

		// Check if color has 6 or 3 characters and get values
		if ( strlen( $color ) == 6 ) {
				$hex = array( $color[0] . $color[1], $color[2] . $color[3], $color[4] . $color[5] );
		} elseif ( strlen( $color ) == 3 ) {
				$hex = array( $color[0] . $color[0], $color[1] . $color[1], $color[2] . $color[2] );
		} else {
				return $default;
		}

		// Convert hexadec to rgb
		$rgb = array_map( 'hexdec', $hex );

		// Check if opacity is set(rgba or rgb)
		if ( $opacity ) {
			if ( abs( $opacity ) > 1 ) {
				$opacity = 1.0;
			}
			$output = 'rgba(' . implode( ',', $rgb ) . ',' . $opacity . ')';
		} else {
			$output = 'rgb(' . implode( ',', $rgb ) . ')';
		}
		// Return rgb(a) color string
		return $output;
	}

	/**
	 * convert hex to rgba
	 *
	 * @since    1.0.2
	 * @param      string $chart_content    display chart details with table
	 */
	public function productsize_chart_display_table( $chart_content ) {

		$chart = json_decode( $chart_content );
		if ( ! empty( $chart ) ) {
			echo "<table id='size-chart'>";
			foreach ( $chart as $chart_details ) {
				echo '<tr>';
				for ( $i = 0; $i < count( $chart_details ); $i++ ) {

					if ( 0 == $i ) {
						echo '<th>' . wp_kses_post( $chart_details[ $i ] ) . '</th>';
					} else {
						echo '<td>' . wp_kses_post( $chart_details[ $i ] ) . '</td>';
					}
				}
				echo '</tr>';
			}
			echo '</table>';
		}

	}

	/**
	 * chart assets
	 *
	 * @since    1.0.2
	 */

	public function productsize_chart_assets( $id ) {

		if ( ! empty( $this->assets ) ) {
			return $this->assets;
		}

		$chart_id     = get_post_meta( $id, 'prod-chart', true );
		$this->assets = array(
			'chart'       => get_post_meta( $id, 'prod-chart', true ),
			'label'       => get_post_meta( $chart_id, 'label', true ),
			'position'    => get_post_meta( $chart_id, 'position', true ),
			'content'     => get_post_meta( $chart_id, 'chart-content', true ),
			'text-color'  => get_post_meta( $chart_id, 'text-color', true ),
			'title-color' => get_post_meta( $chart_id, 'title-color', true ),
			'overlay'     => get_post_meta( $chart_id, 'overlay-color', true ),
			'padding'     => get_post_meta( $chart_id, 'chart-padding', true ),
			'chart-table' => get_post_meta( $chart_id, 'chart-table', true ),
			'chart-1'     => get_post_meta( $chart_id, 'chart-1', false ),
			'chart-2'     => get_post_meta( $chart_id, 'chart-2', false ),
		);

		$defaults = array(
			'text-color'  => '#000',
			'title-color' => '#000',
			'overlay'     => 0.7,
			'padding'     => 35,
		);

		$this->assets = wp_parse_args( $this->assets, $defaults );
		return $this->assets;

	}


	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.2
	 */

	public function productsize_chart_new_product_tab( $tabs ) {
		global $post;
		$assets = $this->productsize_chart_assets( $post->ID );

		if ( ! $assets['chart'] ) {
			return $tabs;

		}

		if ( $assets['position'] == 'tab' ) {
			$tabs['pcfw_tab'] = array(
				'title'    => $assets['label'],
				'priority' => 50,
				'callback' => array( $this, 'productsize_chart_new_product_tab_content' ),
			);
		}


		return $tabs;

	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.2
	 */

	public function productsize_chart_new_product_tab_content() {
		global $post;
		$assets = $this->productsize_chart_assets( $post->ID );
		require 'includes/productsize-chart-contents.php';
	}

	/**
	 * hook to display chart button after add to cart button for modal
	 *
	 * @since    1.0.2
	 */

	public function productsize_chart_chart_button() {
		global $post;
		$assets = $this->productsize_chart_assets( $post->ID );
		if ( ! $assets['chart'] ) {
			return;
		}
		if ( $assets['position'] == 'popup' ) { ?>
			<div><a href="#modal" class="button alt" id="chart-button"><?php esc_html_e( 'Size Chart', 'productsize-chart-for-woocommerce' ); ?></a></div>
			<div class="remodal" data-remodal-id="modal" role="dialog" aria-labelledby="modal1Title" aria-describedby="modal1Desc">
		  		<button data-remodal-action="close" class="remodal-close" aria-label="Close"></button>
		  		<div>
					<?php require 'includes/productsize-chart-contents.php'; ?>
				</div>
			</div>
		<?php }
	}


	public function productsize_chart_custom_style() {
		global $post;
		$assets = $this->productsize_chart_assets( $post->ID );
		?>
		<style>
			#size-chart {clear:both; margin:10px 0; width:100%}
			#size-chart tr th{font-weight:bold;}
			#size-chart tr td, #size-chart tr th{ color:<?php echo ! empty( $assets['text-color'] ) ? esc_attr($assets['text-color']) : '#000'; ?>;
			padding:8px; text-align:left;}
			.remodal p{color:<?php echo esc_attr( $assets['text-color'] ) ?>;}
			h2#modal1Title,h3#modal1Title{color:<?php echo esc_attr( $assets['title-color'] ); ?>;}
			#size-chart tr:nth-child(odd){ background:#ebe9eb; }
			.remodal-overlay {background: <?php echo esc_attr( $this->productsize_chart_hex2rgba( $assets['overlay'], 0.7 ) ) ?> !important; z-index:9999;}
		   	.remodal{padding:<?php echo absint($assets['padding']) . 'px'; ?>;}
		</style>    
		<?php
	}


	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.2
	 */
	public function productsize_chart_public_enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Plugin_Name_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Plugin_Name_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name . '-jquery-modal', plugin_dir_url( __FILE__ ) . 'css/remodal.css', array(), $this->version, 'all' );
		wp_enqueue_style( $this->plugin_name . '-jquery-modal-default-theme', plugin_dir_url( __FILE__ ) . 'css/remodal-default-theme.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.2
	 */
	public function productsize_chart_public_enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Plugin_Name_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Plugin_Name_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */
		wp_enqueue_script( $this->plugin_name . '-jquery-modal', plugin_dir_url( __FILE__ ) . 'js/remodal.js', array( 'jquery' ), $this->version, false );

	}

}
