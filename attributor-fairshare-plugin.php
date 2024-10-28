<?php
/*
Plugin Name: Attributor FairShare plugin
Plugin URI: http://fairshare.attributor.com
Description: Attributor FairShare plugin
Version: 1.3
Author: Attributor Corp.
Author URI: http://attributor.com
*/

/*  Copyright 2010 Attributor Corp. (email : fairshare@attributor.com)

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.


	This plugin installs the Attributor FairShare content syndication widget. Please register at http://fairshare.attributor.com before you install this plugin.
	    
*/
?>
<?php 

register_activation_hook(__FILE__,'attributor_install');
register_deactivation_hook(__FILE__,'attributor_uninstall');
add_action('admin_menu', 'page_field');
add_action('save_post', 'page_save');
add_action('admin_menu', 'attributor_custom');
add_filter('the_content','protect');

/* attributor PLUGIN INSTALL */
function attributor_install()
{
	try{
		global $wpdb;

		//CREATE A TABLE CONTAINING THE PAGES WHICH WILL BE PROTECTED
		$table=$wpdb->prefix."attributor";
		$structure = "CREATE TABLE IF NOT EXISTS ". $table." (
					  id INT(9) NOT NULL AUTO_INCREMENT,
					  post_id VARCHAR(255) NOT NULL,        
					  UNIQUE KEY id (id)
		);";

		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($structure);
		
		delete_option('fairshare_position');
		add_option('fairshare_position','top-left');

		$insert_structure = "INSERT INTO ".$table."(post_id) SELECT ID FROM wp_posts WHERE post_status='publish' or post_status = 'draft'";
		$wpdb->query($insert_structure);
	}
	catch(Exception $e){
		$myFile = WP_CONTENT_DIR."/plugins/attributor-fairshare-plugin/fairshare-widget.log";
		$fh = fopen($myFile, 'a');
		fwrite($fh, "Error while installing the plugin");
		fwrite($fh, $e->getMessage()."\n");
		fclose($fh);
	}
}

function page_field()
{
	if( function_exists( 'add_meta_box' )) {
		add_meta_box('attributor', __('Attributor FairShare'), 'post_attributor', 'page', 'advanced', 'core');
		add_meta_box('attributor', __('Attributor FairShare'), 'post_attributor', 'post', 'advanced', 'core');
	}
}

/* BACK-END attributor DISPLAY */
function post_attributor($post) {
	global $wpdb;

	$page_id=$post->ID;
	$table=$wpdb->prefix."attributor";
	$structure = "SELECT * FROM ". $table." WHERE post_id=".$post->ID;

	$myrows = $wpdb->get_results($structure);
	$pageName = basename($_SERVER['SCRIPT_FILENAME']);
?>
<input type="checkbox" name="is_checked" id="is_checked" <?php if (count($myrows) > 0 || $pageName == "post-new.php") echo 'checked'; ?>/>
<label for="is_checked">Show the FairShare widget</label>
<?php
}

/* SAVE attributor STATUS */
function page_save($post_id)
{
	global $wpdb;

	$table=$wpdb->prefix."attributor";

	$delete_structure = "DELETE FROM ". $table." WHERE post_id=".$post_id;

	$wpdb->query($delete_structure);

	$status = $wpdb->get_results('SELECT post_status FROM wp_posts WHERE ID='.$post_id);

	//IF WE SET THAT THE POST WILL INCLUDE THE attributor WIDGET
//	if(($status[0]->post_status=='publish'))
//	{
		$myFile = WP_CONTENT_DIR."/plugins/attributor-fairshare-plugin/fairshare-widget.log";
		$fh = fopen($myFile, 'a');
		
		$postAction = $_POST['action'];
		if(isset($_POST["is_checked"]) || $postAction === "post-quickpress-publish" || $postAction === "post-quickpress-save"){		
			$text = "Saved checked box for post ".$post_id."\n";
			fwrite($fh, $text);			
			$insert_structure = "INSERT INTO ".$table."(post_id) VALUES (".$post_id.")";
			$wpdb->query($insert_structure);
		}
		else{
			$text = "Saved unchecked box for post ".$post_id."\n";
			fwrite($fh, $text);
		}
		fclose($fh);
//	}	
}

