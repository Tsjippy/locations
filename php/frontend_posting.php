<?php

namespace TSJIPPY\LOCATIONS;

use TSJIPPY;

if (! defined('ABSPATH')) {
    exit;
}

add_action('tsjippy-frontend-content-post-content-title', __NAMESPACE__ . '\contentTitle');
function contentTitle($postType)
{
    //Location content title
    ?>
    <h4 class='property location <?php if ($postType != 'location') echo 'hidden'; ?>' name='location-content-label'>";
        Please describe the location
    </h4>
    <?php
}

/**
 * Allow comments
 * 
 * @param   \WP_Post    $post       The new or updated post
 * @param   object      $object     FrontEndContent Instance
 * @param   array       $request    The sanitized request data
 */
add_action('tsjippy-frontend-content-after-post-save', __NAMESPACE__ . '\afterPostSave', 10, 3);
function afterPostSave($post, $frontEndPost, $request)
{
    if ($post->post_type != 'location') {
        return;
    }

    //tel
    if (isset($request['tel'])) {
        if (empty($request['tel'])) {
            delete_post_meta($post->ID, 'tsjippy_tel');
        } else {
            //Store serves
            update_metadata('post', $post->ID, 'tsjippy_tel', $request['tel']);
        }
    }

    //url
    if (isset($request['url'])) {
        if (empty($request['url'])) {
            delete_post_meta($post->ID, 'tsjippy_url');
        } else {
            //Store serves
            update_metadata('post', $post->ID, 'tsjippy_url', $request['url'], 'url');
        }
    }

    setLocationAddress($post->ID, $request);
}

add_action('tsjippy-sim-nigeria-ministry-added', __NAMESPACE__ . '\setLocationAddress', 10, 2);
/**
 * Store location details in meta
 */
function setLocationAddress($postId, $request)
{
    if (
        isset($request['location'])                &&
        isset($request['location']['latitude'])    &&
        isset($request['location']['longitude'])  &&
        !empty($request['location']['latitude'])  &&
        !empty($request['location']['longitude'])
    ) {
        update_metadata('post', $postId, 'tsjippy_location', json_encode(TSJIPPY\sanitize($request['location'])));
    }

    if (empty($request['location']['latitude']) && empty($request['location']['longitude']) && empty($request['location']['address'])) {
        //Delete the custom map for this post
        delete_metadata('post', $postId, 'tsjippy_location');
    }
}

/**
 * Creates a location map and marker if the metvalue is updated
 */
