<?php

namespace TSJIPPY\LOCATIONS;

use TSJIPPY;

if (! defined('ABSPATH')) {
    exit;
}

// Create the location custom post type
add_action('init', function () {
    TSJIPPY\registerPostTypeAndTax('location', 'locations');
}, 999);

// add the marker id to the family meta keys
add_filter('tsjippy-family-meta-keys', function ($metaKeys) {
    $metaKeys[]    = 'marker_id';

    return $metaKeys;
});

//Add a map when a location category is added
add_action('tsjippy-frontend-content-after-category-add', __NAMESPACE__ . '\afterCategoryAdd', 10, 3);
function afterCategoryAdd($postType, $name, $result)
{
    if ($postType != 'location') {
        return;
    }

    $Maps        = new Maps();
    $mapId        = $Maps->addMap($name);

    //Attach the map id to the term
    update_term_meta($result['term_id'], 'tsjippy_map_id', $mapId);

    $settings    = SETTINGS;

    $settings[$name . '-map']    = $mapId;

    update_option('tsjippy_locations_settings', $settings);
}

add_filter('widget_categories_args', __NAMESPACE__ . '\widgetCats');
function widgetCats($catArgs)
{
    //if we are on a locations page, change to display the location types
    if (is_tax('locations') || is_page('location') || get_post_type() == 'location') {
        $catArgs['taxonomy']         = 'locations';
        $catArgs['hierarchical']    = true;
        $catArgs['hide_empty']         = false;
    }

    return $catArgs;
}

add_filter('widget_title', __NAMESPACE__ . '\widgetTitle', 999, 2);
function widgetTitle($title, $widgetId = null)
{
    //Change the title of the location category widget if not logged in
    if (is_tax('locations') && $widgetId == 'categories' && !is_user_logged_in()) {
        $url = TSJIPPY\SITEURL . '/locations/ministry/';
        return "<a href='$url'>Ministries</a>";
    }
    return $title;
}

//Remove marker when post is sent to trash
add_action('wp_trash_post', __NAMESPACE__ . '\trashPost');
function trashPost($postId)
{
    $maps    = new Maps();
    $maps->removePostMarkers($postId);
}

add_filter('tsjippy-template-filter', __NAMESPACE__ . '\templateFilter');
function templateFilter($templateFile)
{
    global $post;

    if (in_array('locations', get_post_taxonomies()) && in_array('ministry', wp_get_post_terms($post->ID, 'locations', ['fields' => 'slugs']))) {
        return str_replace('single-location', 'single-ministry', $templateFile);
    }
    return $templateFile;
}

/**
 * Get the people that work at a certain location
 *
 * @param   int|\WP_Post    $post        WP_Post or post id 
 * @param   bool            $echo       Whether to print to screen    
 */
function getLocationEmployees($post, $echo)
{

    wp_enqueue_style('tsjippy_employee_style');

    if (!is_user_logged_in()) {
        return '';
    }

    if (is_numeric($post)) {
        $post    = get_post($post);
    }

    $locations        = array_keys(get_children(array(
        'post_parent'    => $post->ID,
        'post_type'      => 'location',
        'post_status'    => 'publish',
    )));
    $locations[]    = $post->ID;

    //Loop over all users to see if they work here
    $users             = get_users('orderby=display_name');

    if(!$echo){
        ob_start();
    }

    if (empty($users)) {
        ?>
        No workers are currently affiliated with this ministry.<br>
        <?php

        $url     = get_edit_profile_url(get_current_user_id());
        ?>
        If you work here indicate so on your 
        <a href='<?php echo esc_url($url); ?>/?main-tab=generic-info#ministries'>
            Profile Page
        </a>
        <?php

        if($echo){
            return;
        }else{
            return ob_get_clean();
        }
    }

    ?>
    <div style='padding:10px;text-align: center;'>
        <h3 style='text-align:center'>
            People who Work at $post->post_title
        </h3>
        <br>
        <br>
        <div class='employee-gallery'>
            <?php

            foreach ($users as $user) {
                $userLocations = (array)get_user_meta($user->ID, "tsjippy_jobs", true);

                $intersect     = array_intersect(array_keys($userLocations), $locations);

                //If a user works for this ministry, echo its name and position
                if ($intersect) {
                    $userPageUrl       = TSJIPPY\maybeGetUserPageUrl($user->ID);
                    $privacyPreference = get_user_meta($user->ID, 'tsjippy_privacy_preference');
                    $class             = 'description';
                    if (isset($privacyPreference['hide_profile_picture'])) {
                        $class         .= ' empty-picture';
                    }

                    if (!isset($privacyPreference['hide_ministry'])) {
                        ?>
                        <div class='person-wrapper'>
                            <?php
                            if (!isset($privacyPreference['hide_profile_picture'])) {
                                TSJIPPY\displayProfilePicture(userId: $user->ID, echo: true);
                            }

                            $pageUrl = "";
                            foreach ($intersect as $postId) {
                                $job    = ucfirst($userLocations[$postId]);
                                ?>
                                <div class='$class'>
                                    <a class='user-link' href='<?php echo esc_url($userPageUrl);?>'>
                                        <?php echo esc_html($user->display_name);?>
                                    </a>
                                    <br>
                                    (<?php echo esc_html(ucfirst($userLocations[$postId]));?>)
                                </div>
                                <?php
                            }
                            ?>
                        </div>
                        <?php
                    }
                }
            }
            ?>
        </div>
    </div>
    <?php

    if(!$echo){
        return ob_get_clean();
    }
}

add_filter('tsjippy-theme-archive-page-title', __NAMESPACE__ . '\changeArchiveTitle', 10, 2);
function changeArchiveTitle($title, $category)
{
    if (is_post_type_archive('location')) {
        $title = 'Locations';
    }

    return $title;
}
