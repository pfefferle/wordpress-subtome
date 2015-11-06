<?php
/**
 * Plugin Name: SubToMe
 * Plugin URI: http://www.subtome.com/
 * Description: A plugin to integrate a SubToMe button to your blog. This button is a universal subscribe button and will let your readers pick the subscription tool of their choice.
 * Version: 1.5.1-dev
 * Author: Julien Genestoux
 * Author URI: http://superfeedr.com/
 * Author Email: julien@superfeedr.com
 * Text Domain: subtome
 * Domain Path: /lang/
 * Network: false
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 *
 * Copyright 2013 Superfeedr (julien@superfeedr.com)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2, as
 * published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.	See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public Licensealong with this program; if not, write to the Free
 * Software Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA
 */

add_action( 'widgets_init', create_function( '', 'register_widget("SubToMeWidget");' ) );
add_action( 'init', array( 'SubToMePlugin', 'init' ) );

/**
 * SubToMe widget class
 *
 * @author Julien Genestoux
 * @author Matthias Pfefferle
 */
class SubToMeWidget extends WP_Widget {

	/**
	 * Specifies the classname and description, instantiates the widget,
	 * loads localization files, and includes necessary stylesheets and JavaScript.
	 */
	public function __construct() {

		// load plugin text domain
		add_action( 'init', array( $this, 'widget_textdomain' ) );

		parent::__construct(
			'subtome',
			__( 'SubToMe', 'subtome' ),
			array(
				'classname' => 'widget_subtome',
				'description' => __( 'Universal Subscribe Button.', 'subtome' ),
			)
		);
	} // end constructor

	/**
	 * Outputs the content of the widget.
	 *
	 * @param	array args			The array of form elements
	 * @param	array instance	The current instance of the widget
	 */
	public function widget( $args, $instance) {

		extract( $args, EXTR_SKIP );

		$title = empty( $instance['title'] ) ? '' : $instance['title'];
		$caption = empty( $instance['caption'] ) ? 'Subscribe' : $instance['caption'];
		$description = empty( $instance['description'] ) ? null : $instance['description'];

		echo $before_widget;

		if ( $title ) {
			echo $before_title . $title . $after_title;
		}
		?>

		<?php
		echo SubToMePlugin::generate_button( null, $caption, $description );

		echo $after_widget;
	} // end widget

	/**
	 * Processes the widget's options to be saved.
	 *
	 * @param	array new_instance	The previous instance of values before the update.
	 * @param	array old_instance	The new instance of values to be generated via the update.
	 */
	public function update( $new_instance, $old_instance ) {
		$instance = $old_instance;

		$instance['title'] = attribute_escape( $new_instance['title'] );
		$instance['caption'] = attribute_escape( $new_instance['caption'] );
		$instance['description'] = attribute_escape( $new_instance['description'] );

		return $instance;
	} // end widget

	/**
	 * Generates the administration form for the widget.
	 *
	 * @param array instance	The array of keys and values for the widget.
	 */
	public function form( $instance ) {
		$instance = wp_parse_args(
			(array) $instance,
			array(
				'title' => __( 'SubToMe', 'subtome' ),
				'caption' => SubToMePlugin::get_button_caption(),
				'description' => SubToMePlugin::get_button_description(),
			)
		);

		$title = strip_tags( $instance['title'] );
		$caption = strip_tags( $instance['caption'] );
		$description = strip_tags( $instance['description'] );
		?>
		<p><label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:', 'subtome' ); ?></label> <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" /></p>
		<p><label for="<?php echo $this->get_field_id( 'caption' ); ?>"><?php _e( 'Caption:', 'subtome' ); ?></label> <input class="widefat" id="<?php echo $this->get_field_id( 'caption' ); ?>" name="<?php echo $this->get_field_name( 'caption' ); ?>" type="text" value="<?php echo esc_attr( $caption ); ?>" /></p>
		<p><label for="<?php echo $this->get_field_id( 'description' ); ?>"><?php _e( 'Description:', 'subtome' ); ?></label> <input class="widefat" id="<?php echo $this->get_field_id( 'description' ); ?>" name="<?php echo $this->get_field_name( 'description' ); ?>" type="text" value="<?php echo esc_attr( $description ); ?>" /></p>
		<?php
	} // end form

	/**
	 * Loads the Widget's text domain for localization and translation.
	 */
	public function widget_textdomain() {
		load_plugin_textdomain( 'subtome', false, plugin_dir_path( __FILE__ ) . '/lang/' );
	} // end widget_textdomain

} // end class

