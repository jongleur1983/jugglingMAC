<?php
/**
 * Plugin Name: Media Access Control
 * Plugin URI: -- (not yet available)
 * Description: Access control for media files
 * Version: 0.1
 * Author: Peter Wendorff (Peter.Wendorff@gmx.de)
 * Author URI: http://jugglingsource.de
 * License: all rights reserved (for now)
 * Text Domain: jmac
 */

//TODO: attribute http://loopj.com/jquery-tokeninput/

function jmac_init() {
 $plugin_dir = basename(dirname(__FILE__));
 load_plugin_textdomain( 'jmac', false, $plugin_dir.'/lang' );
}

add_action('plugins_loaded', 'jmac_init');

//-------------------------------------------------------------------
// implementation of the shortcode:

// [acvideo cam="1" ]
//optional parameters: autoplay="1" preload="1" controls="1" loop="1"
function replaceACVideo( $atts ) {
  $a = shortcode_atts( array(
      'cam' => '0',
      'autoplay' => get_option('jmac_autoplay', 'false') == 'true',
      'preload' => get_option('jmac_preload', 'false') == 'true',
      'controls' => get_option('jmac_show_controls', 'true') == 'true',
      'loop' => get_option('jmac_loop', 'true') == 'true'
  ), $atts );
  $req_caps = get_options('jmac_required_options');
  //TODO: for knob: redirect to http://www.comoestaeso.com/membresia-streaming-service/
  //TODO: for knob: when video does not exist, show nothing
  return '<video '.($a['autoplay']?'autoplay ':'')
                  .($a['preload']?'preload autobuffer':'') //we use both variants to be on the safe side
                  .($a['controls']?'controls ':'')
                  .($a['loop']?'loop ':'')
                 .'>'
        .'<source src="' . plugins_url( 'paywall.php', __FILE__ ) . '?cam=' . $a['cam'] . '" type="video/mp4"/>'
        .'</video>';
}

add_shortcode( 'acvideo', 'replaceACVideo' );

//--------------------------------------------------------------------
// implementation of the setup page and the options:

if ( is_admin() ){ // admin actions
  add_action( 'admin_menu', 'add_jmacmenu' );
} else {
  // non-admin enqueues, actions, and filters
}

function jmac_scripts_method() {
	wp_enqueue_script(
		'jquery.tokeninput',
		plugins_url( 'js/jquery.tokeninput.js' , __FILE__ ),
		array( 'jquery' ) //this script depends on jquery
	);
	wp_enqueue_style( 'jmac-style', plugins_url('css/style.css', __FILE__ ));
	wp_enqueue_style( 'jmac-style-tif', plugins_url('css/token-input-facebook.css', __FILE__));
}

add_action( 'admin_enqueue_scripts', 'jmac_scripts_method' );

/*
TODO:
- add adjustable settings for the video file name and paths
- add adjustable settings for the capabilities where access is granted
- add adjustable settings for the failure behaviour, with options to
return a defined fallback video or to return the http failure header.
- add adjustable settings for the behaviour of the shortcode, to allow
redirecting to somewhere else or display an error message when not
authorized.
*/

