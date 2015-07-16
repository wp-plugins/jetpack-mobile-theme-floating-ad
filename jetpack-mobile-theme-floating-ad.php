<?php
/*
 * Plugin Name: Jetpack Mobile Theme Floating Ad
 * Plugin URI: http://jetpack.me/support/mobile-theme/
 * Description: Display a floating ad banner while using Jetpack Mobile Theme.
 * Version: 1.0.0
 * Author: Equus Assets
 * Author URI: http://equusassets.com
 * Text Domain: jp-floating-ad
 */

/* Credit in large part goes to Mobile Theme Ads for Jetpack plugin (https://wordpress.org/plugins/jetpack-mobile-theme-ads/) developer jeherve (http://profiles.wordpress.org/jeherve) */

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

class Jetpack_Mobile_Theme_Floating_Ad
{
  private static $instance = false;

  private function __construct() {
    add_action( 'wp_head', array($this, 'jp_floating_ad_maybe_add_filter'));
    add_filter( 'jp_floating_ad_output', array($this, 'jp_floating_ad_custom_code'));
    add_action( 'admin_init', array($this, 'jp_floating_ad_init'));
    add_action( 'admin_menu', array($this, 'jp_floating_ad_add_page'));
  }

  public static function getInstance() {
    if (!self::$instance)
      self::$instance = new self;
    return self::$instance;
  }

  // Check if we are on mobile
  private function jp_floating_ad_is_mobile() {
  	// Are Jetpack Mobile functions available?
  	if ( ! function_exists( 'jetpack_is_mobile' ) ) {
  		return false;
  	}
  
  	// Is Mobile theme showing?
  	if ( isset( $_COOKIE['akm_mobile'] ) && $_COOKIE['akm_mobile'] == 'false' ) {
  		return false;
  	}
  
  	return jetpack_is_mobile();
  }

  // On Mobile, and on a page selected in the Mobile Ads options? Show the ads
  public function jp_floating_ad_maybe_add_filter() {
  
  	// Are we on Mobile
  	if ( $this->jp_floating_ad_is_mobile() ) :
  
  	$options = get_option( 'jp_floating_ad_strings' );
  
  	if ( isset( $options['show']['front'] ) && (is_home() || is_front_page() || is_search() || is_archive()) ) {
  		add_filter( 'wp_footer', array($this, 'jp_floating_ad_show_floating_ad' ));
  	}
  
  	else if ( isset( $options['show']['post'] ) && is_single() ) {
  		add_filter( 'wp_footer', array($this, 'jp_floating_ad_show_floating_ad' ));
  	}
  
  	else if ( isset( $options['show']['page'] ) && is_page() ) {
  		add_filter( 'wp_footer', array($this, 'jp_floating_ad_show_floating_ad' ));
  	}
  
  	endif; // End check if we're on mobile
  
  }
  
  // Display Ads
  public function jp_floating_ad_show_floating_ad() {
  	$options = get_option( 'jp_floating_ad_strings' );
  
    /* TODO: support user specified ad width and height
    if (isset($options['google_ad_width']) && isset($options['google_ad_height']))
      $ad_dimensions = 'width:'.$options['google_ad_width'].'px;height:'.$options['google_ad_height'].'px';
    */

  	$ads = '
  	<script async src="//pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"></script>
  	<ins class="adsbygoogle adslot_1" style="display:inline-block" data-ad-client="'. $options['google_ad_client'] .'" data-ad-slot="'. $options['google_ad_slot'] .'" data-ad-format="auto"></ins>
  	<script>
  		(adsbygoogle = window.adsbygoogle || []).push({});
  	</script>
  	';
  
  	// Allow custom ads instead of Google Adsense
  	$ads = apply_filters( 'jp_floating_ad_output', $ads );
  
  	echo $ads;
  }
  
  // Add the option to use custom ad embed code
  public function jp_floating_ad_custom_code( $ads ) {
  	$options = get_option( 'jp_floating_ad_strings' );

  	// Wrap the ads around a div, for styling
  	$ad_wrap_begin = '<div class="jp_floating_ad" style="width:100%;max-width:100%;text-align:center;position:fixed;'.$options['float_align'].':0;z-index:'.$options['z_index'].';">';
  	$ad_wrap_end = '</div>';
  
  	if ( ! empty( $options['custom_ad_code'] ) ) {
  		return $ad_wrap_begin.$options['custom_ad_code'].$ad_wrap_end;
  	} else if (!empty($options['google_ad_client']) && !empty($options['google_ad_slot'])) {
  		return $ad_wrap_begin.$ads.$ad_wrap_end;
  	} else {
      return ''; // user has not supplied any ad info, do not show the ad
    }
  }


