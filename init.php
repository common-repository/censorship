<?php
/*
Plugin Name:  Censorship Plugin
Plugin URI:   https://wordpress.org/plugins/wp-censorship
Description:  Censorship Plugin For Page & Post (Title, Content & Comments)
Version:      3.00
Author:       nath4n
Author URI:   https://profiles.wordpress.org/nath4n
License:      GPL2
License URI:  https://www.gnu.org/licenses/gpl-2.0.html
Text Domain:  wporg
Domain Path:  /languages
*/

define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
define( 'WP_DEBUG_DISPLAY', false );

function wp_censorship_options_install() {
	$arrFilters = array();
	$arrFilters['filters'] = array( 
					'fuck',
					'cunt',
					'shit',
					'ass',
					'dick',
					'cock',
					'cum'
				);
	
	$arrFilters['types'] = array(
							'pages',
							'posts',
							'comments' 
						);
						
	$arrFilters['chrReplace'] = '*****';
	
	update_option( 'censorship-settings', maybe_serialize( $arrFilters ) );
}

register_activation_hook(__FILE__, 'wp_censorship_options_install');

add_action( 'admin_enqueue_scripts', function() {
	wp_enqueue_script( 'jquery-3.3.1', PLUGINS_URL( 'jquery/jquery-3.3.1.min.js' , __FILE__  ) );	
	wp_enqueue_style( 'style-tags-input', PLUGINS_URL( 'tags-input/jquery.tagsinput-revisited.min.css' , __FILE__  ) );
	wp_enqueue_script( 'script-tags-input', PLUGINS_URL( 'tags-input/jquery.tagsinput-revisited.min.js' , __FILE__  ) );
}, 10, 2 );

add_action( 'init', function(){	
	$currSettings = array();
	$currSettings = maybe_unserialize( get_option( 'censorship-settings' ) );
	if( in_array('pages', $currSettings['types']) ) {
		add_filter( 'the_title', 'filter_page', 10 );		// Filter & Replace Pages Title
		add_filter( 'the_content', 'filter_page', 10 );		// Filter & Replace Pages Content
	}
	
	if( in_array('posts', $currSettings['types']) ) {
		add_filter( 'the_title', 'filter_post', 10 );		// Filter & Replace Posts Title
		add_filter( 'the_content', 'filter_post', 10 );		// Filter & Replace Posts Content
	}
	
	if( in_array('comments', $currSettings['types']) ) add_filter( 'comment_text', 'filter_comment', 10 ); // Filter & Replace Comments Text	
}, 10, 2 );

add_action( 'admin_menu', function() {
	add_menu_page( 'WP Censorship', 'WP Censorship', 'manage_options', 'wp_censorship_page', 'wp_censorship_page', 'dashicons-dismiss', '25' );
}, 10, 2 );

function filter_page( $content ) {
	global $wpdb, $post;
	$currSettings = $marketPlace = $arrTitle = $arrContent = $edited_post = array();
	
	if ( $post->post_type == "page" ) {
		$currSettings = maybe_unserialize( get_option( 'censorship-settings' ) );
			
		foreach ( $currSettings['filters'] as $filter ) {
			$marketPlace[] = strtolower( $filter );
		}
		
		$arrContent = explode(' ', $content);
		
		// Filter & Replace Page Content
		foreach($arrContent as $key => $word)
		{
			$word = strtolower(str_replace('.', '', strip_tags($word)));
			if(in_array($word, $marketPlace))
			{
				$arrContent[$key] = $currSettings['chrReplace'][0];
			}
		}

		$content = implode(' ', $arrContent);
	}
	return $content;
}

function filter_post( $content ) {
	global $wpdb, $post;
	$currSettings = $marketPlace = $arrTitle = $arrContent = $edited_post = array();
	
	if ( $post->post_type == "post" ) {
		$currSettings = maybe_unserialize( get_option( 'censorship-settings' ) );
			
		foreach ( $currSettings['filters'] as $filter ) {
			$marketPlace[] = strtolower( $filter );
		}
		
		$arrContent = explode(' ', $content);
		
		// Filter & Replace Post Content
		foreach($arrContent as $key => $word)
		{
			$word = strtolower(str_replace('.', '', strip_tags($word)));
			if(in_array($word, $marketPlace))
			{
				$arrContent[$key] = $currSettings['chrReplace'][0];
			}
		}

		$content = implode(' ', $arrContent);
	}
	return $content;
}

