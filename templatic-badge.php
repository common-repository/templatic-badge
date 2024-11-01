<?php
/*
Plugin Name: Post Badges
Plugin URI: https://wordpress.org/plugins/templatic-badge
Description: This add-on allows you to display a badge on your listings , where you can set label and color for the badge. 
Version: 1.0.1
Author: Templatic
Author URI: https://templatic.com/
*/

/* added .mo file for translation  */
$locale = get_locale();
load_textdomain( 'templatic_badge', plugin_dir_path( __FILE__ ).'languages/'.$locale.'.mo' );

/* added farbtastic script and css while adding badges from backend */
add_action('admin_enqueue_scripts','tmpl_badge_admin_head_script',99);
function tmpl_badge_admin_head_script(){
	wp_enqueue_script('farbtastic');
    wp_enqueue_style('farbtastic');   
}


register_activation_hook(__FILE__, 'tmpl_badge_activate');
/* while activation save variable to show activation message  */
function tmpl_badge_activate() {
    add_option('tmpl_badge_activate_msg', 'y');
}

/* This function display admin notice to activate templatic-badge plugin, if they first activated */
add_action('admin_notices','tmpl_badge_admin_notices',99);

function tmpl_badge_admin_notices(){
	if (get_option('tmpl_badge_activate_msg') == 'y') {
		echo '<div class="updated"><p>'.  __('Templatic - Badges plugin is activated successfully. Badges can be added from Add Post page in backend from "Templatic Badge" section.','templatic_badge') . '</p></div>';
		delete_option('tmpl_badge_activate_msg');
	}
}

/* Class for templatic badges where badges are saved and shown besides title */
class templaticBadges 
{
	/* call default construtor */
	function __construct() 
	{
		
		/* action to show metabox at backend */
		add_action('admin_init',array($this,'tmpl_badge_meta_box'));

		/* save badge in post meta table */
		add_action( 'save_post', array( $this, 'tmpl_badge_save_post' ), 1, 2 );

	}
	
	/* call badge metabox  */
	function tmpl_badge_meta_box(){

		global $post;
		
		/* names or objects */
		$output = 'objects'; 
		$args = array();

		/* names or objects, note names is the default */
		$output = 'names'; 
		
		/* 'and' or 'or' */
		$operator = 'and'; 

		$post_types = get_post_types( $args, $output, $operator ); 

		$exclude_post_type = apply_filters('tmpl_badge_unset_post_type',array('page','attachment','revision','nav_menu_item'));
		
		
		/* loop for post type to show post detail template */
		foreach ( $post_types  as $post_type ) {

			if(in_array($post_type,$exclude_post_type))
				continue;
		   
		   /*show single page template for custom post type*/
			add_meta_box( 'templatic_badge', __( 'Templatic Badge', 'templatic_badge' ), array( $this,'tmpl_badge_meta_box_content'), $post_type, 'side','high',$post );
		}
		
	}


	/* display metabox for each post type */
	function tmpl_badge_meta_box_content(){
		global $post;
		$newbadge_title=get_post_meta($post->ID,'newbadge_title',true);
		$newbadge_color=get_post_meta($post->ID,'newbadge_color',true);
		$newbadge_color=($newbadge_color!='')?$newbadge_color:'#';
		?>
		<input type="hidden" name="tmpl_noncename" id="tmpl_noncename" value="<?php echo wp_create_nonce( plugin_basename( __FILE__ ) ); ?>" />
		<ul class="badge_list">
			<li>
				<label><strong><?php echo _e('Badge Title','templatic_badge');?></strong></label><span><input type="text" name="newbadge_title" value="<?php echo $newbadge_title?>" /></span>
				<p class="description"><?php _e('This title will appear as a badge on your listings, detail pages and widgets.','templatic_badge');?></p>
			</li>
			<li>
				<label><strong><?php echo _e('Color','templatic_badge');?></strong></label>
				<span><input type="text" name="newbadge_color" value="<?php echo $newbadge_color;?>"  id="newbadge_color_picker" /></span>
				<a id="close_newbadge_color_picker" style="display:none"><span class="dashicons dashicons-dismiss"></span></a>
				<div class="farbtastic_color" id="color_newbadge_color_picker"  name="newbadge_color_picker" style="display:none" >
				</div>
				<p class="description"><?php _e('Select color for your new badge.','templatic_badge');?></p>
			</li>
		</ul>
		<script type="text/javascript">
		jQuery(document).ready(function($){
			jQuery("#color_newbadge_color_picker").farbtastic("#newbadge_color_picker");
			jQuery(document).on( 'click focus','#newbadge_color_picker', function(e) {
				jQuery('[name="newbadge_color_picker"]').css('display', 'block');
				jQuery('#close_newbadge_color_picker').css('display', 'block');
				return false;
			});
			jQuery(document).on( 'click focus','#close_newbadge_color_picker', function(e) {
				jQuery('[name="newbadge_color_picker"]').css('display', 'none');
				jQuery('#close_newbadge_color_picker').css('display', 'none');
				return false;
			});
			
		});	
		</script>
		<?php
	}


	/*	 Save Directory NewBadge filed save	 */
	
	function tmpl_badge_save_post($post_id,$post){
		
		/*
		 * Verify this came from the our screen and with proper authorization,
		 * because save_post can be triggered at other times
		 */
		if ( ! wp_verify_nonce( $_POST['tmpl_noncename'], plugin_basename( __FILE__ ) ) )
			return $post->ID;
	
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {		
			return ;	
		}
		
		/* names or objects */
		$output = 'objects'; 
		$args = array();

		/* names or objects, note names is the default */
		$output = 'names'; 
		
		/* 'and' or 'or' */
		$operator = 'and'; 

		$post_types = get_post_types( $args, $output, $operator ); 
		
		if(!empty($post_types) && in_array($_POST['post_type'],$post_types)){		
			update_post_meta($post_id,'newbadge_title',$_POST['newbadge_title']);
			update_post_meta($post_id,'newbadge_color',$_POST['newbadge_color']);
		}
	}

} /* end class */


add_filter('the_title','tmpl_badge_tag',10,2);
/* to show badge beside post title where we have used wordpress the_title   */
function tmpl_badge_tag($title,$post_id){
	global $post;
	if(!is_admin())
	{
		$newbadge_title=get_post_meta($post_id,'newbadge_title',true);
		$newbadge_title=($newbadge_title!="")? $newbadge_title : '';
		$newbadge_color=get_post_meta($post_id,'newbadge_color',true);	
		$tmpl_display_badge = '';
		
		if($newbadge_title!=''){		
			
			$tmpl_display_badge = '<span class="badge-status" style="background:'. $newbadge_color.'">'. $newbadge_title.'</span>&nbsp;';
			
		}
		
		return apply_filters('tmpl_title_badge',$title.$tmpl_display_badge);
	}
	else
		return $title;
}

$templ = new templaticBadges(); // go

/* include css in for badge */
add_action('wp_head','tmpl_badge_wp_footer');
function tmpl_badge_wp_footer(){
	?>
    <style type="text/css">
		.badge-status { display:inline; font-size:11px; color:#fff; padding:3px 5px; margin:5px;  position: relative; top:-7px;
			-webkit-border-radius: 3px; -moz-border-radius: 3px; border-radius: 3px;}
		.widget .badge-status {top:-3px; font-size:10px; }
	</style>
    <?php	
}