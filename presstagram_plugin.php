<?php
/*
Plugin Name: Presstagram
Plugin URI: http://www.yelinkyaw.com/presstagram
Description: Presstagram
Author: Ye Lin Kyaw
Version: 0.1.0
Author URI: http://www.yelinkyaw.com
*/

require( __DIR__.'/presstagram.php' );

// Activation Event
register_activation_hook( __FILE__, 'presstagram_init' );

// Activation Hook
function presstagram_init() 
{
	// Create Presstagarm
	$presstagram = new Presstagram();
	
	//Initialize setting
	if(get_option('presstagram_author_id')=='')
	{
		$presstagram->init_presstagram();
	}
	
	// Set Custom Presstagram Cron Interval
	//add_custom_cron_interval();
		
	// Schedule Presstagram
	schedule_presstagram_cron();
}

// Deactivation Event
register_deactivation_hook( __FILE__, 'presstagram_deactivate' );

// Deactivation Hook
function presstagram_deactivate() 
{
	clear_presstagram_cron();
	remove_custom_cron_interval();
}

// Register Hook
add_action('prestagram_schedule', 'check_and_post_presstagram');

// Presstagram Cron Job
function check_and_post_presstagram() 
{
	$presstagram = new Presstagram();
	$presstagram->processPresstagram();
	
	// Only for testing
	// Need to remove Create new post
	// 497003835510675296_13711972
	// 497422608151532759_13711972
	//$media = $presstagram->getMediaById("497003835510675296_13711972");
	//$presstagram->createPresstagramPost($media);
}

// Add Custom Cron Interval
function add_custom_cron_interval()
{
	add_filter('cron_schedules','presstagram_custom_cron_interval');
} 

// Remove Custom Cron Interval
function remove_custom_cron_interval()
{
	remove_filter('cron_schedules','presstagram_custom_cron_interval');
} 


// Enable Presstagram Cron
add_custom_cron_interval();
   
function presstagram_custom_cron_interval($schedules)
{
	//Get Setting
	$hour = get_option('presstagram_hour');
	$minute = get_option('presstagram_minute');
	$publish_type = get_option('presstagram_publish_type');
	
	// Default Daily 24 Hours
	$interval = 24 * 60 * 60;
	
	// Set Frequency for Individual
	if($publish_type == "individual")
	{
		$interval = (($hour * 60) + $minute) * 60 ;
	}
	
	$schedules['presstagram_cron_interval'] = array(      
									'interval'=> $interval,      
									'display'=>  __("Presstagram Interval")  
									);  
	return $schedules;
}


// Schedule Presstagarm Cron
function schedule_presstagram_cron()
{
	// Clear Existing Cron
	clear_presstagram_cron();

	$publish_type = get_option('presstagram_publish_type');
	
	// Start Time 00:00:00
	$start_time = strtotime(date("Y-m-d"));
	
	/*
	// Check now for Individual
	if($publish_type == "individual")
	{
		$start_time = time();
	}
	*/
	
	// Schedule Hook
	wp_schedule_event($start_time, 'presstagram_cron_interval', 'prestagram_schedule');
}

// Clear Presstagram Cron
function clear_presstagram_cron()
{
	wp_clear_scheduled_hook('prestagram_schedule');
}

// Add Shortcode
add_shortcode('presstagram', 'presstagram_shortcode');

// Shortcode Handler
function presstagram_shortcode($atts, $content)
{
	$presstagram = new Presstagram();
	$media_html = "";
	$media_ids = explode (',', $content);
	$media_html = $presstagram->getMediaHTMLByIds($media_ids);
	
	return $media_html;
}

// Add Admin Panel
add_action('admin_menu', 'presstagram_settings');

// Admin Panel
function presstagram_settings()
{
	add_options_page('Presstagram', 'Presstagram', 'administrator', 'presstagram', 'admin_settings');
}

