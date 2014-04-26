<?php
/**
 * Move body CSS to head.
 * Borrowed from NextGen Gallery C_Photocrati_Resource_Manager class.
 *
 * @package WP User Avatar
 * @version 1.9.6
 */

class WP_User_Avatar_Resource_Manager {
  static $instance = NULL;

  var $buffer = '';
  var $styles = '';
  var $other_output = '';
  var $wrote_footer = FALSE;
  var $run_shutdown = FALSE;
  var $valid_request = TRUE;

  /**
   * Start buffering all generated output. We'll then do two things with the buffer
   * 1) Find stylesheets lately enqueued and move them to the header
   * 2) Ensure that wp_print_footer_scripts() is called
   * @since 1.9.5
   */
  function __construct() {
    // Validate the request
    $this->validate_request();
    add_action('init', array(&$this, 'start_buffer'), 1);
  }

  /**
   * Determines if the resource manager should perform it's routines for this request
   * @since 1.9.5
   * @return bool
   */
  function validate_request() {
    $retval = FALSE;
    if(!is_admin()) {
      $retval = TRUE;
    }
    $this->valid_request = $retval;
  }

  // Start the output buffers
  function start_buffer() {
    ob_start(array(&$this, 'output_buffer_handler'));
    ob_start(array(&$this, 'get_buffer'));
    add_action('wp_print_footer_scripts', array(&$this, 'get_resources'), 1);
    add_action('shutdown', array(&$this, 'shutdown'));
  }

  function get_resources() {
    ob_start();
    wp_print_styles();
    print_admin_styles();
    $this->styles = ob_get_clean();
    $this->wrote_footer = TRUE;
  }

  /**
   * Output the buffer after PHP execution has ended (but before shutdown)
   * @since 1.9.5
   * @param string $content
   * @return string
   */
  function output_buffer_handler($content) {
    return $this->output_buffer();
  }

  /**
   * Removes the closing </html> tag from the output buffer. We'll then write our own closing tag
   * in the shutdown function after running wp_print_footer_scripts()
   * @since 1.9.5
   * @param string $content
   * @return mixed
   */
  function get_buffer($content) {
    $this->buffer = $content;
    return '';
  }


  // Moves resources to their appropriate place
  function move_resources() {
    if($this->valid_request) {
      // Move stylesheets to head
      if($this->styles) {
        $this->buffer = str_ireplace('</head>', $this->styles.'</head>', $this->buffer);
      }
    }
  }

  // When PHP has finished, we output the footer scripts and closing tags
  function output_buffer($in_shutdown=FALSE) {
    // If the footer scripts haven't been outputted, then
    // we need to take action - as they're required
    if(!$this->wrote_footer) {
      // We don't want to manipulate the buffer if it doesn't contain HTML
      if(strpos($this->buffer, '</body>') === FALSE) {
        $this->valid_request = FALSE;
      }
      // The output_buffer() function has been called in the PHP shutdown callback
      // This will allow us to print the scripts ourselves and manipulate the buffer
      if($in_shutdown === TRUE) {
        ob_start();
        if(!did_action('wp_footer')) {
          wp_footer();
        } else {
          wp_print_footer_scripts();
        }
        $this->other_output = ob_get_clean();
      }
      // W3TC isn't activated and we're not in the shutdown callback.
      // We'll therefore add a shutdown callback to print the scripts
      else {
        $this->run_shutdown = TRUE;
        return '';
      }
    }
    // Once we have the footer scripts, we can modify the buffer and
    // move the resources around
    if ($this->wrote_footer) $this->move_resources();
    return $this->buffer;
  }

  // PHP shutdown callback. Manipulate and output the buffer
  function shutdown() {
    if($this->run_shutdown) echo $this->output_buffer(TRUE);
  }

  public static function init() {
    $klass = get_class();
    return self::$instance = new $klass;
  }
}