/* attributor UNINSTALL */
function attributor_uninstall(){
	global $wpdb;

	$table=$wpdb->prefix."attributor";
	$structure = "DROP TABLE ". $table.";";
	$wpdb->query($structure);
	
	delete_option('fairshare_position');
}

//FUNCTION FOR INSERTING attributor WIDGET INTO SELECTED POSTS
function protect($content )
{
	global $wpdb;
	global $post;
	$table = $wpdb->prefix."attributor";
	
	//VERIFY IF THE POST IS SET TO HAVE attributor WIDGET IN IT
	$is_attributor = $wpdb->get_results('SELECT * FROM '.$table.' WHERE post_id='.$post->ID);
	if(count($is_attributor)>0){
        
		$pos = get_option('fairshare_position');
		$result = '';
		switch($pos){
			case 'top-left':
			    $result .= '<div class="attributor-widget"><div style="width: 144px;float:left;">
				<script type="text/javascript" src="http://widgets.attributor.com/fsw-1.0/fsw/j/fssynwidget-merge.min.js?ref=wp"></script> 
			</div></div><div style="clear:both;"></div>'.$content;
			break;
			case 'top-right':
				$result .= '<div class="attributor-widget"><div style="width: 144px;float:right;">
				<script type="text/javascript" src="http://widgets.attributor.com/fsw-1.0/fsw/j/fssynwidget-merge.min.js?ref=wp"></script> 
			</div></div><div style="clear:both;"></div>'.$content;
			break;
			case 'top-center':
				$result .= '<div class="attributor-widget"><div style="width: 144px;margin-left: auto;margin-right: auto;">
				<script type="text/javascript" src="http://widgets.attributor.com/fsw-1.0/fsw/j/fssynwidget-merge.min.js?ref=wp"></script> 
			</div></div><div style="clear:both;"></div>'.$content;
			break;
			case 'bottom-left':
				$result .= $content.'<div class="attributor-widget "><div style="width: 144px;float: left;">
				<script type="text/javascript" src="http://widgets.attributor.com/fsw-1.0/fsw/j/fssynwidget-merge.min.js?ref=wp"></script> 
			</div></div>';
			break;
			case 'bottom-right':
				$result .= $content.'<div class="attributor-widget "><div style="width: 144px;float: right;">
				<script type="text/javascript" src="http://widgets.attributor.com/fsw-1.0/fsw/j/fssynwidget-merge.min.js?ref=wp"></script> 
			</div></div>';
			break;
			case 'bottom-center':
				$result .= $content.'<div class="attributor-widget "><div style="width: 144px;margin-left: auto;margin-right: auto;">
				<script type="text/javascript" src="http://widgets.attributor.com/fsw-1.0/fsw/j/fssynwidget-merge.min.js?ref=wp"></script> 
			</div></div>';
			break;
		}
	}
	else{
		$result = $content;
	}
	return $result;
}

function attributor_custom(){
	add_menu_page('Attributor FairShare Widget', 'FairShare', 7, 'attributor-page.php', 'attributor_custom_fct', WP_CONTENT_URL.'/plugins/attributor-fairshare-plugin/fs-rings_16x16.gif');
}