function admin_settings()
{
	
	// Return from Instagram Auth Callback
	if(isset($_GET['code']) && !isset($_POST['image_size']))
	{
		$code = $_GET['code'];
		// Create Presstagarm
		$presstagram = new Presstagram();
		$presstagram->requestToken($code);
	}
	
	// Logout Instragram
	if(isset($_GET['logout']))
	{
		update_option('presstagram_token', '');
		
		//Reset Setting
		//$presstagram = new Presstagram();
		//$presstagram->init_presstagram();
	}
	
	// Save Settings
	if(isset($_POST['image_size']))
	{
		// Settings
		$image_size = $_POST['image_size'];
		update_option('presstagram_image_size', $image_size);
		
		$min_media_id = $_POST['min_media_id'];
		update_option('presstagram_min_media_id', $min_media_id);
		
		$publish_type = $_POST['publish_type'];
		update_option('presstagram_publish_type', $publish_type);
		
		$hour = $_POST['hour'];
		update_option('presstagram_hour', $hour);
		
		$minute = $_POST['minute'];
		update_option('presstagram_minute', $minute);
		
		$video_enable = $_POST['video_enable'];
		update_option('presstagram_video_enable', $video_enable);
			
		$author_id = $_POST['author_id'];
		update_option('presstagram_author_id', $author_id);
		
		$post_type = strtolower($_POST['post_type']);
		update_option('presstagram_post_type', $post_type);
		
		$post_format = $_POST['post_format'];
		update_option('presstagram_post_format', $post_format);
		
		$category_id = $_POST['category_id'];
		update_option('presstagram_category_id', $category_id);
		
		$hashtag = $_POST['hashtag'];
		update_option('presstagram_hashtag', $hashtag);
		
		$title_prefix = $_POST['title_prefix'];
		update_option('presstagram_title_prefix', $title_prefix);
		
		$title_prefix = $_POST['title_default'];
		update_option('presstagram_title_default', $title_prefix);
		
		$hash_disable = $_POST['hash_disable'];
		update_option('presstagram_hash_disable', $hash_disable);
		
		$post_content = $_POST['post_content'];
		update_option('presstagram_post_content', $post_content);
		
		// Re-Schedule
		schedule_presstagram_cron();
	}
	
	// Create Presstagarm
	$presstagram = new Presstagram();
	
	//Show Admin UI
	admin_ui($presstagram);
}