function register_jmacsettings() { // whitelist options
  add_settings_section('jmac_main', //unique section id
                       __('jmac_main_settings_title', 'jmac'), //title of the section
                       'jmac_get_main_section_description', //function callback returning the describing section header
                       'jugglingmac-options');

  register_setting( 'jugglingmac-options',  //option group
                    'jmac_video_path',           //option name
                    'jmac_check_video_path'); //function that returns the value to save to the database
  add_settings_field('video_path_string', //field id
                     __('jmac_video_path', 'jmac'), //title for the field
                     'jmac_videopath_input', //function callback to display the input box
                     'jugglingmac-options',  //page name
                     'jmac_main'); //section id

  register_setting( 'jugglingmac-options', //set of capabilities that enable the video for the user
                    'jmac_required_capabilities',
                    'jmac_check_required_capabilities');
  add_settings_field('required_capabilities_value',
                     __('jmac_required_capabilities', 'jmac'),
                     'jmac_required_capabilities_input',
                     'jugglingmac-options',
                     'jmac_main');

  register_setting( 'jugglingmac-options', 
                    'jmac_action_on_failure',
                    'jmac_check_action_on_failure');
  add_settings_field('action_on_failure_value',
                     __('jmac_action_on_failure', 'jmac'),
                     'jmac_action_on_failure_input',
                     'jugglingmac-options',
                     'jmac_main');
                    
  register_setting( 'jugglingmac-options', 
                    'jmac_redirect_url_on_404',
                    'jmac_check_redirect_url_on_404');
  add_settings_field('redirect_url_on_404_value',
                     __('jmac_redirect_on_404', 'jmac'),
                     'jmac_action_redirect_on_404_input',
                     'jugglingmac-options',
                     'jmac_main');

  register_setting( 'jugglingmac-options', 
                    'jmac_redirect_url_on_403',
                    'jmac_check_redirect_url_on_403');
  add_settings_field('redirect_url_on_403_value',
                     __('jmac_redirect_on_403', 'jmac'),
                     'jmac_action_redirect_on_403_input',
                     'jugglingmac-options',
                     'jmac_main');
                     
  add_settings_section('jmac_player', //unique section id
                       __('jmac_player_settings_title', 'jmac'), //title of the section
                       'jmac_get_player_section_description', //function callback returning the describing section header
                       'jugglingmac-options');
                       
  register_setting('jugglingmac-options',
                   'jmac_autoplay',
                   'jmac_check_autoplay');
  add_settings_field('autoplay',
                     __('jmac_autoplay', 'jmac'),
                     'jmac_autoplay_input',
                     'jugglingmac-options',
                     'jmac_player');
  
  register_setting('jugglingmac-options',
                   'jmac_preload',
                   'jmac_check_preload');
  add_settings_field('preload',
                     __('jmac_preload', 'jmac'),
                     'jmac_preload_input',
                     'jugglingmac-options',
                     'jmac_player');

  register_setting('jugglingmac-options',
                   'jmac_show_controls',
                   'jmac_check_show_controls');
  add_settings_field('show_controls',
                     __('jmac_show_controls', 'jmac'),
                     'jmac_show_controls_input',
                     'jugglingmac-options',
                     'jmac_player');
                     
  register_setting('jugglingmac-options',
                   'jmac_loop',
                   'jmac_check_loop');
  add_settings_field('loop',
                     __('jmac_loop', 'jmac'),
                     'jmac_loop_input',
                     'jugglingmac-options',
                     'jmac_player');
}

function jmac_get_player_section_description() {
  _e('jmac_player_section_description', 'jmac');
}

function jmac_autoplay_input() {
  ?>    
  <input type="checkbox" name="jmac_autoplay" value="true" <?php echo (get_option('jmac_autoplay', 'false') == 'true'?'checked':''); ?> />
  <?php
}

function jmac_check_autoplay($input) {
  return ($input == 'true'?'true':'false');
}

function jmac_preload_input() {
  ?>    
  <input type="checkbox" name="jmac_preload" value="true" <?php echo (get_option('jmac_preload', 'false') == 'true'?'checked':''); ?> />
  <?php
}

function jmac_check_preload($input) {
  return ($input == 'true'?'true':'false');
}

function jmac_show_controls_input() {
  ?>    
  <input type="checkbox" name="jmac_show_controls" value="true" <?php echo (get_option('jmac_show_controls', 'true') == 'true'?'checked':''); ?> />
  <?php
}

function jmac_check_show_controls($input) {
  return ($input == 'true'?'true':'false');
}

function jmac_loop_input() {
  ?>    
  <input type="checkbox" name="jmac_loop" value="true" <?php echo (get_option('jmac_loop', 'true') == 'true'?'checked':''); ?> />
  <?php
}

function jmac_check_loop($input) {
  return ($input == 'true'?'true':'false');
}

function jmac_action_redirect_on_403_input() {
  $option = get_option('jmac_redirect_url_on_403');
  ?>    
  <input type="text" name="jmac_redirect_url_on_403" value="<?php echo esc_attr( get_option('jmac_redirect_url_on_403') ); ?>" size="40" />
  <?php
}