add_action('added_post_meta', __NAMESPACE__ . '\createLocationMarker', 10, 4);
add_action('updated_postmeta', __NAMESPACE__ . '\createLocationMarker', 10, 4);
function createLocationMarker($metaId, $postId,  $metaKey,  $location)
{
    if ($metaKey != 'location' || empty($location)) {
        return;
    }

    global $wpdb;

    $maps   = new Maps();

    if (!is_array($location)) {
        $location   = json_decode($location, true);
    }

    $address    = $location["address"]     = TSJIPPY\sanitize($location["address"]);
    $latitude    = $location["latitude"]   = TSJIPPY\sanitize($location["latitude"]);
    $longitude    = $location["longitude"] = TSJIPPY\sanitize($location["longitude"]);

    //Only update if needed
    if (empty($latitude) || empty($longitude)) {
        return;
    }

    $check    = $maps->checkCoordinates($latitude, $longitude);
    if (is_wp_error($check)) {
        return $check;
    }

    //Get marker array
    $markerIds = get_post_meta($postId, "tsjippy_marker_ids", true);
    if (!is_array($markerIds)) {
        $markerIds = [];
    }

    $title            = get_the_title($postId);

    $categories     = wp_get_post_terms(
        $postId,
        'locations',
        array(
            'orderby'   => 'name',
            'order'     => 'ASC'
        )
    );

    $description    = '[tsjippy_location_description id=$postId]';

    //Get url of the featured image
    $iconUrl        = get_the_post_thumbnail_url($postId);

    //Get the first category name
    $name           = get_term($categories[0], 'locations')->slug . "_icon";

    //If there is a location category set and an custom icon for this category is set
    if (!empty($categories) && SETTINGS[$name] ?? false) {
        $iconId = SETTINGS[$name];
    } else {
        $iconId = 1;
    }

    /*
        GENERIC MARKER
    */
    //Update existing marker
    if (isset($markerIds['generic']) && $maps->markerExists($markerIds['generic'])) {
        //Generic map, always update
        $result = TSJIPPY\updateDbValue(
            $wpdb->prefix . 'ums_markers',
            array(
                'description'    => $description,
                'coord_x'        => $latitude,
                'coord_y'        => $longitude,
                'address'        => $address,
            ),
            array('ID'           => $markerIds['generic']),
            [
                '%s',
                '%s',
                '%s',
                '%s',
            ],
            ['%d'],
            'locations'
        );
    } else {
        $mapId    =  SETTINGS['directions_map_id'] ?? false;

        //First create the marker on the generic map
        //Get the marker id
        $markerIds['generic'] = TSJIPPY\insertInDb(
            $wpdb->prefix . 'ums_markers',
            array(
                'title'       => $title,
                'description' => $description,
                'coord_x'     => $latitude,
                'coord_y'     => $longitude,
                'icon'        => $iconId,
                'map_id'      => $mapId,        //Generic map with all places
                'address'     => $address,
            ),
            [
                '%s',
                '%s',
                '%s',
                '%s',
                '%d',
                '%d',
                '%s',
            ],
            'locations'
        );
    }

    /*
        Location map
    */
    $mapId = get_post_meta($postId, 'tsjippy_map_id', true);

    // map no longer exists create a new one
    if (is_numeric($mapId) && empty($maps->getMap($mapId))) {
        //Create a map for this location
        $mapId = $maps->addMap($title, $latitude, $longitude, $address, '300', 10);

        //Save the map id in db
        update_metadata('post', $postId, 'tsjippy_map_id', $mapId);
    }

    //Update existing
    if (isset($markerIds['page_marker']) && $maps->markerExists($markerIds['page_marker'])) {
        //Create an icon for this marker
        $maps->createIcon($markerIds['page_marker'], $title, $iconUrl, $iconId);

        $result = TSJIPPY\updateDbValue(
            $wpdb->prefix . 'ums_markers',
            array(
                'title'       => $title,
                'description' => '[tsjippy_location_description id=$postId basic=true]',
                'coord_x'     => $latitude,
                'coord_y'     => $longitude,
                'address'     => $address,
            ),
            array('ID'        => $markerIds['page_marker']),
            [
                '%s',
                '%s',
                '%s',
                '%s',
                '%s',
            ],
            ['%d'],
            'locations'
        );
    }
    // Create new
    else {
        if (!is_numeric($mapId)) {
            //Create a map for this location
            $mapId = $maps->addMap($title, $latitude, $longitude, $address, '300', 10);

            //Save the map id in db
            update_metadata('post', $postId, 'tsjippy_map_id', $mapId);
        }

        //Create an icon for this marker
        $customIconId = $maps->createIcon(null, $title, $iconUrl, $iconId);

        //Add the marker to this map
        $markerIds['page_marker'] = TSJIPPY\insertInDb(
            $wpdb->prefix . 'ums_markers',
            array(
                'title'         => $title,
                'description'   => '[tsjippy_location_description id=$postId basic=true]',
                'coord_x'       => $latitude,
                'coord_y'       => $longitude,
                'icon'          => $customIconId,
                'map_id'        => $mapId,
                'address'       => $address,
            ),
            [
                '%s',
                '%s',
                '%s',
                '%s',
                '%d',
                '%d',
                '%s',
            ],
            'locations'
        );
    }

    /**
     *   Category maps
     */
    foreach ($categories as $category) {
        $name               = $category->slug;
        $mapName            = $name . "_map";
        $mapId              = SETTINGS[$mapName] ?? '';
        $iconName           = $name . "_icon";
        $iconId             = SETTINGS[$iconName] ?? 1;

        //Update existing
        if (is_numeric($markerIds[$name]) && $maps->markerExists($markerIds[$name])) {
            //Create an icon for this marker
            $maps->createIcon($markerIds[$name], $title, $iconUrl, $iconId);

            //Update the marker in db
            $result = TSJIPPY\updateDbValue(
                $wpdb->prefix . 'ums_markers',
                array(
                    'description' => $description,
                    'coord_x'     => $latitude,
                    'coord_y'     => $longitude,
                    'map_id'      => $mapId,
                    'address'     => $address,
                ),
                array('ID' => $markerIds[$name]),
                [
                    '%s',
                    '%s',
                    '%s',
                    '%d',
                    '%s',
                ],
                ['%d'],
                'locations'
            );
        } else {
            //Create an icon for this marker
            $customIconId = $maps->createIcon(null, $title, $iconUrl, $iconId);

            //Add marker for this map
            $markerIds[$name] = TSJIPPY\insertInDb(
                $wpdb->prefix . 'ums_markers',
                array(
                    'title'       => $title,
                    'description' => $description,
                    'coord_x'     => $latitude,
                    'coord_y'     => $longitude,
                    'icon'        => $customIconId,
                    'map_id'      => $mapId,
                    'address'     => $address,
                ),
                [
                    '%s',
                    '%s',
                    '%s',
                    '%s',
                    '%d',
                    '%d',
                    '%s',
                ],
                'locations'
            );

            TSJIPPY\printArray("Created marker with id {$markerIds[$name]} and title $title on map with id $mapId");
        }
    }

    //Store marker ids in db
    update_metadata('post', $postId, "tsjippy_marker_ids", $markerIds);
}

