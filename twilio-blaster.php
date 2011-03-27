<?php
/*
 * Plugin Name: Twilio Blaster
 * Plugin URI: http://www.project515.org
 * Version: 1.0
 * Author: Mitchell Hislop
 * Description: This plugin allows you to blast out messages to your message list, as well as blast a post
 */

if (!class_exists("TwilioBlaster"))
{	
	class TwilioBlaster
	{	
		var $admin_options_name="twilio_text_blast";
		function TwilioBlaster()
		{

		}

		function init()
		{
			$this->get_admin_options();
		}


		function print_admin_page()
		{
			
			?>
			<div class="updated"><p><strong>Post Sent</strong></p></div>
			<?php // } ?>
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

		
	}
}
//end of class, begin of out-of-class-scope section

if (class_exists("TwilioBlaster"))
{
	$o4sqTdAdd= new TwilioBlaster();
}
//actions and filters
function twilio_meta_box()
		{
			add_meta_box('twilio_text_blast', 'Send Text?', 'twilio_text_callback' , 'post' );
		}

function twilio_text_callback()
{
	$twilio_nonce=wp_create_nonce('send-text-nonce');
	echo '<h3>Send Text?:</h3> <input type="checkbox" name="send_twilio_text"  /> ';
	echo '<input type="hidden" name="nonce-twilio-blaster" value="'.$twilio_nonce.'" />';
}

function twilio_save_meta()
{	
	$post_id=$_POST['post_ID'];
	$nonce=$_POST['nonce-twilio-blaster'];
	$send_text_value=$_POST['send_twilio_text'];
	if (wp_verify_nonce($nonce, 'send-text-nonce'))
	{	
		add_post_meta($post_id, 'twilio_text', $send_text_value);
	}
}

if(!function_exists("twilio_text_blaster_ap"))
{
	function twilio_text_blaster_ap()
	{
		global $oTwilioTextBlaster;
		if(!isset($oTwilioTextBlaster))
		{
			return;
		}
		if (function_exists('add_submenu_page'))
		{
			add_submenu_page('index.php', 'Twilio Text Blaster', 'Twilio Text Blaster', 'activate_plugins', 'twilio_blaster', array(&$oTwilioTextBlaster, 'print_admin_page'));
		}
	}
}


if (isset($oTwilioTextBlaster))
{
	//add an action to print the script out
	add_action('activate_twilio-text-blaster/twilio-blaster.php', array(&$oTwilioTextBlaster, 'init'));

	if(is_admin())
	{
		add_action('admin_menu' , 'twilio_text_blaster_ap');
		add_action('admin_menu' , 'twilio_meta_box');
		add_action('edit_post' , 'twilio_save_meta');
		add_action('save_post', 'twilio_save_meta');
		add_action('publish_post' , 'twilio_save_meta');

	}

	//add a filter to the_content to print the button out
}
?>