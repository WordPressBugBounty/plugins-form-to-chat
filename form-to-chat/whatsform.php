<?php

/**
 * Plugin Name: Form to Chat by WhatsForm
 * Version: 1.1.9
 * Plugin URI: https://whatsform.com/
 * Description: Collect form responses from the customer's WhatsApp number using WhatsForm.
 * Author: Micro.company
 * Author URI: https://micro.company/
 * License: GPLv2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

$whatsform_active_plugin = [];
$base = plugin_basename(__FILE__);
$wp_plugins_dir = defined('WP_PLUGIN_DIR') ? WP_PLUGIN_DIR : $wp_content_dir . '/plugins';
if (is_admin()) {
    if (!function_exists('get_plugin_data')) {
        require_once(ABSPATH . 'wp-admin/includes/plugin.php');
    }
    $whatsform_active_plugin[$base] = get_plugin_data($wp_plugins_dir . '/' . $base);
}

define('WHATSFORM_PLUGIN_DIR', str_replace('\\', '/', dirname(__FILE__)));
define('WHATSFORM_DIR_URL', plugin_dir_url(__FILE__));

function whatsform_settings_link($links)
{
    $settings_link = '<a href="options-general.php?page=whatsform">Settings</a>';
    $support_link = '<a href="https://reach.at/whatsform" target="_blank">Support</a>';

    array_push($links, $settings_link);
    array_push($links, $support_link);

    return $links;
}

$plugin = plugin_basename(__FILE__);
add_filter(
    "plugin_action_links_$plugin",
    'whatsform_settings_link'
);



function whatsform_activation_redirect($plugin)
{
    if ($plugin == plugin_basename(__FILE__)) {
        exit(wp_redirect(admin_url('admin.php?page=whatsform')));
    }
}
add_action(
    'activated_plugin',
    'whatsform_activation_redirect'
);

/**
 * Display instructional notice on top of dashboard pages.
 * Removed when user dismisses the notice.
 */