// Admin UI
function admin_ui($presstagram)
{
	$presstagram_token = get_option('presstagram_token');
	$login_caption = 'Re-authenticate with Instagram';
	$user_name = "";
	$auth_url= plugins_url().'/'.plugin_basename(__DIR__).'/auth.php';
	$admin_url = $presstagram->getAdminUrl();
	$media_list = "";
	$author_list = "";
	$category_list = "";
	
	//Load Settings
	$image_size = get_option('presstagram_image_size');
	$min_media_id = get_option('presstagram_min_media_id');
	$publish_type = get_option('presstagram_publish_type');
	$hour = get_option('presstagram_hour');
	$minute = get_option('presstagram_minute');
	$video_enable = get_option('presstagram_video_enable');
	$author_id = get_option('presstagram_author_id');
	$post_type = get_option('presstagram_post_type');
	$post_format = get_option('presstagram_post_format');
	$category_id = get_option('presstagram_category_id');
	$hashtag = get_option('presstagram_hashtag');
	$title_prefix = get_option('presstagram_title_prefix');
	$title_default = get_option('presstagram_title_default');
	$hash_disable = get_option('presstagram_hash_disable');
	$post_content  = get_option('presstagram_post_content');
	
	if($presstagram_token == '')
	{
		$login_caption = 'Login with Instagram';
	}
	else
	{	
		$user_name = $presstagram->getCurrentUserName();
		$attr = Array("min_id"=>$min_media_id);
		//$medium = $presstagram->getMedia($attr);
		$medium = $presstagram->getMedia();
		
		foreach ($medium as $media)
		{
			$idMedia = $media->getMediaId();
			$Caption = $media->getCaption();
			
			$selected = "";
			if($min_media_id == $idMedia)
			{
				$selected = "selected";
			}
			$media_list = $media_list."<option value=\"$idMedia\" $selected>$Caption</option>\n";
		}
	}
	
	//Get Author Lists
	$args = array(
		'blog_id'      => $GLOBALS['blog_id'],
		'role'         => 'Administrator',
		'meta_key'     => '',
		'meta_value'   => '',
		'meta_compare' => '',
		'meta_query'   => array(),
		'include'      => array(),
		'exclude'      => array(),
		'orderby'      => 'login',
		'order'        => 'ASC',
		'offset'       => '',
		'search'       => '',
		'number'       => '',
		'count_total'  => false,
		'fields'       => 'all',
		'who'          => ''
	 );
	$blogusers = get_users($args);
	foreach($blogusers as $bloguser)
	{ 
		$selected = "";
		if($author_id == $bloguser->ID)
		{
			$selected = "selected";
		}
		$author_list = $Author_List."<option value=\"$bloguser->ID\" $selected>$bloguser->display_name</option>\n";
	} 
	
	// Get Categories
	$args=array(
	  'orderby' => 'name',
	  'order' => 'ASC',
	  'hide_empty'=>'0'
	  );
	$categories=get_categories($args);
	foreach($categories as $category)
	{ 
		$selected = "";
		if($category_id == $category->term_id)
		{
			$selected = "selected";
		}
		$category_list = $category_list."<option value=\"$category->term_id\" $selected >$category->name</option>\n";
	} 
	
	// Hour
	
	for($i=0; $i<24; $i++)
	{ 
		$selected = "";
		if($i == $hour)
		{
			$selected = "selected";
		}
		$hour_list = $hour_list."<option value=\"$i\" $selected >$i</option>\n";
	} 
	
	// Minute
	for($i=0; $i<60; $i++)
	{ 
		$selected = "";
		if($i == $minute)
		{
			$selected = "selected";
		}
		$minute_list = $minute_list."<option value=\"$i\" $selected >$i</option>\n";
	} 
	
?>
<div class="wrap">
<div id="icon-options-general" class="icon32"></div>
<h2>Presstagram Settings</h2>
<h3>Authentication</h3>
<a class="button-secondary" href="<?php echo "$auth_url?url=$admin_url"; ?>"><?php echo $login_caption; ?></a>
<?php if($presstagram_token!='') echo "<a class=\"button-secondary\" href=\"$admin_url&logout=true\">Logout - $user_name</a>" ?>
<form method="POST" action="<?php $admin_url; ?>">
	<table>
		<tr>
			<td cols="2">
				<h3>Presstagram</h3>
			</td>
		</tr>
		<tr>
			<td><label for="image_size">Image Size: </label></td>
			<td><input id="image_size" maxlength="45" size="20" name="image_size" value="<?php echo $image_size; ?>" /> pixels</td>
		</tr>
		<tr>
			<td><label for="min_media_id">Start Posting from: </label></td>
			<td>
				<select id="min_media_id" name="min_media_id"  style="width: 300px">
					<?php echo $media_list; ?>
				</select> (Presstagram will <b>automatically start publishing</b> after this Instagram Post.)
			</td>
		</tr>
		<tr>
			<td><label for="publish_type">Publish Type: </label></td>
			<td>
				<select id="publish_type" name="publish_type" style="width: 300px">
					<option value="individual" <?php if($publish_type=="individual") echo "selected"; ?>>Individual Posts</option>
					<option value="daily" <?php if($publish_type=="daily") echo "selected"; ?>>Daily Post</option>
				</select>
			</td>
		</tr>
		<tr>
			<td><label for="hour">Checking Interval : </label></td>
			<td><select id="hour" name="hour" style="width: 60px"><?php echo $hour_list; ?></select>:<select id="minute" name="minute" style="width: 60px"><?php echo $minute_list; ?></select> HH:MM (New posts checking interval for individual posts option. Very frequent interval is not recommened on shared hosting.)</td>
		</tr>
		<tr>
			<td><label for="video_enable">Enable Video: </label></td>
			<td><input type="checkbox" id="video_enable" name="video_enable" value="1" <?php if($video_enable=='1') echo 'checked'; ?>> (Note: Some browsers are not supporting HTML5 video tag and mp4 format.)</td>
		</tr>
		<tr>
			<td cols="2">
				<h3>Posts</h3>
			</td>
		</tr>
		<tr>
			<td><label for="author_id">Author: </label></td>
			<td>
				<select id="author_id" name="author_id" style="width: 300px">
					<?php echo $author_list; ?>
				</select>
			</td>
		</tr>
		<tr>
			<td><label for="post_type">Post Type: </label></td>
			<td>
				<select id="post_type" name="post_type" style="width: 300px">
					<option value="Post" <?php if($post_type=="post") echo "selected"; ?>>Post</option>
					<option value="Page" <?php if($post_type=="page") echo "selected"; ?>>Page</option>
				</select>
			</td>
		</tr>
		<tr>
			<td><label for="post_format">Post Format: </label></td>
			<td>
				<select id="post_format" name="post_format" style="width: 300px">
					<option value="0" <?php if($post_format=="0") echo "selected"; ?>>Standard</option>
					<option value="aside" <?php if($post_format=="aside") echo "selected"; ?>>Aside</option>
					<option value="image" <?php if($post_format=="image") echo "selected"; ?>>Image</option>
					<option value="link" <?php if($post_format=="link") echo "selected"; ?>>Link</option>
					<option value="video" <?php if($post_format=="video") echo "selected"; ?>>Video</option>
				</select>
			</td>
		</tr>
		<tr>
			<td><label for="category_id">Category: </label></td>
			<td>
				<select id="category_id" name="category_id" style="width: 300px">
					<?php echo $category_list; ?>
				</select>
			</td>
		</tr>
		<tr>
			<td><label for="hashtag">Add Hashtags to: </label></td>
			<td>
				<select id="hashtag" name="hashtag" style="width: 300px">
					<option value="0" <?php if($hashtag=="0") echo "selected"; ?>>None</option>
					<!-- <option value="categories" <?php if($hashtag=="categories") echo "selected"; ?>>Categories</option> -->
					<option value="tags" <?php if($hashtag=="tags") echo "selected"; ?>>Tags</option>
				</select>
			</td>
		</tr>
		<tr>
			<td><label for="title_prefix">Title Prefix: </label></td>
			<td><input id="title_prefix" maxlength="100" size="50" name="title_prefix" value="<?php echo $title_prefix; ?>" /></td>
		</tr>
		<tr>
			<td><label for="title_default">Default Title: </label></td>
			<td><input id="title_default" maxlength="100" size="50" name="title_default" value="<?php echo $title_default; ?>" /></td>
		</tr>
		<tr>
			<td><label for="hash_disable">Remove Hash from Title: </label></td>
			<td><input type="checkbox" id="hash_disable" name="hash_disable" value="1" <?php if($hash_disable=='1') echo 'checked'; ?>></td>
		</tr>
		<tr>
			<td><label for="post_content">Post Contents: </label></td>
			<td>
				<input type="radio" name="post_content" id="post_content" value="html" <?php if($post_content=='html') echo 'checked'; ?>> Use Generated HTML (Recommended)<br>
				<input type="radio" name="post_content" id="post_content" value="shortcode" <?php if($post_content=='shortcode') echo 'checked'; ?>> Use Shortcode [presstagram][/presstagram]
			</td>
		</tr>
		<tr>
			<td colspan="2"><br/><input class="button-primary" type="submit" name="Save" value="Save Settings" id="save"/></td>
		</tr>
	</table>
</form>
</div>
<?php
}
?>