function attributor_custom_fct(){
	global $wpdb;

	if((isset($_POST['save']))&&($_POST['save']=='Save'))
	{
		$myFile = WP_CONTENT_DIR."/plugins/attributor-fairshare-plugin/fairshare-widget.log";
		$fh = fopen($myFile, 'a');
		$text = "Saved setting into database \n";
		fwrite($fh, $text);
		fclose($fh);
		?>
		<div id="attributor-widget-save-message" style="">FairShare widget setting has been updated.</div>
			
		<?php 
		
			$position = preg_split('/\s/m', $_POST['position']);
	
			update_option('fairshare_position',strtolower($position[0]));		
	}
		?>
	
	<style type="text/css">
	
	#attributor-widget-save-message{
		background-color:#FFFFE0;
		border:1px solid #E6DB55;
		margin:10px 0;
		padding:5px;
		width:600px;
	}
	
	#fs-widget-element {
	  text-decoration: none;
	  color: #000;	 
	  width: 150px;
	  border: 1px solid #000;
	  background-color: #c9c9c9;	 
	  display: block;	 
	  z-index:1;
	  position: relative;
	  top:-20px;
	}
	
	a.element-parent:hover {
		background-color: #FAFFD8;
		border-color: #333;
	}

	*[draggable=true] {
	  -moz-user-select:none;
	  -khtml-user-drag: element;
	  cursor: move;
	}

	*:-khtml-drag {
	  background-color: rgba(238,238,238, 0.5);
	}

	 a.element-parent:hover:after {
	}

	.element-parent {
	  text-align:center;
	  width:160px;
	}

	a.over {
	  border-color: #333;
	  background: #ccc;
	  z-index: 9999;
	}

	div.bin {	  
	  border: 1px dashed #999;
	  background: #eee;	 	  
	  margin: 10px;
	  width: 160px;
	  text-align:center;
	  height:20px;
	  padding: 10px;
	  display: block;
	}
	
	#content {	  
	 text-decoration: none;
	  color: #000;
	  margin: 10px;
	  width: 150px;
	  height:150px;
	  border: 1px solid #000;
	  background-color: #c9c9c9;
	  padding: 10px;
	  display: block;
	  text-align:center;	 	  
	}

	div.bin.over {
	  background-color: #FAFFD8;
	}

	.text-color{
		color:#EEEEEE;
	}
	
	</style>
	<?php 
	
	$element = '<a id="fs-widget-element" class="element-parent" href="#">FairShare widget</a>';
	$pos = get_option('fairshare_position');
	?>
	<div style="width:620px;height:400px; ">
		<div style="width:620px;height:100px;">
			<div class="bin <?php if($pos =='top-left') echo 'text-color' ?>"  style="float:left;">Top-Left
				<?php if($pos=='top-left') echo $element ?>
			</div>
			<div class="bin <?php if($pos =='top-center') echo 'text-color' ?>" style="float:left;">Top-Center
				<?php if($pos=='top-center') echo $element ?>
			</div>
			<div class="bin <?php if($pos =='top-right') echo 'text-color' ?>" style="float:left;">Top-Right
				<?php if($pos=='top-right') echo $element ?>
			</div>
		</div>
		<div style="width:620px;height:200px;">
			<div id="content" style="margin:0 auto;width:144px;">Page Content</div>
		</div>
		<div style="width:660px;height:100px;">
			<div class="bin <?php if($pos =='bottom-left') echo 'text-color' ?>" style="float:left;">Bottom-Left
				<?php if($pos=='bottom-left') echo $element ?>
			</div>
			<div class="bin <?php if($pos =='bottom-center') echo 'text-color' ?>" style="float:left;" >Bottom-Center
				<?php if($pos=='bottom-center') echo $element ?>
			</div>
			<div class="bin <?php if($pos =='bottom-right') echo 'text-color' ?>" style="float:left;" >Bottom-Right
				<?php if($pos=='bottom-right') echo $element ?>
			</div>
		</div>
	</div>
	<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.3.2/jquery.min.js"></script>
	<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jqueryui/1.7.2/jquery-ui.js"></script>
    <script>
  
	  jQuery(document).ready(function(){
	  
		jQuery("#fs-widget-element").draggable({
				revert: 'invalid',
				helper: 'clone',		
				cursor: 'move'
			}
		);
		
		var divs = $('div.bin'), el = null;
		
		for (var i = 0; i < divs.length; i++) {
			var bin = divs[i];  
            
			jQuery(bin).droppable({
					accept: "#fs-widget-element",
					activeClass: 'droppable-active',
					hoverClass: 'over',
					drop: function(event, ui){
						$('#fs-widget-element').appendTo(this);
						 $(this).parent().parent().children().children().removeClass('text-color');		
						 $(this).addClass('text-color ui-state-highlight');						
						 jQuery('#position').val($(this).text());	
						$.post( "../wp-content/plugins/attributor-fairshare-plugin/log.php",
						{log:'Set position to '+$(this).text()+'\n'}) 
					}
			});		
		}
	  });
    </script> 


 <form method="post" action="">
	<input type="text" style="display:none" name="position" id="position" value="<?php echo get_option('fairshare_position') ?>"/>
	<input type="submit" style="margin-top:30px;" value="Save" id="save" name="save"/>
  </form>
<?php }
?>