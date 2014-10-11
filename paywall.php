<?php
  error_reporting(E_ALL | E_NOTICE);
  ini_set('display_errors', 1);

  /** Load WordPress Bootstrap */
  require_once( dirname( __FILE__ ) . '/../../../wp-load.php' );

  //call this with the target filename as a header:
  // open the file in a binary mode
  $cam = $_GET['cam'];
  if (!is_numeric($cam)) {
    //wrong parameter. send the corresponding header:
    header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found"); 
    //and exit the script.
    die;
  }

  if (!current_user_can('access_s2member_level2') &&
      !current_user_can('publish_posts')) {
    header("HTTP/1.1 401 you don't have the required privileges to access this video.");
    //if redirection is required (e.g. to an explaining default video, uncomment the next line
    //header("Location: error401.php");
    exit;
  }

  $filename = str_replace('{cam}', $cam, get_option('jmac_video_path'));
  header('X-video-file: '.$filename); //TODO: remove debug header!!!
  $fp = fopen($filename, 'rb');

  // send the right headers
  header("Content-Type: video/mp4");
  header("Content-Length: " . filesize($filename));

  // dump the picture and stop the script
  fpassthru($fp);
  exit;

?>
