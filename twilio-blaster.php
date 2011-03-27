<?php
/*
 * Plugin Name: Twilio Blaster
 * Plugin URI: http://www.project515.org
 * Version: 1.0
 * Author: Mitchell Hislop
 * Description: This plugin allows you to blast out messages to your message list, as well as blast a post
 */

class TwilioBlaster
{	
	var $admin_options_name="twilio_text_blast";

	function onAdminMenu()
	{
		if(is_admin())
		{
			add_menu_page('Twilio Text Blaster',
							 'Twilio Text Blaster',
							 'activate_plugins',
							 'twilio_blaster',
							 array($this, 'printAdminPage'));
			
			add_meta_box('twilio_text_blast',
						 'Send Text?',
						 array($this, 'textCallback'),
						 'post' );
		}
	}

	function textCallback()
	{
		$twilio_nonce=wp_create_nonce('send-text-nonce');
		echo '<input type="checkbox" name="send_twilio_text" id="send_text" /><label for="send_text">Check to send link</label> ';
		echo '<input type="hidden" name="nonce-twilio-blaster" value="'.$twilio_nonce.'" />';
	}

	public function saveMeta()
	{
		$post_id=$_POST['post_ID'];
		$nonce=$_POST['nonce-twilio-blaster'];
		$send_text_value=$_POST['send_twilio_text'];		
		if (wp_verify_nonce($nonce, 'send-text-nonce') && $send_text_value == 'on')
		{
			$this->sendPost($post_id);
			//add_post_meta($post_id, 'twilio_text', $send_text_value);
		}
	}

	protected function sendPost($post_id)
	{
		$post = get_post($post_id);
		//die(print_r($post, TRUE));

		$twilio_app_dir = dirname(__FILE__) . '/../../../twilio-app/';
		require_once $twilio_app_dir . 'twilio-php/twilio.php';
		require_once $twilio_app_dir . 'config.php';
		$client = new TwilioRestClient($AccountSid, $AuthToken);
		//connect to DB
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
		//bit.ly api goes here
		$login="project515";
		$appkey="R_315ad1d93fff05076a8f9d7e053c6257";
		$format="xml";
		$url=$post->guid;
		$bitly = 'http://api.bit.ly/v3/shorten?longUrl='.urlencode($url).'&login='.$login.'&apiKey='.$appkey.'&format='.$format;
		//get the url
		//could also use cURL here
		$response = file_get_contents($bitly);
		//parse depending on desired format
		$xml = simplexml_load_string($response);
		$link = $xml->data->url;
		$message = "New Post: {$post->post_title} $link";
		foreach ($txt_list as $number){
			$response = $client->request("/$ApiVersion/Accounts/$AccountSid/SMS/Messages", 
										 "POST", array(
											 "To" => $number,
											 "From" => "612-360-2696",
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

		
}
//end of class, begin of out-of-class-scope section

$oTwilioTextBlaster = new TwilioBlaster();

add_action( 'admin_menu', array($oTwilioTextBlaster, 'onAdminMenu'));
add_action('edit_post' , array($oTwilioTextBlaster, 'saveMeta'));
add_action('save_post', array($oTwilioTextBlaster, 'saveMeta'));
add_action('publish_post' , array($oTwilioTextBlaster, 'saveMeta'));

?>