function is_valid_url($url) {
    $regex = "/^"; //has to start here
    $regex .= "((https?)\:\/\/)?"; // SCHEME
    $regex .= "([a-z0-9+!*(),;?&=\$_.-]+(\:[a-z0-9+!*(),;?&=\$_.-]+)?@)?"; // User and Pass
    $regex .= "([a-z0-9-.]*)\.([a-z]{2,3})"; // Host or IP
    $regex .= "(\:[0-9]{2,5})?"; // Port
    $regex .= "(\/([a-z0-9+\$_-]\.?)+)*\/?"; // Path
    $regex .= "(\?[a-z+&\$_.-][a-z0-9;:@&%=+\/\$_.-]*)?"; // GET Query
    $regex .= "(#[a-z_.-][a-z0-9+\$_.-]*)?"; // Anchor 
    $regex .= "$/"; //has to end here
  if (preg_match($regex, 
                $url)) {
//                http://www.jugglingsource.de/blog/wp-login.php
    return true;
  } else {
    return false;
  }
}

function jmac_check_redirect_url_on_403($input) {
  $oldvalue = get_option('jmac_redirect_url_on_403');
  //validate the $input for this field and return the sanitized value (which is stored in the database afterwards)
  if (is_valid_url($input)) {
    return $input;
  } else {
    add_settings_error('jmac_redirect_url_on_403', 
                       'INVALID_URL',
                       sprintf( __( 'jmac_invalid_url_403', 'jmac' ), $input ););
    return $oldvalue;
  }
}

function jmac_action_redirect_on_404_input() {
  ?>
  <input type="text" name="jmac_redirect_url_on_404" value="<?php echo esc_attr( get_option('jmac_redirect_url_on_404') ); ?>" size="40" />
  <?php
}