  /*
   * Options page
   */
  // Init plugin options
  public function jp_floating_ad_init() {
  	register_setting( 'jp_floating_ad_options', 'jp_floating_ad_strings', array($this, 'jp_floating_ad_validate' ));
  }
  
  // Add menu page
  public function jp_floating_ad_add_page() {
  	add_options_page( __( 'Jetpack Mobile Floating Ad', 'jp_floating_ad' ), __( 'Jetpack Mobile Floating Ad', 'jp_floating_ad' ), 'manage_options', 'jp_floating_ad', array($this, 'jp_floating_ad_do_page') );
  }
  
  // Draw the menu page itself
  public function jp_floating_ad_do_page() {
  	?>
  	<div class="wrap">
  		<h2><?php _e( 'Jetpack Mobile Theme Floating Ad Settings', 'jp_floating_ad' ); ?></h2>
  
  		<?php if ( ! class_exists( 'Jetpack' ) || ! Jetpack::is_module_active( 'minileven' ) ) : ?>
  			<div class="error"><p>
  				<?php
  				printf(__( 'To use the Jetpack Mobile Theme Floating Ad plugin, you\'ll need to install and activate <a href="%1$s">Jetpack</a> first, and <a href="%2$s">activate the Mobile Theme module</a>.'),
  				'plugin-install.php?tab=search&s=jetpack&plugin-search-input=Search+Plugins',
  				'admin.php?page=jetpack_modules',
  				'jp_floating_ad'
  				);
  				?>
  			</p></div>
  		<?php endif; // End check if Jetpack and the Mobile Theme are active ?>
  
  		<form method="post" action="options.php">
  			<?php
  
  			settings_fields( 'jp_floating_ad_options' );
  			$options = get_option( 'jp_floating_ad_strings' );
  
  			// Default to align floating ad to bottom of screen
  			if ( ! isset( $options['float_align'] ) ) {
  				$options['float_align'] = 'bottom';
  			}
  
  			// Default to show ads on singular pages
  			if ( ! isset( $options['show'] ) ) {
  				$options['show'] = array(
  					'front' => 0,
  					'post'  => 1,
  					'page'  => 1,
  				);
  			}

        // Default z-index of floating ad to 1000
        if (!isset($options['z_index'])) {
          $options['z_index'] = 1000;
        }
  			?>
  
  			<h3><?php _e( 'Ad placement', 'jp_floating_ad' ); ?></h3>
  
  			<table class="form-table">
  				<tr valign="top"><th scope="row"><?php _e( 'Align floating ad to top or bottom of screen?', 'jp_floating_ad' ); ?></th>
  					<td>
              <label>
  					  <input type="radio" name="jp_floating_ad_strings[float_align]" value="top" <?php checked( 'top', $options['float_align'], true ); ?> />
              <?php _e('Top', 'jp_floating_ad'); ?>
              </label>
              <br/>
              <input type="radio" name="jp_floating_ad_strings[float_align]" value="bottom" <?php checked( 'bottom', $options['float_align'], true ); ?> />
              <?php _e('Bottom', 'jp_floating_ad'); ?>
              </label>
            </td>
  				</tr>
  				<tr valign="top">
  					<th scope="row"><?php _e( 'Show ads on:', 'jp_floating_ad' ); ?></th>
  					<td>
  						<label>
  						<input type="checkbox" name="jp_floating_ad_strings[show][front]" value="1" <?php checked( 1, $options['show']['front'], true ); ?> />
  						<?php _e( 'Front Page, Archive Pages, and Search Results', 'jp_floating_ad' ); ?>
  						</label>
  						<br>
  						<label>
  						<input type="checkbox" name="jp_floating_ad_strings[show][post]" value="1" <?php checked( 1, $options['show']['post'], true ); ?> />
  						<?php _e( 'Posts', 'jp_floating_ad' ); ?>
  						</label>
  						<br>
  						<label>
  						<input type="checkbox" name="jp_floating_ad_strings[show][page]" value="1" <?php checked( 1, $options['show']['page'], true ); ?> />
  						<?php _e( 'Pages', 'jp_floating_ad' ); ?>
  						</label>
  					</td>
  				</tr>
  			</table>
  
  			<h3><?php _e( 'Ad code', 'jp_floating_ad' ); ?></h3>
  
  			<p><?php _e( 'If you want to add Google Adsense ads, fill in the fields below:', 'jp_floating_ad' ); ?></p>
  
  			<table class="form-table">
  				<tr valign="top"><th scope="row"><?php _e( 'Enter your google_ad_client ID here:', 'jp_floating_ad' ); ?></th>
  					<td><input type="text" name="jp_floating_ad_strings[google_ad_client]" value="<?php echo $options['google_ad_client']; ?>" /></td>
  				</tr>
  				<tr valign="top"><th scope="row"><?php _e( 'Enter your google_ad_slot ID here:', 'jp_floating_ad' ); ?></th>
  					<td><input type="text" name="jp_floating_ad_strings[google_ad_slot]" value="<?php echo $options['google_ad_slot']; ?>" /></td>
  				</tr>
          <?php /* TODO: support user specified ad width and height
  				<tr valign="top"><th scope="row"><?php _e( 'Enter the ad width here:', 'jp_floating_ad' ); ?></th>
  					<td><input type="text" name="jp_floating_ad_strings[google_ad_width]" value="<?php echo $options['google_ad_width']; ?>" /></td>
  				</tr>
  				<tr valign="top"><th scope="row"><?php _e( 'Enter the ad height here:', 'jp_floating_ad' ); ?></th>
  					<td><input type="text" name="jp_floating_ad_strings[google_ad_height]" value="<?php echo $options['google_ad_height']; ?>" /></td>
  				</tr>
          */ ?>
  			</table>
  
  			<h3><?php _e( 'Custom ads', 'jp_floating_ad' ); ?></h3>
  
  			<p><?php _e( 'If you want to use another ad network or your own custom ads, you can enter a custom ad embed code below:', 'jp_floating_ad' ); ?></p>
  
  			<table class="form-table">
  				<tr valign="top">
  					<td colspan="2"><textarea class="widefat" id="jp_floating_ad_custom_code" name="jp_floating_ad_strings[custom_ad_code]" rows="8" cols="20"><?php
  						if ( isset( $options['custom_ad_code'] ) ) {
  							echo $options['custom_ad_code'];
  						}
  						?></textarea></td>
  				</tr>
          <?php /* TODO: support user specified ad width and height
  				<tr valign="top"><th scope="row"><?php _e( 'Enter custom ad width here:', 'jp_floating_ad' ); ?></th>
  					<td><input type="text" name="jp_floating_ad_strings[custom_ad_width]" value="<?php echo $options['custom_ad_width']; ?>" /></td>
  				</tr>
  				<tr valign="top"><th scope="row"><?php _e( 'Enter custom ad height here:', 'jp_floating_ad' ); ?></th>
  					<td><input type="text" name="jp_floating_ad_strings[custom_ad_height]" value="<?php echo $options['custom_ad_height']; ?>" /></td>
  				</tr>
          */ ?>
  			</table>

  			<h3><?php _e( 'Advanced', 'jp_floating_ad' ); ?></h3>
  
  			<p><?php _e( 'If the ad is displaying fine, you should ignore this.', 'jp_floating_ad' ); ?></p>

  			<table class="form-table">
  				<tr valign="top">
  					<th scope="row"><?php _e( 'Floating ad z-index:', 'jp_floating_ad' ); ?></th>
  					<td><input type="text" name="jp_floating_ad_strings[z_index]" value="<?php echo $options['z_index']; ?>" /></td>
          </tr>
        </table>
  
  			<p class="submit">
  				<input type="submit" class="button-primary" value="<?php esc_attr_e( 'Save configuration', 'jp_floating_ad' ); ?>" />
  			</p>
  		</form>
  	</div>
  	<?php
  }
  
  // Sanitize and validate input. Accepts an array, return a sanitized array.
  public function jp_floating_ad_validate( $input ) {
  
  	$input['google_ad_client']  = wp_filter_nohtml_kses( $input['google_ad_client'] );
  	$input['google_ad_slot']    = sanitize_key( $input['google_ad_slot'] );
    /* TODO: support user specified ad width and height
    if (isset($input['google_ad_width']) && !empty($input['google_ad_width']))
  	  $input['google_ad_width']   = absint( $input['google_ad_width'] );
    if (isset($input['google_ad_height']) && !empty($input['google_ad_height']))
  	  $input['google_ad_height']  = absint( $input['google_ad_height'] );
    if (isset($input['custom_ad_width']) && !empty($input['custom_ad_width']))
  	  $input['custom_ad_width']   = absint( $input['custom_ad_width'] );
    if (isset($input['custom_ad_height']) && !empty($input['custom_ad_height']))
  	  $input['custom_ad_height']  = absint( $input['custom_ad_height'] );
    */
  	$input['z_index']  = absint( $input['z_index'] );
  
  	return $input;
  }
}

$Jetpack_Mobile_Theme_Floating_Ad = Jetpack_Mobile_Theme_Floating_Ad::getInstance();
?>
