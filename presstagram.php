<?php

/*
Class: Presstagram
Author: Ye Lin Kyaw
Date: 14/07/2013
*/

require( __DIR__.'/SplClassLoader.php' );
$loader = new SplClassLoader( 'Instagram', dirname( __FILE__ ));
$loader->register();
		
class Presstagram
{
	private $auth_config;
	private $token;
	private $instagram;
	
	// Constructor
	public function Presstagram()
	{
		// Token
		$this->token = get_option('presstagram_token');
		
		// Instagram
		$this->instagram = new Instagram\Instagram;
		$this->instagram->setAccessToken($this->token);
		
		// Auth Config
		$this->auth_config = array(
									'client_id'         => 'fcb5ed908edc462e95875eae35fd07f6',
									'client_secret'     => '660b7405f7d44055bf0eb0f1c821d331',
									'redirect_uri'      => 'http://presstagram.appspot.com/pressta_auth?url='.$this->getAdminUrl(),
									'scope'             => array('basic')
									);
	}
	
	// Initialize
	public function init_presstagram()
	{
		update_option('presstagram_image_size', '300');
		update_option('presstagram_min_media_id', '');
		update_option('presstagram_publish_type', 'individual');
		update_option('presstagram_hour', '00');
		update_option('presstagram_minute', '15');
		update_option('presstagram_video_enable', '0');
		update_option('presstagram_author_id', get_current_user_id());
		update_option('presstagram_post_type', 'post');
		update_option('presstagram_post_format', '0');
		update_option('presstagram_category_id', get_option('default_category'));
		update_option('presstagram_hashtag', '0');
		update_option('presstagram_title_prefix', 'Instagram - ');
		update_option('presstagram_title_default', 'Post via Instagram');
		update_option('presstagram_hash_disable', '1');
		update_option('presstagram_post_content', 'html');
	}
	
	// Get Admin URL
	public function getAdminUrl()
	{
		return admin_url('options-general.php?page=presstagram');
	}
	
	// Get Auth Config
	public function getAuthConfig()
	{
		return $this->auth_config;
	}
	
	// Request Access Token
	public function requestToken($code)
	{
		$auth = new Instagram\Auth($this->getAuthConfig());
		$token = $auth->getAccessToken($code);
		update_option('presstagram_token', $token);
		return $token;
	}
	
	// Get Current Username
	public function getCurrentUserName()
	{
		$current_user = $this->instagram->getCurrentUser();
		$user_name = $current_user->getUserName();
		return $user_name;
	}
	
	// Get Instagram Media
	public function getMedia($attr='')
	{
		$current_user = $this->instagram->getCurrentUser();
		if($attr!='')
			$media = $current_user->getMedia($attr);
		else
			$media = $current_user->getMedia();
		return $media;
	}
	
	// Get Instagram Media by ID
	public function getMediaById($id)
	{
		$media = $this->instagram->getMedia(trim($id));
		return $media;
	}
	
	// Get Instagram Media by Ids
	public function getMediaHTMLByIds($media_ids)
	{
		$html = "";
		foreach($media_ids as $id)
		{
			$html = $html."\n".$this->getMediaHTMLById($id);
		}
	
		return $html;
	}
	
	// Get Instagram Media by ID
	public function getMediaHTMLById($media_id)
	{
		$media = $this->instagram->getMedia(trim($media_id));
		$html = $this->getMediaHTML($media);
		return $html;
	}
	
	// Process Presstagram
	// Main Presstagram function
	public function processPresstagram()
	{
		$publish_type = get_option('presstagram_publish_type');
		$min_media_id = get_option('presstagram_min_media_id');
		try
		{
			$attr = Array("min_id"=>$min_media_id);
			$medium = $this->getMedia($attr);
		
			// Process individual posts
			if($publish_type == "individual" && sizeof($medium)>0)
			{
				update_option('presstagram_min_media_id', $medium[0]->getMediaId());
				for($i=0; $i<$medium->count()-1; $i++)
				{
					$this->createPresstagramPost($medium[$i]);
				}
			}
		}
		catch (\Instagram\Core\ApiException $e)
		{
			return $e->getMessage();
		}
	}
	
