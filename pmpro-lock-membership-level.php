<?php
/*
Plugin Name: Paid Memberships Pro - Lock Membership Level
Plugin URI: http://www.paidmembershipspro.com/wp/lock-membership-level/
Description: Lock membership level changes for specific users.
Version: .1
Author: Stranger Studios
Author URI: http://www.strangerstudios.com
*/

/*
	Add a page and assign it under Memberships > Page settings that will redirect locked members to 
	using the shortcode [pmpro_membership_locked]. Add shortcode attribute "message" to customize the message shown.
	
	To lock a member from changing their membership level, edit the user and check the box labeled 
	"Lock Membership Level Changes".
*/

function pmprolml_extra_page_settings($pages) {
   $pages['membership_locked'] = array('title'=>'Membership Locked', 'content'=>'[pmpro_membership_locked]', 'hint'=>'Include the shortcode [pmpro_membership_locked].');
   return $pages;
}
add_action('pmpro_extra_page_settings', 'pmprolml_extra_page_settings');

/*
	Add "Locked Members" Menu Item
*/
function pmprolml_pmpro_add_pages()
{	
	$cap = apply_filters('pmpro_add_member_cap', 'edit_users');
	
	add_submenu_page('pmpro-membershiplevels', __('Locked Members', 'pmpro'), __('Locked Members', 'pmpro'), $cap, 'pmpro-lockedmemberslist', 'pmpro_lockedmemberslist');
}
add_action('admin_menu', 'pmprolml_pmpro_add_pages');
function pmpro_lockedmemberslist()
{
	require_once(dirname(__FILE__) . "/adminpages/lockedmemberslist.php");
}

/*
	Add "Lock Membership" field in the profile
*/
function pmprolml_show_extra_profile_fields($user)
{
	?>
	<h3><?php _e('Lock Membership', 'pmpro');?></h3>
	<table class="form-table">
		<tr>
			<th scope="row"><?php _e('Lock Membership Level', 'pmpro');?></th>			
			<td>
				<label for="pmprolml">
					<input id="pmprolml" name="pmprolml" type="checkbox" value="1"<?php checked( get_user_meta($user->ID, 'pmprolml', true)); ?> />
					<?php _e('Lock membership level changes for this user.', 'pmprolml'); ?>
				</label>
			</td>
		</tr>	
	</table>
	<?php
}
add_action( 'show_user_profile', 'pmprolml_show_extra_profile_fields' );
add_action( 'edit_user_profile', 'pmprolml_show_extra_profile_fields' );
 
function pmprolml_save_extra_profile_fields( $user_id ) 
{
	if ( !current_user_can( 'edit_user', $user_id ) )
		return false;
 
	update_usermeta( $user_id, 'pmprolml', $_POST['pmprolml'] );
}
add_action( 'personal_options_update', 'pmprolml_save_extra_profile_fields' );
add_action( 'edit_user_profile_update', 'pmprolml_save_extra_profile_fields' );

/*
	Redirect away from pages if membership is locked.
*/
function pmprolml_template_redirect()
{
	global $pmpro_pages, $current_user;
	$locked_members = array('2004', '1894');

	if(empty($pmpro_pages))
		return;
	
	//Redirect away from the membership locked page if user isn't locked.
	if(is_page($pmpro_pages['membership_locked']) && !in_array($current_user->ID, $locked_members))
	{
		wp_redirect(pmpro_url('account'));
		exit;
	}

	//Redirect to the membership locked page if user is locked.
	if(
		is_page(array(
			pmpro_getOption('levels_page_id'), 
			pmpro_getOption('cancel_page_id'), 
			pmpro_getOption('checkout_page_id'), 
			pmpro_getOption('confirmation_page_id')
		)) 
		&& !empty($pmpro_pages['membership_locked'])
		&& in_array($current_user->ID, $locked_members)
	)
	{
		wp_redirect(pmpro_url('membership_locked'));		//change url here
		exit;
	}
}
add_action('template_redirect', 'pmprolml_template_redirect');

function pmpro_shortcode_membership_locked($atts, $content=null, $code="")
{
	global $current_user;
	
	// $atts    ::= array of attributes
	// $content ::= text within enclosing form of shortcode element
	// $code    ::= the shortcode found, when == callback name
	// examples: [pmpro_membership_locked message="You cannot do this."]
	
	extract(shortcode_atts(array(
		'message' => 'An administrator has locked changes to your membership account.',
	), $atts));
	
	$r = '<div class="pmpro_message pmpro_error">' . $message . '</div>';
	if($current_user->membership_level->ID)
		$r .= '<p><a href="' . pmpro_url("account") . '">' . __("&larr; Return to Your Account", "pmpro") . '</a></p>';

	return $r;
}
add_shortcode("pmpro_membership_locked", "pmpro_shortcode_membership_locked");
