<?php
/*
Plugin Name: Twilio Blaster
Plugin URI: http://www.mitchellhislop.com
Description: This plugin allows you to blast out messages to your message list, as well as blast a post
Version: 1.1
Author: Mitchell Hislop
Author URI: http://www.mitchellhislop.com
*/
/*  
	Copyright 2011 Blind Tigers

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
//Do a PHP version check, require 5.2 or newer
if(version_compare(phpversion(), '5.2.0', '<'))
{
	//Only purpose of this function is to echo out the PHP version error
	function bcn_phpold()
	{
		printf('<div class="error"><p>' . __('Your PHP version is too old, please upgrade to a newer version. Your version is %s, Twilio Blaster requires %s', 'twilio_blaster') . '</p></div>', phpversion(), '5.2.0');
	}
	//If we are in the admin, let's print a warning then return
	if(is_admin())
	{
		add_action('admin_notices', 'twblast_phpold');
	}
	return;
}
//Include admin base class
if(!class_exists('mtekk_admin'))
{
	require_once(dirname(__FILE__) . '/includes/mtekk_admin_class.php');
}
/**
 * The administrative interface class 
 */
class TwilioBlaster extends mtekk_admin
{
	protected $version = '0.0.1';
	protected $full_name = 'Twilio Blaster Settings';
	protected $short_name = 'Twilio Blaster';
	protected $access_level = 'manage_options';
	protected $identifier = 'twilio_blaster';
	protected $unique_prefix = 'twblast';
	protected $plugin_basename = '';
	protected $opt = array(
		'sAccountSid' => 'AC5fafc309936bdbaed48b7d00149481ef',
		'sAuthToken' => '58194089d691ffeb92ee7cf2add060ad',
		'sApiVersion' => '2010-04-01',
		'sPhoneNumber' => '612-360-2696',
		'sBitlyLogin' => 'project515',
		'sBitlyAppKey' => 'R_315ad1d93fff05076a8f9d7e053c6257'
	);
	/**
	 * __construct()
	 * 
	 * Class default constructor
	 */
	function __construct()
	{
		//We set the plugin basename here, could manually set it, but this is for demonstration purposes
		$this->plugin_basename = plugin_basename(__FILE__);
		add_action('admin_menu', array($this, 'onAdminMenu'));
		add_action('edit_post' , array($this, 'saveMeta'));
		add_action('save_post', array($this, 'saveMeta'));
		add_action('publish_post' , array($this, 'saveMeta'));
		//We're going to make sure we load the parent's constructor
		parent::__construct();
	}
	/**
	 * admin initialisation callback function
	 * 
	 * is bound to wpordpress action 'admin_init' on instantiation
	 * 
	 * @return void
	 */
	function init()
	{
		//We're going to make sure we run the parent's version of this function as well
		parent::init();
		//We can not synchronize our database options untill after the parent init runs (the reset routine must run first if it needs to)
		$this->opt = get_option($this->unique_prefix . '_options');
		//Add javascript enqeueing callback
		add_action('wp_print_scripts', array($this, 'javascript'));
	}
	/**
	 * security
	 * 
	 * Makes sure the current user can manage options to proceed
	 */
	function security()
	{
		//If the user can not manage options we will die on them
		if(!current_user_can($this->access_level))
		{
			wp_die(__('Insufficient privileges to proceed.', $this->identifier));
		}
	}
	/**
	 * Upgrades input options array, sets to $this->opt
	 * 
	 * @param array $opts
	 * @param string $version the version of the passed in options
	 */
	function opts_upgrade($opts, $version)
	{
		//If our version is not the same as in the db, time to update
		if($version !== $this->version)
		{
			//Upgrading from 0.2.x
			if(version_compare($version, '0.3.0', '<'))
			{
				$opts['short_url'] = false;
			}
			//Save the passed in opts to the object's option array
			$this->opt = $opts;
		}
	}