// Removes a map when post data is deleted
add_action('delete_post_meta', __NAMESPACE__ . '\postMetaDelete', 10, 4);
function postMetaDelete($metaIds, $postId, $metaKey, $metaValue)
{
    if ($metaKey != 'location') {
        return;
    }

    $markerIds  = get_metadata('post', $postId, "tsjippy_marker_ids", true);
    $mapId      = get_metadata('post', $postId, 'tsjippy_map_id', true);

    delete_metadata('post', $postId, 'tsjippy_map_id');
    delete_metadata('post', $postId, 'tsjippy_marker_ids');

    $maps   = new Maps();

    //Remove all markers related to this post
    foreach ($markerIds as $markerId) {
        $maps->removeMarker($markerId);
    }

    // Remove the location map
    $maps->removeMap($mapId);
}

//add meta data fields
add_action('tsjippy-frontend-content-post-before-default-options-content', __NAMESPACE__ . '\afterPostContent', 1, 2);
function afterPostContent($object)
{
    if (!empty($object->post) && $object->post->post_type != 'location') {
        return;
    }

    wp_enqueue_style('tsjippy_locations_style');

    addGoogleMapsApiKey();

    //Load js
    wp_enqueue_script('tsjippy_location_script');

    $postId     = $object->postId;
    $postName   = $object->postName;
    $location   = get_post_meta($postId, 'tsjippy_location', true);
    if (!is_array($location) && !empty($location)) {
        $location  = json_decode($location, true);
    }

    $address = '';
    if (isset($location['address'])) {
        $address = $location['address'];
    } elseif (get_post_meta($postId, 'tsjippy_geo_address', true) != '') {
        $address = get_post_meta($postId, 'tsjippy_geo_address', true);
    }

    $latitude = '';
    if (isset($location['latitude'])) {
        $latitude = $location['latitude'];
    } elseif (get_post_meta($postId, 'tsjippy_geo_latitude', true) != '') {
        $latitude = get_post_meta($postId, 'tsjippy_geo_latitude', true);
    }

    $longitude  = '';
    if (isset($location['longitude'])) {
        $longitude = $location['longitude'];
    } elseif (get_post_meta($postId, 'tsjippy_geo_longitude', true) != '') {
        $longitude = get_post_meta($postId, 'tsjippy_geo_longitude', true);
    }

    $url = get_post_meta($postId, 'tsjippy_url', true);
    ?>
    <div
        id="location-attributes"
        class="property location
        <?php if ($postName != 'location') echo ' hidden'; ?>">
        <fieldset id="location" class="frontend-form">
            <legend>
                <h4>
                    Location details
                </h4>
            </legend>

            <table class="form-table no-border left">
                <tr>
                    <th><label for="tel">Phone number</label></th>
                    <td>
                        <input type='tel' class='formbuilder' name='tel' value='<?php echo get_post_meta($postId, 'tsjippy_tel', true); ?>'>
                    </td>
                </tr>
                <tr>
                    <th><label for="url">Website</label></th>
                    <td>
                        <input type='url' class='formbuilder' name='url' value='<?php echo esc_url($url); ?>'>
                    </td>
                </tr>
                <tr>
                    <th><label for="address">Address</label></th>
                    <td>
                        <input type="text" class='formbuilder address' name="location[address]" value="<?php echo esc_attr($address); ?>">
                    </td>
                </tr>

                <tr>
                    <th>
                        <button class='current-location button small' type='button'>Use current location</button>
                    </th>
                    <td></td>
                </tr>

                <tr>
                    <th><label for="latitude">Latitude</label></th>
                    <td>
                        <input type="text" class='formbuilder latitude' name="location[latitude]" value="<?php echo esc_attr($latitude); ?>">
                    </td>
                </tr>
                <tr>
                    <th><label for="longitude">Longitude</label></th>
                    <td>
                        <input type="text" class='formbuilder longitude' name="location[longitude]" value="<?php echo esc_attr($longitude); ?>">
                    </td>
                </tr>
            </table>
        </fieldset>
    </div>
    <?php
}