	// Create New Post
	// Data: single id or ids array
	public function createPresstagramPost($data)
	{
		// Inits
		$title = '';
		$contents = '';
		$tags = '';
		$date = '';
		
		// Get Options
		$hastag_option = get_option('presstagram_hashtag');
		$post_content = get_option('presstagram_post_content');
		
		if(is_array($data))
		{
			// Set Default Title
			$title = get_option('presstagram_title_default');
			//foreach($data as $id)
			foreach($data as $media)
			{
				// Get Instagram Media
				//$media = $this->instagram->getMedia(trim($id));
				
				// Get Contents
				if($post_content=='html')
				{
					$contents = $contents.' '.$this->getMediaHTML($media);
				}
			
				// Tags
				if( $hastag_option== 'tags')
				{
					// Prepare Tags
					$hashtags = $media->getTags();
					foreach($hashtags as $tag)
					{
						$tags = $tags.','.$tag->getName();
					}
					$tags = rtrim($tags, ', ');
				}
				else if( $hastag_option== 'categories')
				{
					// Add to category
				}
			}
			
			if($post_content=='shortcode')
			{
				$contents = $contents."\n".$this->getMediaShortcode($data);
			}
			
		}
		else
		{
			// Get Instagram Media
			//$media = $this->instagram->getMedia(trim($data));
			$media = $data;
			
			// Get Title
			$title = $media->getCaption();
			
			// Get Date
			$date = $media->getCreatedTime('Y-m-d H:i:s');
			
			// Set Default Title
			if($title == '')
			{
				$title = get_option('presstagram_title_default');
			}
			
			// Get Contents
			if($post_content=='html')
			{
				$contents = $this->getMediaHTML($media);
			}
			else
			{
				$contents = $this->getMediaShortcode($data);
			}
			
			// Tags
			if( $hastag_option== 'tags')
			{
				// Prepare Tags
				$hashtags = $media->getTags();
				foreach($hashtags as $tag)
				{
					$tags = $tags.','.$tag->getName();
				}
				$tags = rtrim($tags, ', ');
			}
			else if( $hastag_option== 'categories')
			{
				// Add to category
			}
		}
		
		// Set Prefix
		$title = get_option('presstagram_title_prefix').$title;
			
		//Hash Enable
		if(get_option('presstagram_hash_disable')==1)
		{
			$title = $this->removeHash($title);
		}
		
		// Create post object
		$post_data = array(
		  'post_title'    => wp_strip_all_tags($title),
		  'post_content'  => $contents,
		  'post_date'  => $date,
		  'post_status'   => 'publish',
		  'post_author'   => get_option('presstagram_author_id'),
		  'post_type'   => get_option('presstagram_post_type'),
		  'post_category' => array(get_option('presstagram_category_id')),
		  'tags_input' => $tags,
		  'post_content_filtered' => ''
		);
		
		// Disable Post filtering for HTML
		if($post_content=='html')
		{
			remove_filter('content_save_pre', 'wp_filter_post_kses');
			remove_filter('content_filtered_save_pre', 'wp_filter_post_kses');
		}
		
		// Insert the post
		$post = wp_insert_post($post_data);
		
		// Re-Enable Post filtering
		if($post_content=='html')
		{
			add_filter('content_save_pre', 'wp_filter_post_kses');
			add_filter('content_filtered_save_pre', 'wp_filter_post_kses');
		}
		
		// Set Post Format
		$post_format = get_option('presstagram_post_format');
		if($post_format != "0")
		{
			set_post_format( $post , $post_format);
		}
		return $post;
	}
	
	// Remove Hash
	private function removeHash($text)
	{
		return str_replace('#', '', $text);
	}
	
	// Get Media Shortcode
	private function getMediaShortcode($media_id)
	{
		$shortcode = "";
		if(is_array($media_id))
		{
			$ids = "";
			foreach($media_id as $id)
			{
				$ids = $ids.",".$id;
			}
			$ids = trim($ids, ', ');
			$shortcode = '[presstagram]'.$ids.'[/presstagram]';
		}
		else
		{
			$shortcode = '[presstagram]'.$media_id.'[/presstagram]';
		}
		return $shortcode;
	}
	
	// Get Media HTML
	private function getMediaHTML($media)
	{
		try
		{
			$size = get_option('presstagram_image_size');
			$video_enable = get_option('presstagram_video_enable');
			$url = "";
			
			$caption = $media->getCaption();
			$media_html = "";
		
			if($media->type=="image" || $video_enable != "1")
			{
				if(intval($size) > 300)
				{
					$url = $media->getStandardRes()->url;
				}
				else if(intval($size) > 150)
				{
					$url = $media->getLowRes()->url;
				}
				else
				{
					$url = $media->getThumbnail()->url;
				}
				$media_html = "<a href=\"$url\"><img src=\"$url\" alt=\"$caption\" width=\"$size\" height=\"$size\"></a>";
			}
			else if($media->type=="video")
			{
				$thumbnail = "";
				if(intval($size) > 480)
				{
					$url = $media->getStandardResVideo()->url;
					$thumbnail = $media->getStandardRes()->url;
				}
				else
				{
					$url = $media->getLowResVideo()->url;
					$thumbnail = $media->getLowRes()->url;
				}
			
				$media_html = "<video width=\"$size\" height=\"$size\" poster=\"$thumbnail\" controls><source src=\"$url\" type=\"video/mp4\">Please use video supported browser.</video>";
			}
		
			return $media_html;
		}
		catch (\Instagram\Core\ApiException $e)
		{
			return $e->getMessage().": $media_id";
		}
	}
}