function filter_comment( $content ) {
	global $wpdb, $post;
	$currSettings = $marketPlace = $arrTitle = $arrContent = $edited_post = array();
	$currSettings = maybe_unserialize( get_option( 'censorship-settings' ) );
		
	foreach ( $currSettings['filters'] as $filter ) {
		$marketPlace[] = strtolower( $filter );
	}

	$arrContent = explode(' ', $content);

	// Filter & Replace Comments Text
	foreach($arrContent as $key => $word)
	{
		$word = strtolower(str_replace('.', '', strip_tags($word)));
		if(in_array($word, $marketPlace))
		{
			$arrContent[$key] = $currSettings['chrReplace'][0];
		}
	}

	$content = implode(' ', $arrContent);
	return $content;
}

function wp_censorship_page() {
	$editedPost = $tempArray = $arrSettings = $currSettings = array();
	$nonce = $_REQUEST['_wpnonce'];
	// Get entire array
	$currSettings = maybe_unserialize( get_option( 'censorship-settings' ) );

	if ( current_user_can( 'manage_options' ) ) {
		if ( isset( $_POST['updateFilters'] ) && wp_verify_nonce( $nonce, 'wp-filters-nonce' ) ) {			
			$txtfilters = trim( $_POST['filters'] );
			$tempArray = explode( ',', $txtfilters );
			
			foreach( $tempArray as $key => $filter ) {
				$arrSettings['filters'][] = sanitize_text_field( trim( $filter ) );
			}
			
			foreach ( $currSettings['types'] as $key => $type ) {
				$arrSettings['types'][] = sanitize_text_field( trim( $type ) );
			}
			
			$arrSettings['chrReplace'][] = sanitize_text_field( trim( $currSettings['chrReplace'][0] ) );
			// Save Settings			
			update_option( 'censorship-settings', maybe_serialize( $arrSettings ) );
		} elseif ( isset( $_POST['updateReplacement'] ) && wp_verify_nonce( $nonce, 'wp-replacement-nonce' ) ) {
			$tempArray = $_POST['replacement'];
			$txtCustom = trim( $_POST['txtCustom'] );
			if ( $tempArray[0] == 'custom' ) {
				if ( !empty( $txtCustom ) ) {
					$arrSettings['chrReplace'][] = sanitize_text_field( $txtCustom );
				} else {
					$arrSettings['chrReplace'][] = '*****';
				}
			} else {
				$arrSettings['chrReplace'][] = sanitize_text_field( trim( $tempArray[0] ) );
			}
			
			foreach ( $currSettings['filters'] as $key => $filter ) {
				$arrSettings['filters'][] = sanitize_text_field( trim( $filter ) );
			}
			
			foreach ( $currSettings['types'] as $key => $type ) {
				$arrSettings['types'][] = sanitize_text_field( trim( $type ) );
			}
			// Save Settings
			update_option( 'censorship-settings', maybe_serialize( $arrSettings ) );
		} elseif ( isset( $_POST['updateTypes'] ) && wp_verify_nonce( $nonce, 'wp-types-nonce' ) ) {
			$tempArray = $_POST['postType'];
			
			foreach( $tempArray as $key => $type ) {
				$arrSettings['types'][] = sanitize_text_field( trim( $type ) );
			}
			
			foreach ( $currSettings['filters'] as $key => $filter ) {
				$arrSettings['filters'][] = sanitize_text_field( trim( $filter ) );
			}
			
			$arrSettings['chrReplace'][] = sanitize_text_field( trim( $currSettings['chrReplace'][0] ) );
			// Save Settings
			update_option( 'censorship-settings', maybe_serialize( $arrSettings ) );
		}
	}
	?>
	<style>
	.postbox {		
		border-radius: 5px;
		border:1px solid #CCC;
		padding:0 10px;margin-top:5px;		
		-moz-border-radius:5px;
		-webkit-border-radius:5px;		
	}
	</style>
<div class="wrap">
    <h2>WP Censorship Configuration</h2>
	<div id="poststuff">
		<div id="post-body" class="metabox-holder columns-2">
			<!-- main content -->
			<div id="post-body-content">
				<div class="meta-box-sortables ui-sortable">
					<div class="postbox" style="border-radius:5px;background: #ECECEC;">
						<div class="inside">
							<h3><strong>WordPress Content Censorship Plugin</strong></h3>
							<ul class="description">
								<li>A WordPress Plugin to Censor your WordPress Post Comments From the Bad Word or Bad Language</li>
								<li>Replace the Bad Words without changing the WordPress Database</li>
								<li>This Plugin is not use the database</li>
							</ul>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div id="poststuff">			
		<div id="post-body" class="metabox-holder columns-2">
			<!-- main content -->
			<div id="post-body-content">
				<div class="meta-box-sortables ui-sortable">
					<div class="postbox">
						<div class="inside">
							<form id="filters-form" method="POST" action="<?php echo $_SERVER['REQUEST_URI']; ?>">
								<?php wp_nonce_field( 'wp-filters-nonce' ); ?>
								<table class="form-table">
									<tbody>
										<tr>
											<td colspan=2>
												<h3>Add Filters</h3>
											</td>		
										</tr>
										<tr>
											<th>
												<label>Filters input:</label>												
											</th>
											<td>
												<?php
													$currSettings = maybe_unserialize( get_option( 'censorship-settings' ) );
													foreach ( $currSettings['filters'] as $filter ) {
														$dataToInsert .= $filter . ',';
													}
												?>
												<input id="filters" name="filters" type="text" value="<?php echo $dataToInsert; ?>">
											</td>
										</tr>
										<tr>
											<th>&nbsp;</th>
											<td colspan=2>
												<input type='submit' name="updateFilters" id="updateFilters" value='Update Filters' class='button button-primary'>
											</td>		
										</tr>
									</tbody>
								</table>
							</form>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div id="poststuff">			
		<div id="post-body" class="metabox-holder columns-2">
			<!-- main content -->
			<div id="post-body-content">
				<div class="meta-box-sortables ui-sortable">
					<div class="postbox">
						<div class="inside">
							<form id="replacement-form" method="POST" action="<?php echo $_SERVER['REQUEST_URI']; ?>">
								<?php wp_nonce_field( 'wp-replacement-nonce' ); ?>
								<table class="form-table">
									<tbody>
										<tr>
											<td>
												<h3>Replacement</h3>
											</td>
										</tr>
										<tr>
											<th>
												<label>Replace Censored Words With:</label>												
											</th>
											<td>
											<?php 
												$currSettings = maybe_unserialize( get_option( 'censorship-settings' ) );
												if( $currSettings['chrReplace'] == "*****" ) {
													echo '<input type="radio" name="replacement[]" value="*****" checked>';
												} else {
													echo '<input type="radio" name="replacement[]" value="*****">';
												}
												echo '<i>Stars</i> ( "<b>*****</b>" )<br>';
												
												if( $currSettings['chrReplace'] !== "*****" ) {
													echo '<input type="radio" name="replacement[]" value="custom" checked>';
													echo '<i>Custom</i> : <input type="text" name="txtCustom" value="' . $currSettings['chrReplace'] . '" placeholder="%@!#%&amp;">';
												} else {
													echo '<input type="radio" name="replacement[]" value="custom">';
													echo '<i>Custom</i> : <input type="text" name="txtCustom" value="" placeholder="%@!#%&amp;">';
												}												
											?>
											</td>
										</tr>
										<tr>
											<th>&nbsp;</th>
											<td colspan=2>
												<input type='submit' name="updateReplacement" id="updateReplacement" value='Update Replacement' class='button button-primary'>
											</td>		
										</tr>
									</tbody>
								</table>
							</form>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div id="poststuff">			
		<div id="post-body" class="metabox-holder columns-2">
			<!-- main content -->
			<div id="post-body-content">
				<div class="meta-box-sortables ui-sortable">
					<div class="postbox">
						<div class="inside">						
							<form id="posts-form" method="POST" action="<?php echo $_SERVER['REQUEST_URI']; ?>">
								<?php wp_nonce_field( 'wp-types-nonce' ); ?>
								<table class="form-table">
									<tbody>
										<tr>
											<td colspan=2>
												<h3>Select Post Types</h3>
											</td>		
										</tr>
										<tr>
											<th>
												<label>Post Types:</label>					
											</th>
											<td>
												<?php
													$currSettings = maybe_unserialize( get_option( 'censorship-settings' ) );
													if( in_array('pages', $currSettings['types']) ) {
														echo '<input type="checkbox" name="postType[]" value="pages" checked>';
													} else {
														echo '<input type="checkbox" name="postType[]" value="pages">';
													}
													echo '<span><b>Filter All <i>Pages</i></b></span><br>';
													
													if( in_array('posts', $currSettings['types']) ) {
														echo '<input type="checkbox" name="postType[]" value="posts" checked>';
													} else {
														echo '<input type="checkbox" name="postType[]" value="posts">';
													}
													echo '<span><b>Filter All <i>Posts</i></b></span><br>';
													
													if( in_array('comments', $currSettings['types']) ) {
														echo '<input type="checkbox" name="postType[]" value="comments" checked>';
													} else {
														echo '<input type="checkbox" name="postType[]" value="comments">';
													}
													echo '<span><b>Filter All <i>Comments</i></b></span><br>'
												?>
											</td>
										</tr>
										<tr>
											<th>&nbsp;</th>
											<td colspan=2>
												<input type='submit' name="updateTypes" id="updateTypes" value='Update Post Types' class='button button-primary'>
											</td>		
										</tr>
									</tbody>
								</table>
								<script>
								jQuery(function() {
									$('#filters').tagsInput();
								});	
								</script>
							</form>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
	<?php
}