/**
 * SubToMe Main Plugin
 *
 * @author Julien Genestoux
 * @author Matthias Pfefferle
 */
class SubToMePlugin {

	/**
	 * Initialize the plugin, registering WordPress hooks.
	 */
	public function init() {
		add_shortcode( 'subtome', array( 'SubToMePlugin', 'shortcode' ) );
		add_action( 'admin_menu', array( 'SubToMePlugin', 'add_menu_item' ) );
		add_action( 'admin_init', array( 'SubToMePlugin', 'register_settings' ) );
		add_action( 'the_content', array( 'SubToMePlugin', 'extend_post' ), 99 );
	}

	/**
	 * add the button to the content
	 *
	 * @param string $content the post/page content
	 * @return string the post/page-code with the SubToMe button
	 */
	public static function extend_post($content) {
		$perma_link = get_permalink();

		$button = self::generate_button();

		if ( ( is_single() && get_option( 'subtome_button_visibility_posts', 'show' ) == 'show' ) ||
				( is_page() && get_option( 'subtome_button_visibility_pages', 'hide' ) == 'show' ) ||
				( ! is_singular() && get_option( 'subtome_button_visibility_archives', 'hide' ) == 'show' ) ) {

			return $content . ' ' . $button . '';
		}

		return $content;
	}

	/**
	 * adds a "subtome" shortcode
	 *
	 * @param array $atts
	 * @return string
	 */
	public static function shortcode( $atts ) {
		extract( shortcode_atts( array(
			'type' => 'default',
			'caption' => self::get_button_caption(),
			'description' => '',
		), $atts ) );

		return self::generate_button( $type, $caption, $description );
	}

	/**
	 * generates the HTML button-code
	 *
	 * @return string the HTML code
	 */
	public static function generate_button($type = null, $caption = null, $description = null) {
		$java_script = self::get_javascript();

		// set default type if empty
		if ( ! $type ) {
			$type = get_option( 'subtome_button_type', 'default' );
		}

		$caption = self::get_button_caption( $caption );
		$description = self::get_button_description( $description );

		// build button html
		switch ( $type ) {
			case 'logo':
				$button = '<img src="' . plugins_url( 'subtome/img/subtome-button.svg', __FILE__ ) . '" onclick="' . $java_script . '" alt="' . $caption . '" style="vertical-align: middle;" />';
				break;
			case 'default':
			default:
				$button = '<input type="button" onclick="' . $java_script . '" value="' . $caption . '" />';
				break;
		}

		$html = '<p class="subtome">';

		// check if description is set
		if ( $description ) {
			$html .= '<span class="subtome-description">' . $description . '</span>&nbsp;';
		}

		$html .= $button . '</p> ';

		return apply_filters( 'subtome_button', $html );
	}

	/**
	 * returns the button-description
	 *
	 * @param string $description the description
	 * @return string
	 */
	public static function get_button_description( $description = null ) {
		if ( $description ) {
			return $description;
		} else {
			return get_option( 'subtome_description', __( 'Liked this post? Follow this blog to get more.', 'subtome' ) );
		}
	}

	/**
	 * returns the button-caption
	 *
	 * @param string $caption the caption
	 * @return string
	 */
	public static function get_button_caption( $caption = null ) {
		if ( $caption ) {
			return $caption;
		} else {
			return get_option( 'subtome_caption', __( 'Follow', 'subtome' ) );
		}
	}


	/**
	 * returns the SubToMe JS snippet
	 *
	 * @return string the SubToMe JS snippet
	 */
	public static function get_javascript() {
		// to be able to filter the javascript code
		return apply_filters( 'subtome_javascript', '(function(){var z=document.createElement("script");z.src="https://www.subtome.com/load.js";document.body.appendChild(z);})()' );
	}

	/**
	 *
	 */
	public static function add_menu_item() {
		add_options_page( 'SubToMe', 'SubToMe', 'administrator', 'subtome', array( 'SubToMePlugin', 'settings' ) );
	}