	function onAdminMenu()
	{
		if(is_admin())
		{
			add_menu_page('Twilio Text Blaster',
							 'Twilio Text Blaster',
							 'activate_plugins',
							 'twilio_text_blaster',
							 array($this, 'printAdminPage'));
			
			add_meta_box('twilio_text_blast',
						 'Send Text?',
						 array($this, 'textCallback'),
						 'post' );
		}
	}

	function textCallback()
	{
		$twilio_nonce = wp_create_nonce('send-text-nonce');
		echo '<input type="checkbox" name="send_twilio_text" id="send_text" /><label for="send_text">Check to send link</label> ';
		//echo wp_nonce_field($this->unique_prefix . '_twilio_blaster');
		echo '<input type="hidden" name="nonce-twilio-blaster" value="' . $twilio_nonce . '" />';
	}
	public function saveMeta()
	{
		$post_id = $_POST['post_ID'];
		$nonce = $_POST['nonce-twilio-blaster'];
		$send_text_value = $_POST['send_twilio_text'];		
		if(wp_verify_nonce($nonce, 'send-text-nonce') && $send_text_value == 'on')
		{
			$this->sendPost($post_id);
			//add_post_meta($post_id, 'twilio_text', $send_text_value);
		}
	}
	protected function sendPost($post_id)
	{
		$post = get_post($post_id);
		require_once dirname(__FILE__) . '/include/twilio.php';
		require_once dirname(__FILE__) . '/config.php';
		$client = new TwilioRestClient($this->opt['sAccountSid'], $this->opt['sAuthToken']);
		//connect to DB
		//TODO redo to wp-db
		$dbconnect=mysql_connect('localhost', $mysql_user, $mysql_pass);
		if(!$dbconnect){
			die('Could Not Connect:' .mysql_error());
		}
		mysql_select_db('twilio_app');
		
		//this will get pulled out of the database of people who have signed up for SMS
		$query="select number as number from numbers where active='1'";
		$numbers=mysql_query($query);
		$txt_list=array();
		while ($row=mysql_fetch_assoc($numbers))
		{
			$txt_list[]=$row['number'];
		}
		//End TODO
		//Start bit.ly api
		$bitly = 'http://api.bit.ly/v3/shorten?longUrl=' . urlencode(get_permalink($post->ID)) . '&login=' . $this->opt['sBitlyLogin'] . '&apiKey=' . $this->opt['sBitlyAppKey'] . '&format=xml';
		//get the url
		//could also use cURL here
		$response = file_get_contents($bitly);
		//parse depending on desired format
		$xml = simplexml_load_string($response);
		$link = $xml->data->url;
		$message = "New Post: {$post->post_title} $link";
		foreach($txt_list as $number)
		{
			$response = $client->request("/" . $this->opt['sApiVersion'] . "/Accounts/" . $this->opt['sAccountSid'] . "/SMS/Messages", 
										 "POST", array(
											 "To" => $number,
											 "From" => $this->opt['sPhoneNumber'],
											 "Body" => $message
											 ));
		}		
	}
	function printAdminPage()
	{
		if(isset($_GET['sent'])):
		?>		
		<div class="updated"><p><strong>Post Sent</strong></p></div>
			<?php endif;  ?>
			<div class=wrap>
			<form method="post" action="<?php echo get_bloginfo('wpurl')."/twilio-app/send.php";?>">
			<h2>Send Campaign</h2>
			<p><label for="text_area">Campaign Text</label><input type="text" id="sms_input" name="sms_input" /></p>
			<div class="submit">
			<input type="submit" name="send_campaign" value="Send Campaign" />
			</div>
			</form>
			</div>
			<?php 	
	}
	/**
	 * javascript
	 *
	 * Enqueues JS dependencies (jquery) for the tabs
	 * 
	 * @see admin_init()
	 * @return void
	 */
	function javascript()
	{
		//Enqueue ui-tabs
		wp_enqueue_script('jquery-ui-tabs');
	}
	/**
	 * get help text
	 * 
	 * @return string
	 */
	protected function _get_help_text()
	{
		return sprintf(__('Tips for the settings are located below select options. Please refer to the %sdocumentation%s for more information.', $this->identifier), 
			'<a title="' . __('Go to the Relatively Perfect online documentation', $this->identifier) . '" href="http://urlhere">', '</a>');
	}
	/**
	 * admin_head
	 *
	 * Adds in the JavaScript and CSS for the tabs in the adminsitrative 
	 * interface
	 * 
	 */
	/**
	 * enqueue's the tab style sheet on the settings page
	 */
	function admin_styles()
	{
		wp_enqueue_style('mtekk_admin_tabs');
	}
	/**
	 * enqueue's the tab js and translation js on the settings page
	 */
	function admin_scripts()
	{
		//Enqueue the admin tabs javascript
		wp_enqueue_script('mtekk_admin_tabs');
		//Load the translations for the tabs
		wp_localize_script('mtekk_admin_tabs', 'objectL10n', array(
			'mtad_import' => __('Import', $this->identifier),
			'mtad_export' => __('Export', $this->identifier),
			'mtad_reset' => __('Reset', $this->identifier),
		));
	}
	function admin_head()
	{	
	
	}
	/**
	 * admin_page
	 * 
	 * The administrative page for Relatively Perfect
	 * 
	 */
	function admin_page()
	{
		global $wp_taxonomies;
		$this->security();
		$this->version_check(get_option($this->unique_prefix . '_version'));
		?>
		<div class="wrap"><h2><?php _e('Twillio Blaster Settings', 'twilio_blaster'); ?></h2>		
		<p<?php if($this->_has_contextual_help): ?> class="hide-if-js"<?php endif; ?>><?php 
			print $this->_get_help_text();			 
		?></p>
		<form action="options-general.php?page=twilio_blaster" method="post" id="<?php echo $this->unique_prefix;?>-options">
			<?php settings_fields($this->unique_prefix . '_options');?>
			<div id="hasadmintabs">
			<fieldset id="twillio" class="<?php echo $this->unique_prefix;?>_options">
				<h3><?php _e('Twillio Settings', 'twilio_blaster'); ?></h3>
				<table class="form-table">
					<?php
						$this->input_text(__('Phone Number', 'twilio_blaster'), 'sPhoneNumber', '30', false, __('Phone number to sent text messages from.', 'twilio_blaster'));
						$this->input_text(__('Account ID', 'twilio_blaster'), 'sAccountSid', '30', false, __('Twillio account ID.', 'twilio_blaster'));
						$this->input_text(__('Auth Token', 'twilio_blaster'), 'sAuthToken', '30', false, __('Twillio auth token.', 'twilio_blaster'));
						$this->input_text(__('API Version', 'twilio_blaster'), 'sApiVersion', '30', false, __('Twillio API version.', 'twilio_blaster'));
					?>
				</table>
			</fieldset>
			<fieldset id="bitly" class="<?php echo $this->unique_prefix;?>_options">
				<h3><?php _e('Bit.ly', 'twilio_blaster'); ?></h3>
				<table class="form-table">
					<?php
						$this->input_text(__('Login', 'twilio_blaster'), 'sBitlyLogin', '30', false, __('Bit.ly account/login name.', 'twilio_blaster'));
						$this->input_text(__('API Key', 'twilio_blaster'), 'sBitlyAppKey', '30', false, __('Bit.ly API key.', 'twilio_blaster'));
					?>
				</table>
			</fieldset>
			</div>
			<p class="submit"><input type="submit" class="button-primary" name="<?php echo $this->unique_prefix;?>_admin_options" value="<?php esc_attr_e('Save Changes') ?>" /></p>
		</form>
		<?php $this->import_form(); ?>
		</div>
		<?php
	}
}
//Let's make an instance of our object takes care of everything
$TwilioBlaster = new TwilioBlaster;