function whatsform_getting_started_notice()
{

    global $current_user;
    $user_id = $current_user->ID;
    /* Check that the user hasn't already clicked to ignore the message */
    if (!get_user_meta($user_id, 'whatsform_getting_started_notice') && !(isset($_GET['page']) && $_GET['page'] == 'whatsform')) {

        printf(__('<div class="notice" style="display: flex;flex-direction:column;gap:10px;padding:20px;">
        <a href="https://whatsform.com/?utm_source=wordpress" class="logo" ><img src="%2$s" width="150px"   alt="whatsform logo"/></a>
        <div>
            <h4 style="margin: 0;">Getting started with your WhatsForm 🚀</h4>

            <ol>
                <li>If you are not an existing WhatsForm user, <a href="https://whatsform.com/?utm_source=wordpress" target="_blank" rel="noreferrer">click here to register.</a></li>
                <li>Design and publish your WhatsForm.</li>
                <li>Copy the embed code / widget snippet and visit <a href="options-general.php?page=whatsform">plugin settings</a> to add WhatsForm to your website.
            </ol>
        </div>
        <a href="%1$s">Dismiss</a>
    </div>'), '?whatsform_notice_ignore=1', WHATSFORM_DIR_URL . "whatsform-logo.png");
    }
}
add_action('admin_notices', 'whatsform_getting_started_notice');


function whatsform_notice_ignore()
{
    global $current_user;
    $user_id = $current_user->ID;
    /* If user clicks to ignore the notice, add that to their user meta */
    if (isset($_GET['whatsform_notice_ignore']) && '1' == $_GET['whatsform_notice_ignore']) {
        add_user_meta($user_id, 'whatsform_getting_started_notice', 'true', true);
    }
}
add_action('admin_init', 'whatsform_notice_ignore');




/**
 * Convert shortcode into embedded iframe.
 */


function whatsform_embed_shortcode($atts)
{
    $atts = array_change_key_case((array) $atts, CASE_LOWER);
    extract(shortcode_atts(array(
        'id' => '',
        'width' => '100%',
        'height' => "600"
    ), $atts));
    if (true || !($id == '')) {
        // add source = wp
        return '<iframe src="' . esc_url("https://whatsform.com/" . $id . "?source=wordpress") . '"  width="' . esc_attr($width) . '" height="' . esc_attr($height) . '" frameBorder="0" allowfullscreen ></iframe>';
    } else {
        return "⚠ Please enter a valid WhatsForm ID";
    }
}
add_shortcode('whatsform', 'whatsform_embed_shortcode');

/**
 * Register OEmbed config. This automatically loads the iframe from plain url.
 */

wp_oembed_add_provider('https://whatsform.com/*', "https://whatsform.com/oembed");

/**
 * Automatically generate whatsform page.
 */

function whatsform_check_page_existence($page_slug)
{
    $page = get_page_by_path($page_slug, OBJECT, 'page');

    return isset($page);
}
function add_whatsform_page($url, $title, $path)
{
    try {
        if (whatsform_check_page_existence($path)) throw new Exception("Page already exists");
        $whatsform_page = array(
            'post_title'    => $title,
            'post_name' => $path,
            'post_content'  => '[whatsform id="' . substr($url, 22) . '"]',
            'post_status'   => 'publish',
            'post_author'   => 1,
            'post_type'     => 'page',
        );
        wp_insert_post($whatsform_page, true);
        update_option("whatsform_url", $url);
        update_option('whatsform_page_title', $title);
        update_option("whatsform_path", $path);

        return true;
    } catch (Exception $e) {
        return false;
    }
}


/**
 * Create settings menu
 */

// Refer below link to understand how to add multiple settings section to same page
// http://www.mendoweb.be/blog/wordpress-settings-api-multiple-forms-on-same-page/
function whatsform_add_menu()
{

    add_menu_page(
        'Form to Chat by WhatsForm', // page <title>Title</title>
        'Form to Chat', // menu link text
        'manage_options', // capability to access the page
        'whatsform', // page URL slug
        'whatsform_settings_template', // callback function /w content
        'data:image/svg+xml;base64,PHN2ZyB2ZXJzaW9uPSIxLjIiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgdmlld0JveD0iMCAwIDUxMiA1MTIiIHdpZHRoPSI2NCIgaGVpZ2h0PSI2NCI+PGRlZnM+PGltYWdlICB3aWR0aD0iMzcxIiBoZWlnaHQ9IjM3MCIgaWQ9ImltZzEiIGhyZWY9ImRhdGE6aW1hZ2UvcG5nO2Jhc2U2NCxpVkJPUncwS0dnb0FBQUFOU1VoRVVnQUFBWE1BQUFGeUNBTUFBQUF3T01vRkFBQUFBWE5TUjBJQjJja3Nmd0FBQXdCUVRGUkZwNnF0cDZxdHA2cXRwNnF0cDZxdHA2cXRwNnF0cDZxdHA2cXRwNnF0cDZxdHA2cXRwNnF0cDZxdHA2cXRwNnF0cDZxdHA2cXRwNnF0cDZxdHA2cXRwNnF0cDZxdHA2cXRwNnF0cDZxdHA2cXRwNnF0cDZxdHA2cXRwNnF0cDZxdHA2cXRwNnF0cDZxdHA2cXRwNnF0cDZxdHA2cXRwNnF0cDZxdHA2cXRwNnF0cDZxdHA2cXRwNnF0cDZxdHA2cXRwNnF0cDZxdHA2cXRwNnF0cDZxdHA2cXRwNnF0cDZxdHA2cXRwNnF0cDZxdHA2cXRwNnF0cDZxdHA2cXRwNnF0cDZxdHA2cXRwNnF0cDZxdHA2cXRwNnF0cDZxdHA2cXRwNnF0cDZxdHA2cXRwNnF0cDZxdHA2cXRwNnF0cDZxdHA2cXRwNnF0cDZxdHA2cXRwNnF0cDZxdHA2cXRwNnF0cDZxdHA2cXRwNnF0cDZxdHA2cXRwNnF0cDZxdHA2cXRwNnF0cDZxdHA2cXRwNnF0cDZxdHA2cXRwNnF0cDZxdHA2cXRwNnF0cDZxdHA2cXRwNnF0cDZxdHA2cXRwNnF0cDZxdHA2cXRwNnF0cDZxdHA2cXRwNnF0cDZxdHA2cXRwNnF0cDZxdHA2cXRwNnF0cDZxdHA2cXRwNnF0cDZxdHA2cXRwNnF0cDZxdHA2cXRwNnF0cDZxdHA2cXRwNnF0cDZxdHA2cXRwNnF0cDZxdHA2cXRwNnF0cDZxdHA2cXRwNnF0cDZxdHA2cXRwNnF0cDZxdHA2cXRwNnF0cDZxdHA2cXRwNnF0cDZxdHA2cXRwNnF0cDZxdHA2cXRwNnF0cDZxdHA2cXRwNnF0cDZxdHA2cXRwNnF0cDZxdHA2cXRwNnF0cDZxdHA2cXRwNnF0cDZxdHA2cXRwNnF0cDZxdHA2cXRwNnF0cDZxdHA2cXRwNnF0cDZxdHA2cXRwNnF0cDZxdHA2cXRwNnF0cDZxdHA2cXRwNnF0cDZxdHA2cXRwNnF0cDZxdHA2cXRwNnF0cDZxdHA2cXRwNnF0cDZxdHA2cXRwNnF0cDZxdHA2cXRwNnF0cDZxdHA2cXRwNnF0cDZxdHA2cXRwNnF0cDZxdHA2cXRwNnF0cDZxdHA2cXRwNnF0cDZxdHA2cXRwNnF0cDZxdHA2cXRwNnF0cDZxdHA2cXRwNnF0cDZxdHA2cXRwNnF0cDZxdHA2cXRwNnF0cDZxdHA2cXRwNnF0cDZxdHA2cXRwNnF0cDZxdHA2cXRwNnF0cDZxdHA2cXRwNnF0cDZxdHA2cXRwNnF0cDZxdHA2cXRwNnF0cDZxdHA2cXRwNnF0cDZxdHA2cXRwNnF0U1dhMTFRQUFBUUIwVWs1VEFBSUVCZ2NKQ2dzUEhTZ3dPa0ZJVTFWYllseEtRanNmRUFnQkV4c2lMelJBV1dseWVINkhpWTZVbFkrQWVYTmFOVE11SEJRbU5sUndnYVN4dnN2VjErdi82dGpVcFlKeE55Y2VCU0FwZXBPcTBObjE5TkcvcTMxZEtrK3p5ZnI0MXNwMFVTc2hEQm9sUEc2V3VQNzl1cGsrRFJsSmc5NnVoVXhHemVEaHp4VVhiS0hGMzhZeDJvUkhpN3dTamVYamtRNjN1VGxscVBzRFE1TEE2Y05uNUwzc2hzZng3NTFMb3Z4WVhxbm5yR01SVjZQb3BwN21QWC81ZkNObXR2UHlUYUN3OXRLWHUvZkNiWnpjTWpqdzNTM3Q3bVFrdFZMVDIydDdpQllZbTVxZlllSldSYTEzcDNaZmFzR3lQODVncjA2S2I4ekVkU3lNbU1pMFVFUm9rQ0VoZ1NZQUFCa21TVVJCVkhpYzdaMTVYRTVaSDhCdmV4SWFTOUtHRExLVGxDMkViSVdVTEpHRW5ySThUeFFoVVpZaUltVnJTSlNRSlVzWlk5K3laMTltR21VWWc5ZVMzWkFaekN2dFBjKzU5MW5PdWVjKzk1N3Y1K00venozbmZGM25udlgzb3lpMVFFTlRTMXRIVjArL2trRmx3eXBWcXhrWi9WQzlldlVhUmtiVmF0WXlybXhRMjBSUFY2ZU9scVlHN21yeUNGTmRNM01MQTh1NmRldlZyMjdWNE1lR2pSbzN0bTdTcEdtenhvMGJOVy9SMHFwNnEzcXQyOWdZV0xTMTFTWFdWVWJickZJN1N6djc5aDJhZE96VTJhRkwxMjZPc3VuZW82ZEQ1MDVPVFh2MXR1OWoyYmVmbVE3dWlxc2x6bVl1L2V1MkdqRFF0Zk1nTjNlQWFWa01kdk1ZTXJUSnNPR3QrN3ZZYXVGdWhQcmdiT3ZpT2FKQms1RmVYVWNwSUxzODNxTjlSallkTTNhY0x4SFBpTWpQZi95RWlaTUdpWlcyWFJhSlI4RGszbFA4elFOeE40dXo2QVg1VDUwMjFDY1lpdTVTZ24ybXo1anA3eWZDM1R6TzRXenJHOUo4K3F4UXlMNkxtVDJuVTYrcXZtR2tueWxCTDl4eTdyejVxSHdYc3lDaVkrL0ljRDNjamVVQ29xQmF2VG90Uk95N21JVWpGNFcwRlhqdkxncUtXaHd3Rzg0SFV6N0VzNWRZMS9RVHJIYWQ2Q3BMT3k5alUzaVI5cGdoeTBOc2RYRTNuMzAwWSswV3hjVklXQmRlaUNSbVJhT1ZzYzY0SmJDS2pzV3ExYUZyTUFrdlpFMm9xNVcvWU5ZSTRuVi9XcnN1Z2YwK3BTTGloUFVUby9RMWNldGdBZWQraVVNMzRIM0ZTMW16WWZwY1g3NTNNYzV0Rm0zc2h2OFZMMFdjTkNTNUxwK3RhMFpOM3BTQzI3SVVteU8yYk9YckRGWFBLSlV6blVwNXRpM3J1RjBmdHg0RWlIYnNUTU0xTkdSR2tyWnI5eDdjaWlEalhIOW5keTUxNHpMb3Ztc0hueFpqNHZlbXAzSGNlQUVacTF2eFpjQ3VZWlNxRHNZTFNOdFh2dzV1WFJEUUdQSHpNdHdxRldCL3VyM2FueDM0eGRwTFRkN3hJc1ErMXNhNHBhbEV1d1ByRCtLV3FDamlnK3NPSGNZdFRtbjBqaHc5aHR1Z1Vod2Z1VW85dDAvalQ1eE13QzFQYVJKT2psWER4Uy8vRGc2NHhhbUV3d3hMM0FvVnBNN2VVNE54VzFNTmNXYmNicldhSTBVbW44YnREQUpwWjhiakZpazNzYnZQbmxPdkFTS0k4eGRNY011VUQwTnJ0bzVPb0NmcDRoVGNPdVhBWlBmNlVkeGRQMVNjK1ltY2Y5VXRHMlhodGdTWmpLYVhjRXVsUlhkc2dQczIzSktnczZRK2gyZEk1aTE2NFBhREJMZERMcmpWZ3JpY3VobTNIVFNJcjZ6bTVycFhiUDBJM0c0UTRyV0RnMXQzYlZ0Y3hlMEZLY0VOYStOV1hKRnJKNi9qdG9LWUcwN2NXb0J4L21rSmJpVXNzSzRtaHpidXpHNzJ4TzJERmJwWWhlRldYVXpiRGpHNGJiQkVUSElsM0xJTHNXalNIYmNMdGhBbmRieUZXM2NCNHpvbDhXTVJVVDV1NDE4SjBLaTNIcmNGbHRrVWhma1lyL2F2SHJnZHNNNXY5YkhlUlJMdHpzWnRBQVBadjJNOHhCdmJjamJ1OW1NaDVnNjJOZlU5QTRReVJxeEl6QUZNcXkvUkhUYmdianMyTmlUYjRsQWVsck1mZDhzeHNteDVMdnZLdzVlcjd5a3RHS1JOdnN1MjhxQXpncGw4QWtqYkVzU3k4ajh5Y0xjWk8vdFQvVmhWZmsvWUhVc2hHZmRaZk5Qdm5nRUYzUk1XR1JkWms1NjdOZ2wzYXpsQzJqMldQcVJtT1VtNDI4b1pNcGF6c290aDBsem9JNWF5WkRReVE2OWMxRjZkcnNHaEorMU9MR3JsV244K3dOMUtqaEdUaVBvaTc0Z0Z1TnZJT2E3K0ZZOVV1VEVmN2svQXBzdERsTW9OaExZUkp4OE9rZWlVK3o3QzNUcU9jdHNYbGZLMlRkVDhMaHd5ams5R2RPNWx6NTBidU52R1ZTUTNlaUhadzRqZkllUTlDaWIyMzBSeGxySEthTnp0NGpROW91QXI3OThaZDZzNHp2eitzSlgzTzZOOGNnbUJrTjRQcnZMQXg1dUZkQ2hSS1FZUGczc0F3eDUxRkg0K3NPRUhVNGpLTFRmaGJvOWE0RkFYbnZKKzkwakhJaGYvZzNiUFMvZkpEVDdkNEVmSXVVT3dUbzl1NVUrY0N0UUUvd3BIdWY4azNDMVJJem9id0ZCdXRwaC9VUlBRSVc0RzR4empVMkdlTVZlV0RhdFVqME5xU2JZcEZHT1R5Z1BHNklHNDI2QjJORkgxeUV0MXN1ZXNJT0tZWjZwTlI4Yzl4OTBFTldSVEcxV1VCMXFUTVlzU3JGVmxzY3VJbjdHZVVKUDFWSG5sZC9OdzExNU5PYXAwL0M3bmhtU2ZYem5PTFZKMmQvVEZTOXgxVjFzOHFpcW5YUFFIN3Bxck1hbktuZFo5SmNTNy9MRG9ucWlNOHJEYnVPdXQxcHhWNWpqZEFPNWw0MU1uTXFjcHZ0YmxTYlpBVmNPcmo2TEs0NjNWTHNrTng5aldSTkY0UnZVRzRhNnoybk82cG9LditVV3k2NndxNGxURjB1M01GRWFRU3JRc3E2YlFhNzZQdk9ZUTJLWElpMjd2aHJ1NnZDRGhCL21WYTdpUzF4d0tyN1hsZHY2R0xKdkRRZjRYWGNPVm0rbkoxWkRYOHZib0owaHZEb3VFN2ZJcGoxOU5Ybk5vUEpMUCtRdlNtOE5qdjF5VDBmaDdaS1VGSHVKOThoeDJ1UVJ4Q3BvMFpIS0x1YzhtTEhMdHl0eGRMWHpiNGhBSEdOQ2lvenM4QVk2T1hlVEloeG4vTmhOV2NjZE92WHZSYms4ZGJaMjdsNm90WlF5SXNhS2RpUmtITURFeGdwcUJ4bnNpODR2dThoNVNZUkt2SHlOMWk4dUxEeC83TTBOSXFieG9PZjRUc3NGRHVFSDFCbmt5RmFqWkcxS3E4b09kcHBiTGpCZGZld3o5RVBRb3kyRTVnV3hOZ21PZ2lNSERtRUxxNkErQlU5TEJYWFlWOTZZQ2I5SktqMnVMU3FLQ25FaUNvNkNZdnhuTzZXcWNnTk9aaVRkZWxuNjQ3Z2U2S0ExeDVtZ1VLc3pNSkNnS1N0ZzhuUDVGMSs0SXA1d3NlMWxmRHRzL3pvRi93bHZuanEvcE4rayt3cGtQWmE2VmZhVG1vUmY0b2lsL25idTFwaHU2T0IrQ016YnRDWWdLcHBjRGZqNS9uUS9PMTZJcHpuWStuRktjUU1FMXQ0UFBodkhYdWFNUHpaQk1ZeXFjNkUrYkg0TytHblp4d04wUUhqdS9iZy8raXVwZWhGTkd0aEhvREpQNUorQlhsTWZPSFZlRGowYmY2Z0tuaUVHMVFCOE52Um5BS1JlZm5ic0JiMGM3cjRJMEIvVzZESEt1ZGVBNDZFZDhkcDdTQURSYzFEOEZxUWdmUTZEejVvSjA3aGdBaW5meE1RdFNDYWREUU03MTN3S3Z5L0RhZVlLZGJDTmFEV0JkSDVxOUE5UWUvM1JnS0RWZU94ZDNrUDBWalk2RFZVSm1JOUFrNE9FL3dCL3gycm5qT3RrekZwczBhQ1dzOEFlMFp3SjRFWjNmenJ2SlBJMnUxUnRlQ1JsV3NzOTF0SFVDMzZ2bXQzTnhRMW1kaSsxSWlFWEU5WlhaSEN1YWtJSDhkdTRZSUt0ejhReUdXRUxTQVZrTGk1V1gwUHlFNTg3VEtrdVhGUDhLMG9Tb2tPQWEwcC9Sb0hsMDI5czhkKzQ0UnZxOGFQUTh1RVY0REsvNHBsdmNvNzJKTjRtdmUzTkY1RWxIU1BmL0RYSVpDd2U0bEgzVlRhSWUwZjlIK2hkREVsU1pWRVdUVEMrclhjV0NURTlzaGwxSWdtc04zMEN0Z2hYR2VKM29LY2x6R0VMQm5BckU0RmNXdGREa2pFelpLN1VwM3doK0tkdmNkblhZTVhYOHlpbWY1MDZNWUx6aHUybW1uVEVIV0duWDRBcDhGUVVNckxqbVluNFdTVG5uc2h5R0xIbnVJYytiY3lQaWJHY09zUEhzYVVTUjN1ZFh6RE5pU2JMSW9TYWpkWG5sR2pkeDEwZ0F6QzIvaUc1MkJuZUZCRUJxK2RHaXhkKzRLeVFBNWh3dTU3dzF5Y09Lbm02L2xPdk9WK0d1anhBUVh5amJvWnMxeFYwZlFiQ2xiSWQrR05JSmFBSXRFUlpsUitja0VqRWJsRnZQUFVHQ1Y3S0JwRldwOGpvdFNDNGNWbWhVdXI0ZEJPbWtQNEdCMTZWN0JQMjljRmRHSUx3czNTWnVROUtFc0VQMzBsdFcxVWllU25hUWxCeHcwM3FNdXk2Q29aZHVrWE5ic3FqSUZoMkxiN2tjaG5aUWtjREEvT0tONk1xUXJsY1FHRmxvVStSOFBGbklaWXZNa0NMbmU4bXdoVFdxRnlyWC9JQzdJZ0xpVHVFSk9wTm11Q3NpSU00VUJybG82NFM3SWdJaXJ6RGxRbCs2RThvRXVQaU0rKzc4RW1Pb0xBSTBzaTk5ZDc0eUJuZEZCRVNhNFhmblc2RUZtaU13Y203c2QrZTdjZGREVUZnVktOY1lnN3NhZ3FKRndSa1h2Um00cXlFb2x1cWJVaHJrUEJHcmJBblhwRFRKbEloVmh2YlRvclQ4U1M0L05qbnJyMDFwZTY3RFhRMUI4YzlISGFxT0RjcGtmbWt2QTA0OTZ2VGNqZm1jbURqMDBVNVhMckI2aURkQ0lZNWQydWhST3BlUVpaWWJkWHJldTZuWEt2bTU5R21WdkNTRDRTOUxYQ3ZaaG5PQU1OczNhTzZIRmpIN3NvalNYWWtxSGU3KysxWDFTbzV6OUcwWlFiOHpJazVsQ3AvTUZzWk1yNGRLcEJtS0tOR1VEV2dlM2pPeFhEeGtyVGIzYWErSWlwMTBRUkpZcGdxYU83bEZYS21sVHdWV1FmTmY2ZjJJQ2pFRlRQMW0wRVhNRkR2cHlWYkFPbFhRYmc4YitWR3hVVkRqV1JUVDVWZnB1QmJoRTJtaThRckdlU3RmeW5ZdmlnZG5OSkIxZTc5L0hqaVBuV0NjditwTHRSMk80TG1TMVJYdldSZFNBeHpwWHpET2o5aFFGaWlXY29NQktiL0MvZ0FPWGdUai9Ga2JxdDBSQk04OUJZcC9jd1FZVmtBd3ppL1lVUWFKOEI5N3JDRm9yRDIrTStnV2pXQ2NUN2hNOWJlQy85amcrcURZclpYdWdXSnhDOFo1ZTBQcTJsejRqL1g0QW9wL0hwZ0RXbmtSalBQZXhwVE5CZmlQZFFER2hhNERqQXN0Sk9jbzNuT3djMjNpL0Z2Zk11NFovTWVDNC95TGtrR3pYc0U0Ly9ZTlJURnV5Zm9MNUx6Zkg2REZhY0U0L3paV3ZJVmdUcFF5QVBRTk5kNG8rTEhpNzYycDJrL2hQMVk4OUM2Z1BkVWZBSDhqRk9kZkk2bndWZ2llZXhYUXVVU2ZBZVk5Rm96ejNaNlVHWXAxeFczL2t4MXcyQWljOTFnd3pvZmZvbUtyQWpPcHFNQ3kzMld0NWJxNGd1TzNDc2I1M2txVXFCNks3VC94bkpyU1N5NzZpMm4rZVlYaS9GaUlHYVUzSGttQUJmRnpxVEY2ZEs4a3VoOEl4SG5DK0VCS3R3K2NsS0VWRWZ2VTBDblhsUDdMYVZQd0NzWDVNa01ScFhOcEZxS25MOHczTHMwazRwSzRnajQxcWVRa2MwNTJkakJFZXRiaWFoODlxczdIOWFnZVAvajU0aDMxeGdXRnVkUWQrMkVYVXc4bWNUSVQ2WE9BV0ZFVTB2ZmNJMUtYMHVvYmdLNEE3NnZucDEvOGxIN1Vnekg2dWFONDlKbi9CbktBdGMwZUliM3JjOTZ6RHVYczhnaGxFWTZTTmFPMnlSVmhiZHVvZzF4ZzFNRTFTSDNrV1doVDhYZC9SbG9Hb1R4TzVzNlVLYmw2emlvVGJlTUwwcWppcm9hZ1dDVDZOajR6L1IxM05RUkY3Ky96ODdIQXRUNENmRjU5bndOTUlXR0syR053MUhmbmRxZ08vUk9rZVZEM3UvTnI0TnkxQk5oMEtRekhiZEVKZDBVRXhMckNpTG01OTNCWFJFQTRGVzZnNmZUQ1hSRUJrVk1VQWYwbTJoVUdRaGt1RkcyZmhVQlBZMGtBTU9yWG9rWDYxaVQ4T1Z2c3R5dHk3aG1CdXlxQzRmUzRJdWZtSkowRlc5ejJMWEtPSWtjdVFTWURvNHVjbTk1a1NOVk1nRVhMa3B2SzlaQkdjaUNVc09aTnlla0NtOUc0S3lNUVFtMUtuRnVRNUFyc3NLNDB5ZCtlNWJnckl4Qit6aTF4cnBGSThzMnhnWGhNbWZncUwyaVBFaElna1JKVjVvaWVKd25IelFiWi9jczROOStGdXpxQzRHalowQk82aDNCWFJ4QTBqaTNqbkpxSkpFQVVvUnppOHFFbnhvRURDQkZnRVRPdXJITEtQQTkzaFFUQTJmS1hDZldhNDY2UUFNZ3YxNTFUcGxXdjRLNFI3OW44YTRYYk8rM0F0MlVKY01qdVcxNDVsVXNpejZQbTMvQUt6clhlSVNubldPanBpUFgvZEUxZ1hzOFJKNnhmOTV3RHJCc3lDRkdheVR0U0FZSHJ3dCszRU04T1dHdzFZbVdiRjl0LzdQZ2IweTB1eWI5OWJDSTVnT1cxNm1nV241S21TRjNHOUJzQ3U1QWJrNnI3UitzV25LRnhEZ3o2WXAwRmp0dGFnT1RuaWpYQ1JXczA5ME85cEVPcmlwSWhseEhhd2FCc1pPaXdzUUcwZHdBbDZkcFNkY0xERkRTeHVNL0VTcFZrR2dYM05GZDI0cDRLQlZ4THBWdGdFRHVKV0hMS1JDMGtkeUJTL3BJUnU4a0Y2aDMwRENzZHFSSU9ENlg1T3ZIOXZuK1h3ektLaXAwSXNZUXIrYll5aXJoOEh2d0x2anQzMHBkUmxNWWI1Z3ZpY2hOd1RXWnozb0h6T1BEY2Vjb09UVmxsMVlhWHdDVmhyclBNNWxnTUJaNWU0cm56MGJLNmxtK2R5MFZvSld3MEFMU25aUkxvSnp4My9qOVpzYklvS3Y0enJNN2wzRnZwWkJhRlBQUVNabnpGbEtjeXV4YUtxZ1JyNVBMZ0thZzlGdk5BRjRENTdieW5CYUF3M2JXUVNqZ2RBb281RkpzanpIaTVXNlJIem9XWVZxRkxJS1FBRHNiZ3VOQ2cvb3ZYenErUEFBV3pwYUtmd3luQzV6TEl1ZFlCUVRyZkZBNHNUYnNGYy81RGVYajVBdVJjZDVFUSt4WkpEbWhNOFkxckM2R1UwZU16Nkw5UzdsclFRaGVmbmFlMW9TbE9aeWVVTXJxOUF6bXZmQW8wS2VLejgzL3Bsa3pqN2FFc0xtNjdLTDF1V1lnUk1JQW1qNTBmWHdVWW5CY1NCdWZhNHN2eHNoOWZad2J3ZzhGajV5OUJHYklLY1Q1QUgrMVRUbzdseUU0R2Fud2V1REhLWCtlWnpXaGZjNHI2Q09kcTBlZzNza1l1K3MzQXV4YjhkZDZqRDBPQnp2T2dsQ09KdXlUajJjOUN3Yi9nclhPeEsxUFNaZE1RT1B2LzdpYy9WbnkwdHYxdmRGWGpxL051MVlCejBHS2NiOE1wS2pQZHVQd2F1dC9YT1hSL256djVvT3ZCZGI1UjlpcHVXZUtmUXRxTDNyWnhsWC9wdjNCMHJlWDBSeGdrcVF4Zkd0WVlEM1hmUDZXOUhESEdvMmsyTFJYamV1cndOcjdST3RxQlFmMGZOdmRpK011U2RGRzhOZ2ZRMG9TYjl6eENkc2JnOG1nMGdKZFVKR1hUL2VZWHJENVk1MlV6UmhRUWUxMVlsY2dGYmk2Rm1WVEZ2Ymxjb2ZRcmJZSllwdEJ4cUhnWVZ6YW1CK0NzTGhJY0hjL2x5Nldjb203NTRLNHFiM2daS2FkenFoSFM3QUlDNHVCYWVaVlRmZC9qcml4UG1HWEhMTHVZWlBLaXcrQmdNL21WVSsyWVJ0TUVlWENvcTRCemlneGRJSEF1UjZFMFZ5N0lFa1VKaUUxU3kzejBqQ0gzUlZWbDhBSEZsRk1tSkVTWHFteVVaNldsSEsvUTNLb1JEdGNuS0txYzB0bUh1OUpxem12UTJRY2F0dEx0NmhDWTZGRkRjZVdVOW5JU2lsNTV4R2VVMm1xMElTRzZsY2ZIV0JubEZQV0JqQmVWWmZBd0pYY2F6Vnh4VjExdE9hcndPTEdZenlSMnNYSXNISzZzY2tydkxVbi9wd3ppNVVxTUU0dHBkeFozOWRXU0NCbUgyT1RuVlREdStxc2hOeTRvdEo1WWtlaTFKR0swd3B6TVpSWkxSK1RmdUZ1Z2RydzNWRTA1UmEyaU9VdExrRUhLTzVVUEFkcGFrNlF1Q25FL1NGWGxGR1ZBeGk2SzRLWFNtS1dZYW5BdU1BcUR0Q01xalZtSzBSOUdOcVRsUmZMZlhSaktLY3FYQkkyV2x5SDltWFhLeHpUY1RWRVhycjVobGlrZlppU1dybnk0ZjRCMkorb0xXVjZVai90S3IrQks4VHVVZTdyODV6d29DcG5pNU43SDNSajE0T3BNYU1xcGgxMXh0MFl0U0dvUDhZTHJCTksxeU1HNVpuN3dsT2VleE4wY3RlQ1U3S0NWeXZFUUdIS0ZVSXFQRGJOSitYbEhadjdNWkoyQXFUd1hUcUFMZnBQMkZSUS9VU21pU05mQ3lQVUQwY3dpRmFBbHVjN0Z4UEdtRUxZcHloQ1dpcnRGM0djWHZDbi9kNmFTaEl0TUxBSEZIMWFXSjZScllXQVQxRkhpTjJ6VGNUZUo2M2lvZkxLaUlsVkoxMEtQVzFYWXlxbkhwR3VoSmRpZU1jQ1pvcEJSQ3ozQk8yVG5TbEdGcVdSQ1JFZm9LNW9ZMjhveTRCenVabkdaQnpjUnBNZ0xKNXZQTklRZVFaR1ZzQ1ladFlBSi9oTkpJc2hEcEdzQmt2VW5ncjc4VzlkQ3JwOERjV3VGUkRtMWxYUXRJSHErUVJScDlvQTM3cVp4bFZsZm9FK0ZDc21GazFDRWg2eG5paUt2TkNleWNiZU5vOXoyUktXY21rWXU1TXJpeGo0WFpNcHRoK0p1SFNkSldFcWZoVVVsVHBBckxUS1kvVGdjblhKcUVlbGFwT241VklXYi9JeUVrYTVGbW5VUDRhL2Rsb0YwTFZLazdKT2R6UjBheWFScnFjQ0dSYlhSS3MrZGpydUpYT1AwS2pPMHlxblBwR3NwaTlqOTZFL0ljL25rak1MZFRFNnhMQi9kM0xNWTIxTzRXOGtwUE9hR0lWZE91cGF5WkhiNndwU21Ed2I1cEdzcFlXRXYrWElNcVVqMFVkd041UXBpNzdQMTJVaytPRFlMZDF1NVF2WmJTMWFNVTVTMUNsMkxkOXI4ZjY1TDRMVWFJK0tEUy81a3prUUpCNU5KeXRiU2U5bUtHVTh0THozeGdkaHlmSFJ0M0lZbDQ5OUdMVmVWcW1KbXpPME93eTBMUHZGYWJ6cGVoOXgrOW5HL1Bid09hOHFwWmtwMExZTVh2QjdXcXZRREg5UUFuT0pjUFhqUHpuQ2xpRDN6RmEzZjhTelhPOVVxckFEVis3UUJoUXFXNko1NkF0SFd2bXlxS2RZdnBHUTdQZDRxNCthWWRvMmhrTkljczA3bWlxLzZiQnFucUxVS0xPTnVYbmp5U2RRZXdJTnFmOWpvcllZOXpEYXY1SEdzQ2xlZ2E1RmM3M3F4ZHozYUc4Q0crUkZxSjMzUXhDaTJWQmRqYWk5WGo3Q21XNWRQRi9vd0JoUFVlSE54bVZwWjc3YXpQcXNkK1hkTW16Q1BXZzd1anh1WUtHYzJ0Zmk5KzdxeDRBb09tL01TMlpvRWxTV2FLWStsOTRiYnpYYTBVK0NKK2tkZUo3RWhUR1d1VE9vTktTU2xZcGp1VGFHcmx2ZURQT3Z0Q2g5a0NyS2Fuc1NTTitWSk9mdkVGNFZSWmpRL2dWTkJaUzdJeXgrcjNKc1E5clVUdDN1WXpXY2Z3NzVETGpmUnN3Q1Zjcjg2ZlZHVWlmSVJlUGZjM0pYQjJhV3ZwTnZ2RUI2Slk4QjA3M0VaVlJLN1p6OXFPRjVQeFE5NjRGK3BHN2k0RjdJdDdmVk5NeWpobkpWRDY2SlVpSG1KdTl2T3gzWjZNTGFubkd0Ti9KdHJjOVBqUGljL0IySTBUbEZtYzhyWFNPTGVkZCs3ajlyUTlnTzFMazA3MjUwN0EzWnh0L1A1ZGtqdXY4bVBxZjJ4TWpXU3VJL3UrT3l3RHR3TldLMUtxMXhqdUpHeWNVMk02eEZmek1ZcHFzN0ZrcytjcFB2NyswLzdRZWxTS2hDdk56Ni9jd0x1bDEyYzhIeXhvUjd5ZzBMTWhBOHFyTkEzNFpPcitZbVF6WUoxL0ZxbHYwL0NhSHpOZ282dHpLRUdpMU1XRGFPQ09LMlNEQy9ybVg0aXROK1YrRmpQM3RORDhlU0VrVHpJYTJFWnk4YVpGVGtRcFRxS3V6L1BlUmdrWXFNMDdlaTZoL0pDMmU1anhLRkhweG1hc2JqcnhrRHVuUFBOZjdrTE1iNHhFenBoZG9kZVgyVlIrNEovbTY4TVkrZkFpcHhvM3ZKanZUNDZ1UitmM1ovUHhqN2UvbGxPN3lxSGMwbzRQcHpOYW9ja2owUjdSTkx0YVA3bncyRm9MdW1ySzRIbTE1NHVqVU1UM3I3cmlvRkhJczFaM3VGVUUwejZlVDVkM09rOXhKeWw0dENYajVKLzhPd1h6WkZCQ2pmUnI5VDNSZnVKazA0dlVIVU5VdnpnOUlySkg3NFl0TVd4OGFOK09OdjZmdnpsWm1PbmpiOHRVR29BNzMzNi9PcWxWbFUrV29SeFowaW9Ic1NhKzF1T3IvL0VPbjJGandmdDNsVUpLVDBkQW5aT3ZQTXF4TktnclFuN084bTh3WG1QdWYrMXlKRGhYeC9ubkVtZkhyQSt3c0ZqOU9qUldWbFp3UVYvZXZRYzVCQnhmc1dqMU1sdkI4ejlzK29sbTNiOWJMRXZXcWsvR3ByYU9ycTJ0eUpyR1NWKzZQRGZ2UzNwcnE5Zm40cUxpNXRVOEtmVG81M3BXejVaTng5anRUMmt0VUc0cm82Mk0vZS9sdjhIcDFGQ1FtTzNMaTBBQUFBQVNVVk9SSzVDWUlJPSIvPjwvZGVmcz48c3R5bGU+PC9zdHlsZT48dXNlICBocmVmPSIjaW1nMSIgeD0iNzAiIHk9IjcxIiAvPjwvc3ZnPg==', // menu icon
        null // priority
    );
}
add_action('admin_menu', 'whatsform_add_menu');


function whatsform_settings_template()
{
    $whatsform_url_input = sanitize_text_field(get_option('whatsform_url_input'));
    $whatsform_page_title_input =  sanitize_text_field(get_option('whatsform_page_title_input'));
    $whatsform_path_input =  sanitize_text_field(get_option('whatsform_path_input'));


    $current_url = sanitize_text_field(get_option("whatsform_url"));
    $current_title = sanitize_text_field(get_option('whatsform_page_title'));
    $current_path = sanitize_text_field(get_option("whatsform_path"));




    $valid_url_pattern = "/^https:\/\/whatsform.com\/.{3,}$/i";
    $valid_path_pattern = "/^([A-Za-z0-9-_])+$/";

    $is_valid_url = preg_match($valid_url_pattern, $whatsform_url_input) == 1;
    $is_valid_path = preg_match($valid_path_pattern, $whatsform_path_input) == 1;
    $is_valid_title = $whatsform_page_title_input !== '';
    $has_path_changed = ($current_path !== $whatsform_path_input);
    $has_title_changed = ($current_title !== $whatsform_page_title_input);
    $has_url_changed = ($current_url !== $whatsform_url_input);


    // Defaults to true. Is false only when page generation fails.
    $page_generated = true;


    if ($is_valid_url && $is_valid_path && $is_valid_title) {
        try {
            if ($has_path_changed || !whatsform_check_page_existence($current_path)) {
                $page_generated = add_whatsform_page($whatsform_url_input, $whatsform_page_title_input, $whatsform_path_input);
            } elseif ($has_url_changed || $has_title_changed) {
                $page = get_page_by_path($current_path, OBJECT, 'page');
                if ($has_url_changed) $page->post_content = '[whatsform id="' . substr($whatsform_url_input, 22) . '"]';
                if ($has_title_changed) $page->post_title = $whatsform_page_title_input;
                wp_update_post($page, true);
                update_option("whatsform_url", $whatsform_url_input);
                update_option('whatsform_page_title', $whatsform_page_title_input);
            }
        } catch (Exception $e) {
        }
    }

    $errors = array();

    if (!$is_valid_url && false !== get_option('whatsform_url_input')) array_push($errors, "Invalid WhatsForm URL. Please enter a valid URL of the form <code>https://whatsform.com/&lt;form_id&gt;</code>");
    if (!$is_valid_title && false !== get_option('whatsform_page_title_input')) array_push($errors, "Please enter a valid page title");
    if (!$is_valid_path && false !== get_option('whatsform_path_input')) array_push($errors, "Invalid WhatsForm path. Path names can only contain alphabets, digits and the characters <code>-</code> and <code>_</code> ");
    if (!$page_generated) array_push($errors, "Couldn't generate the WhatsForm page. Are you sure the page doesn't already exist ?");



    require_once(WHATSFORM_PLUGIN_DIR . '/settings.php');
}

/*
 * Settings template
 */

function whatsform_plugin_settings_init()
{

    add_settings_section('whatsform-settings-section-generate-page', '', '', 'whatsform-generate-page');

    add_settings_field('whatsform-url-input', 'WhatsForm URL', 'whatsform_url_callback', 'whatsform-generate-page', 'whatsform-settings-section-generate-page');
    register_setting('whatsform-settings-generate-page', 'whatsform_url_input', array('type' => 'string', 'sanitize_callback' => 'sanitize_text_field'));

    add_settings_field('whatsform-page-title-input', 'Page title', 'whatsform_page_title_callback', 'whatsform-generate-page', 'whatsform-settings-section-generate-page');
    register_setting('whatsform-settings-generate-page', 'whatsform_page_title_input', array('type' => 'string', 'sanitize_callback' => 'sanitize_text_field'));

    add_settings_field('whatsform-path-input', 'Page path', 'whatsform_path_callback', 'whatsform-generate-page', 'whatsform-settings-section-generate-page');
    register_setting('whatsform-settings-generate-page', 'whatsform_path_input', array('type' => 'string', 'sanitize_callback' => 'sanitize_text_field'));



    add_settings_section('whatsform-settings-section-embed-widget', '', '', 'whatsform-embed-widget');

    add_settings_field('whatsform-widget-snippet', 'WhatsForm Snippet', 'whatsform_widget_snippet_callback', 'whatsform-embed-widget', 'whatsform-settings-section-embed-widget');
    register_setting('whatsform-settings-embed-widget', 'whatsform_widget_snippet');

    add_settings_field('whatsform-widget-show-on', 'Show on', 'whatsform_widget_show_on_callback', 'whatsform-embed-widget', 'whatsform-settings-section-embed-widget');
    register_setting('whatsform-settings-embed-widget', 'whatsform_widget_show_on');
}
add_action('admin_init', 'whatsform_plugin_settings_init');



function whatsform_url_callback()
{

?>
    <input name="whatsform_url_input" class="regular-text" type="text" style="margin:0" placeholder="https://whatsform.com/<form_id>" value="<?php echo (get_option('whatsform_url_input') == false ? '' : esc_attr(get_option('whatsform_url_input'))); ?>" />
<?php
}

function whatsform_path_callback()
{


?> <div style="display:flex;flex-wrap:wrap;"> <code style="display:flex;flex-direction:column;justify-content:center;border:1px solid gray;border-right:0;margin:0;">
            <div><?php echo get_site_url(); ?>/</div>
        </code>
        <input name="whatsform_path_input" class="regular-text" id="whatsform_path_input" type="text" style="max-width:147px;margin:0;" placeholder="whatsform" value="<?php echo esc_attr(get_option('whatsform_path_input') === false ? 'whatsform' : get_option('whatsform_path_input'));  ?>" />
    </div>
<?php
}

function whatsform_page_title_callback()
{
?>
    <input name="whatsform_page_title_input" class="regular-text" type="text" style="margin:0" placeholder="Page title" value="<?php echo (get_option('whatsform_page_title_input') === false ? '' : esc_attr(get_option('whatsform_page_title_input'))); ?>" />
<?php
}

function whatsform_widget_snippet_callback()
{

?>
    <textarea name="whatsform_widget_snippet" id="whatsform_widget_snippet" rows="5" cols="30" style="width:400px;font-family:monospace;font-size:small;" <?php disabled(!current_user_can( 'unfiltered_html') ); ?>><?php echo wp_kses(get_option('whatsform_widget_snippet') === false ? '' : get_option('whatsform_widget_snippet'), array('script' => array('async' => array(), 'src' => array(), 'id' => array(), 'data-id' => array(), 'data-message' => array())));  ?></textarea>
    <?php
        if(!current_user_can( 'unfiltered_html' )) {
              echo '<p style="color:#ffc107"><b>Note:</b> ' . __('You do not have permission to add or edit scripts. Please contact your administrator.', 'whatsform') . '</p>';
        }
    ?>
<?php
}

function whatsform_widget_show_on_callback()
{
    $showOn = get_option('whatsform_widget_show_on') === false ? 'all' : get_option('whatsform_widget_show_on');
?>

    <input type="radio" name="whatsform_widget_show_on" value="all" id="all" <?php checked('all', $showOn); ?> <?php disabled(!current_user_can( 'unfiltered_html') ); ?>> <label class="whatsform-plugin-label" for="all"><?php _e('Everywhere'); ?> </label><br />
    <input type="radio" name="whatsform_widget_show_on" value="home" id="home" <?php checked('home', $showOn); ?> <?php disabled(!current_user_can( 'unfiltered_html') ); ?>> <label class="whatsform-plugin-label" for="home"><?php _e('Homepage Only'); ?> </label><br />
    <input type="radio" name="whatsform_widget_show_on" value="nothome" id="nothome" <?php checked('nothome', $showOn); ?> <?php disabled(!current_user_can( 'unfiltered_html') ); ?>> <label class="whatsform-plugin-label" for="nothome"><?php _e('Everywhere except Home'); ?> </label><br />
    <input type="radio" name="whatsform_widget_show_on" value="none" id="none" <?php checked('none', $showOn); ?> <?php disabled(!current_user_can( 'unfiltered_html') ); ?>> <label class="whatsform-plugin-label" for="none"><?php _e('Nowhere'); ?> </label>
<?php
}




function whatsform_admin_enqueue_scripts()
{
    require_once(WHATSFORM_PLUGIN_DIR . '/feedback-form.php');
    wp_enqueue_style('whatsform-modal-css', plugin_dir_url(__FILE__) . 'css/modal.css');
    whatsform_add_feedback_form();
}
add_action('admin_enqueue_scripts', 'whatsform_admin_enqueue_scripts');

function whatsform_submit_uninstall_reason_action()
{
    global  $wp_version, $whatsform_active_plugin, $current_user;

    check_ajax_referer('whatsform_ajax_nonce', 'whatsform_ajax_nonce');

    $reason_id = isset($_REQUEST['reason_id']) ? stripcslashes(sanitize_text_field($_REQUEST['reason_id'])) : '';
    $basename  = isset($_REQUEST['plugin']) ? stripcslashes(sanitize_text_field($_REQUEST['plugin'])) : '';

    if (empty($reason_id) || empty($basename)) {
        exit;
    }

    $reason_info = isset($_REQUEST['reason_info']) ? stripcslashes(sanitize_textarea_field($_REQUEST['reason_info'])) : '';
    if (!empty($reason_info)) {
        $reason_info = substr($reason_info, 0, 255);
    }
    $is_anonymous = isset($_REQUEST['is_anonymous']) && 1 == $_REQUEST['is_anonymous'];

    $options = array(
        'product'     => 'whatsform_WP_PLUGIN',
        'reason_id'   => $reason_id,
        'reason_info' => $reason_info,
    );

    if (!$is_anonymous) {


        $options['url']                  = get_site_url();
        $options['wp_version']           = $wp_version;
        $options['plugin_version']       = $whatsform_active_plugin[$basename]['Version'];

        $options['email'] = $current_user->data->user_email;
    }

    /* send data */

    wp_remote_post(
        "https://app.whatsform.com/wordpress/churn",
        array(
            'method'  => 'POST',
            'body'    => $options,
            'timeout' => 15,
        )
    );
    exit;
}
add_action('wp_ajax_whatsform_submit_uninstall_reason_action', 'whatsform_submit_uninstall_reason_action');

/**
 * Insert the widget code into page head. In-post snippet prioritised over general one.
 */
function whatsform_wp_head()
{
    $whatsform_inpost_snippet = get_post_meta(get_the_ID(), '_whatsform_inpost_snippet', true);
    $widget_snippet = get_option('whatsform_widget_snippet');
    $widget_show_on = get_option('whatsform_widget_show_on');

    if ($whatsform_inpost_snippet && $whatsform_inpost_snippet != '' && !is_home() && !is_front_page()) {
        echo wp_kses($whatsform_inpost_snippet, array('script' => array('async' => array(), 'src' => array(), 'id' => array(), 'data-id' => array(), 'data-message' => array())));
    } elseif ($widget_snippet && $widget_show_on) {
        if (($widget_show_on === 'all') || ($widget_show_on === 'home' && (is_home() || is_front_page())) || ($widget_show_on === 'nothome' && !is_home() && !is_front_page())) {
            echo wp_kses($widget_snippet, array('script' => array('async' => array(), 'src' => array(), 'id' => array(), 'data-id' => array(), 'data-message' => array())));
        }
    }
}

add_action('wp_head', 'whatsform_wp_head');

/**
 * In-post Widget snippet code
 */
require_once(WHATSFORM_PLUGIN_DIR . '/inpost-snippet.php');


/**
 * Proper ob_end_flush() for all levels
 *
 * This replaces the WordPress `wp_ob_end_flush_all()` function
 * with a replacement that doesn't cause PHP notices.
 */
remove_action('shutdown', 'wp_ob_end_flush_all', 1);
add_action('shutdown', function () {
    while (@ob_end_flush());
});