function jmac_check_redirect_url_on_404($input) {
  $oldvalue = get_option('jmac_redirect_url_on_404');
  //validate the $input for this field and return the sanitized value (which is stored in the database afterwards)
  if (is_valid_url($input)) {
    return $input;
  } else {
    add_settings_error('jmac_redirect_url_on_404',
                       'INVALID_URL',
                       sprintf( __('jmac_invalid_url_404', 'jmac'), $input);
    return $oldvalue;
  }
}

function jmac_action_on_failure_input() {
  $option = get_option('jmac_action_on_failure');
?>
      <input type="radio" name="jmac_action_on_failure" id="action_on_failure_forbidden" value="access_forbidden" <?php 
        echo (get_option('jmac_action_on_failure') == 'access_forbidden'?'checked="checked" ':''); ?>" />
      <label for="action_on_failure_forbidden"><?php _e('access_forbidden_on_failure', 'jmac'); ?></label><br/>

      <input type="radio" name="jmac_action_on_failure" id="action_on_failure_defaultvideo" value="default_video" <?php 
        echo (get_option('jmac_action_on_failure') == 'default_video'?'checked="checked" ':''); ?>" />
      <label for="action_on_failure_defaultvideo"><?php _e('defaultvideo_on_failure', 'jmac'); ?></label><br/>

      <input type="radio" name="jmac_action_on_failure" id="action_on_failure_redirect" value="redirect" <?php 
        echo (get_option('jmac_action_on_failure') == 'redirect'?'checked="checked" ':'' ); ?>" />
      <label for="action_on_failure_redirect"><?php _e('redirect_on_failure', 'jmac'); ?></label><br/>
<?php
}

function jmac_check_action_on_failure($input) {
  //validate the $input for this field and return the sanitized value (which is stored in the database afterwards)
  $oldvalue = get_option('jmac_action_on_failure');
  //validate the $input for this field and return the sanitized value (which is stored in the database afterwards)
  switch ($input) {
    case 'access_forbidden':
    case 'default_video':
    case 'redirect':
      return $input;
      break;
    default: 
      add_settings_error('jmac_action_on_failure',
                         'INVALID_VALUE',
                         __('jmac_invalid_action_on_failure', 'jmac'));
      return $oldValue;
  }
}

function jmac_required_capabilities_input() {
  $option = get_option('jmac_required_capabilities', 
                       'access_s2member_level2,publish_posts');
  echo '<div id="searchbar" style="display: inline-block; padding: 15px 0px; vertical-align:middle;">'
      .'<input type="text" id="jmac_required_capabilities" name="jmac_required_capabilities" value="'.$option.'"> '
      .'</div>';
}

function jmac_check_required_capabilities($input) {
  $option = get_option('jmac_required_capabilities',
                       'access_s2member_level2,publish_posts');
  //TODO: validate the $input for this field and return the sanitized value (which is stored in the database afterwards)
  //assumptions: capabilities does not contain comma or space.
  //we split the string on comma and trim the results without further checking it.
  $caps = explode(',', $input);
  $caps = array_filter(array_map('trim', $caps));
  if (empty($caps)) {
    add_settings_error('jmac_required_capabilities',
                       'EMPTY_SETTINGS',
                       __('jmac_no_capability_set', 'jmac'));
    return $option;
  }
  
  return implode(',', $caps);
}

function jmac_videopath_input() {
  $option = get_option('jmac_video_path');
  echo "<input id='video_path_string' name='jmac_video_path' size='40' type='text' value='$option' />";
}

function jmac_check_video_path($input) {
  //TODO: validate the $input for this field and return the sanitized value (which is stored in the database afterwards)
  return $input;
}

function jmac_get_main_section_description() {
  echo '<p>'.__('jmac_settings_main_section_description', 'jmac').'</p>';
}

function add_jmacmenu() {
  add_options_page('Juggling Media Access Control Settings', //page title
                   'JMAC-Settings',  //menu title
                   'manage_options', //required capability
                   'jugglingmac-options', //used in the URL of the settings page
                   'jmac_settings_page'); //the function generating the page
  add_action( 'admin_init', 'register_jmacsettings' );
}

function jmac_inline_script() {
  //TODO: extend the jquery.tokeninput to fetch the value of the input-field as initial value!
  $initialValue = get_option('jmac_required_capabilities', 
                             'access_s2member_level2,publish_posts');


  $wp_roles = new WP_Roles();
  $all_caps = array();
  foreach ($wp_roles->roles as $role) {
    $all_caps = array_merge($all_caps, $role["capabilities"]);
  }
  
  if ( wp_script_is( 'jquery.tokeninput', 'done' ) ) {?>
    <script type="text/javascript">
      jQuery(function() { 
          jQuery('#jmac_required_capabilities').tokenInput(
            [ 
            <?php
	            foreach ($all_caps as $cap => $bool) {
	              echo '{id: "'.$cap.'", name: "'.$cap.'"}, ';
	            }
	          ?>
	          ], 
            { theme: "facebook", 
              noResultsText: "Nothin' found.", 
              searchingText: "loading...", 
              preventDuplicates: true,
              minChars: 0,
              prePopulate: [
                <?php
                  foreach (explode(',', $initialValue) as $cap2) {
                    echo '{id: "'.$cap2.'", name: "'.$cap2.'"}, ';
                  }
                ?>
              ]
            });
	  });
    </script><?php
  } else {
    alert("fail");
  }
}

add_action( 'admin_footer', 'jmac_inline_script' );


function jmac_settings_page() {
  wp_enqueue_style( 'jmac-css', plugins_url('css/style.css', __FILE__), null, null, null );
  wp_enqueue_style( 'jmac-css2', plugins_url('css/token-input-facebook.css', __FILE__), null, null, null );
  if (!current_user_can('manage_options')) {
    wp_die(__('jmac_permission_denied', 'jmac'));
  }
  ?>
  <div>
    <h2><?php _e('jmac_settings_header','jmac'); ?></h2>
    <form method="post" action="options.php">
  <?php
//  settings_errors();

  settings_fields( 'jugglingmac-options' );
  do_settings_sections( 'jugglingmac-options' );
  submit_button(); 
  ?>
  </form>
  </div>
<?php }
?>
