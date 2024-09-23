<?php
function whatsform_post_editor_box()
{
    add_meta_box('whatsform_all_post_meta', 'WhatsForm Widget', 'whatsform_meta_setup', array(
        'post',
        'page'
    ), 'normal', 'default');
}
add_action('admin_init', 'whatsform_post_editor_box');

function whatsform_post_meta_save($post_id)
{
    if (!isset($_POST['whatsform_post_meta_noncename']) || !wp_verify_nonce($_POST['whatsform_post_meta_noncename'], __FILE__)) return $post_id;
    if ($_POST['post_type'] == 'page') {
        if (!current_user_can('edit_page', $post_id)) return $post_id;
    } else {
        if (!current_user_can('edit_post', $post_id)) return $post_id;
    }
    $current_data = get_post_meta($post_id, '_whatsform_inpost_snippet', true);
    $new_data = wp_kses($_POST['_whatsform_inpost_snippet'], array('script' => array('async' => array(), 'src' => array(), 'id' => array(), 'data-id' => array(), 'data-message' => array())));

    if (!empty($current_data)) {
        if (empty($new_data)) {
            delete_post_meta($post_id, '_whatsform_inpost_snippet');
        } else {
            update_post_meta($post_id, '_whatsform_inpost_snippet', wp_kses($new_data, array('script' => array('async' => array(), 'src' => array(), 'id' => array(), 'data-id' => array(), 'data-message' => array()))));
        }
    } elseif (!empty($new_data)) {
        add_post_meta($post_id, '_whatsform_inpost_snippet', wp_kses($new_data, array('script' => array('async' => array(), 'src' => array(), 'id' => array(), 'data-id' => array(), 'data-message' => array()))));
    }
    return $post_id;
}
add_action('save_post', 'whatsform_post_meta_save');

function whatsform_meta_setup()
{
    global $post;
    $whatsform_inpost_snippet = get_post_meta($post->ID, '_whatsform_inpost_snippet', true);

?>
    <div class="whatsform_meta_control">
        <p>
            <textarea name="_whatsform_inpost_snippet" rows="5" style="width:98%;font-family:monospace;font-size:small;" <?php disabled(!current_user_can( 'unfiltered_html') ); ?>><?php if (!empty($whatsform_inpost_snippet)) echo wp_kses($whatsform_inpost_snippet, array('script' => array('async' => array(), 'src' => array(), 'id' => array(), 'data-id' => array(), 'data-message' => array()))); ?></textarea>
        </p>
        <?php
        if(!current_user_can( 'unfiltered_html' )) {
        	echo '<p style="color:#ffc107"><b>Note:</b> ' . __('You do not have permission to add or edit scripts. Please contact your administrator.', 'whatsform') . '</p>';
        } else {
			echo '<p>'.__('Copy and paste the code snippet to add bot to this post or page', 'whatsform').'</p>'	;
		}
    ?>
    </div>
<?php
    echo '<input type="hidden" name="whatsform_post_meta_noncename" value="' . wp_create_nonce(__FILE__) . '" />';
}