	/**
	 * settings page
	 */
	public static function settings() {
?>
<div class="wrap">
	<h2><?php _e( 'SubToMe Settings', 'subtome' ); ?></h2>

	<p><?php _e( 'SubToMe is a universal follow button for your blog. If you show the button, your readers will be able to follow your blog by picking the tool of their choice.', 'subtome' ); ?></p>

	<h3><?php _e( 'Button style', 'subtome' ); ?></h3>

	<form method="post" action="options.php">
		<?php
			settings_fields( 'subtome_options' );
			do_settings_sections( 'subtome_options' );
		?>
		<p><?php _e( 'The button will blend into the CSS properties of your theme if you pick the default style.', 'subtome' ); ?></p>
		<table class="form-table subtome">
			<tr>
				<th><label><?php _e( 'Button Caption', 'subtome' ); ?></label></th>
				<td><input name="subtome_caption" type="text" value="<?php echo self::get_button_caption(); ?>" /> </td>
			</tr>

			<tr>
				<th><label><?php _e( 'Button Description (displayed before the button)', 'subtome' ); ?></label></th>
				<td><input name="subtome_description" type="text" value="<?php echo self::get_button_description(); ?>" /> </td>
			</tr>

			<tr>
				<th><label><input name="subtome_button_type" type="radio" value="default" <?php checked( get_option( 'subtome_button_type', 'default' ), 'default' ); ?> /> <?php _e( 'Default (full HTML)', 'subtome' ); ?></label></th>
				<td><?php echo self::generate_button( 'default' ); ?></td>
			</tr>
			<tr>
				<th><label><input name="subtome_button_type" type="radio" value="logo" <?php checked( get_option( 'subtome_button_type', 'default' ), 'logo' ); ?> /> <?php _e( 'Logo', 'subtome' ); ?></label></th>
				<td><?php echo self::generate_button( 'logo' ); ?></td>
			</tr>
		</table>

		<h3><?php _e( 'Button visibility', 'subtome' ); ?></h3>
		<p><?php _e( 'The button will be displayed at the end of each post on:', 'subtome' ); ?></p>
		<form method="post" action="options.php">
			<?php
				settings_fields( 'subtome_options' );
				do_settings_sections( 'subtome_options' );
			?>
			<table class="form-table subtome">
				<tr>
					<th><label><?php _e( 'Single posts (recommended)', 'subtome' ); ?></label></th>
					<td><input name="subtome_button_visibility_posts" type="checkbox" value="show" <?php checked( get_option( 'subtome_button_visibility_posts' ), 'show' ); ?> /></td>
				</tr>
				<tr>
					<th><label><?php _e( 'Posts lists (archives, search, ...)', 'subtome' ); ?></label></th>
					<td><input name="subtome_button_visibility_archives" type="checkbox" value="show" <?php checked( get_option( 'subtome_button_visibility_archives' ), 'show' ); ?> /></td>
				</tr>
				<tr>
					<th><label><?php _e( 'Single Pages', 'subtome' ); ?></label></th>
					<td><input name="subtome_button_visibility_pages" type="checkbox" value="show" <?php checked( get_option( 'subtome_button_visibility_pages' ), 'show' ); ?> /></td>
				</tr>
			</table>
			<p><?php _e( 'Don\'t forget to configure the', 'subtome' ); ?> <a href="<?php echo admin_url( 'widgets.php' ); ?>">widget</a>.</p>
		<?php submit_button(); ?>
	</form>

	<h3><?php _e( 'Shortcode', 'subtome' ); ?></h3>

	<p><?php _e( 'You can also use the button anywhere in your posts with the SubToMe shortcode <code>[subtome]</code>', 'subtome' ); ?></p>
	<p><?php _e( 'The shortcode can be customized with the following attributes:', 'subtome' ); ?></p>

	<ul>
		<li><?php _e( '<code>type</code> - the look of the button. <code>default</code> is for the HTML-button and <code>logo</code> for the SubToMe image.', 'subtome' ); ?></li>
		<li><?php _e( '<code>caption</code> - the caption of the button.', 'subtome' ); ?></li>
		<li><?php _e( '<code>description</code> - a small description that will be displayed before the button.', 'subtome' ); ?></li>
	</ul>

	<p><?php _e( 'Full examle: <code>[subtome type="logo" caption="Follow!" description="Liked this post? Follow this blog to get more."]</code>', 'subtome' ); ?></p>
</div>
<?php
	}

	/**
	 * register SubToMe options
	 */
	public static function register_settings() {
		register_setting( 'subtome_options', 'subtome_button_type' );
		register_setting( 'subtome_options', 'subtome_button_visibility_archives' );
		register_setting( 'subtome_options', 'subtome_button_visibility_posts' );
		register_setting( 'subtome_options', 'subtome_button_visibility_pages' );
		register_setting( 'subtome_options', 'subtome_caption' );
		register_setting( 'subtome_options', 'subtome_description' );
	}
}