add_action('tsjippy-frontend-content-post-after-content', __NAMESPACE__ . '\addPostMeta', 20);
/**
 * Add the comments section to the frontend post content
 * 
 * @param   object    $object    The FrontEndContent instance
 */
function addPostMeta($object)
{
    ?>
    <tbody class="frontend-form expand-wrapper property location <?php if ($object->postName != 'location') echo ' hidden'; ?>">
        <tr>
            <td>
                <h4>
                    Parent location
                </h4>
            </td>
            <td>
                <button class="button small expand" type='button'>
                    &#9660;
                </button>
            </td>
        </tr>

        <tr>
            <td class="hidden expandable" collspan=2>
                <?php
                TSJIPPY\pageSelect('parent-location', $object->postParent, '', ['location'], false, true);
                ?>
            </td>
        </tr>
    </tbody>

    <tbody class="frontend-form expand-wrapper property location <?php if ($object->postName != 'location') echo ' hidden'; ?>">
        <tr>
            <td>
                <h4>
                    Update warnings
                </h4>
            </td>
            <td>
                <button class="button small expand" type='button'>
                    &#9660;
                </button>
            </td>
        </tr>

        <tr>
            <td class="hidden expandable">
                <input
                    type='checkbox'
                    name='static-content'
                    value='static-content'
                    <?php if (get_post_meta($object->postId, 'tsjippy_static_content', true)) echo 'checked'; ?>>
                Do not send update warnings for this location
            </td>
        </tr>
    </tbody>
<?php
}

// Update marker icon
add_action('wp_after_insert_post', __NAMESPACE__ . '\afterInsertPost', 10, 3);
function afterInsertPost($postId, $post, $update)
{
    if ($post->post_type != 'location') {
        return;
    }

    $url        = get_the_post_thumbnail_url($postId);
    $markerIds  = get_post_meta($postId, "tsjippy_marker_ids", true);

    if (!is_array($markerIds) || !isset($markerIds['page_marker'])) {
        return;
    }

    $maps   = new Maps();

    // Update the url
    $maps->createIcon($markerIds['page_marker'], $post->post_title, $url, 1);
}
