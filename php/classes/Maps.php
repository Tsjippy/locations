<?php

namespace TSJIPPY\LOCATIONS;

use TSJIPPY;

if (! defined('ABSPATH')) {
    exit;
}

class Maps
{
    public $mapTable;
    public $markerTable;

    public function __construct()
    {
        global $wpdb;

        $this->mapTable        = $wpdb->prefix . 'ums_maps';
        $this->markerTable    = $wpdb->prefix . 'ums_markers';
    }

    /**
     * Function to retrieve all maps from the db
     *
     * @return    array        array of map objects
     */
    public function getMaps($where = 1)
    {
        global $wpdb;

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT  `id`,`title` FROM %i WHERE $where ORDER BY `title` ASC",
                $this->mapTable
            )
        );
    }

    /**
     * Function to retrieve a specific map from the db
     *
     * @return    array        array of map objects
     */
    public function getMap($mapId)
    {
        return $this->getMaps("id=$mapId ");
    }

    /**
     *
     * Add a new map to the db
     *
     * @param    string    $name        The map name
     * @param    string    $lattitude    The lattitude of the map center
     * @param    string    $longitude    The longitude of the map center
     * @param    string    $address    The address of the map
     * @param    int        $height        The height of the map. Default 400px.
     * @param    int        $zoom        The zoom level of the map. Default 6
     *
     * @return    int                    The new map id
     */
    public function addMap($name, $lattitude = '9.910260', $longitude = '8.889170', $address = '', $height = '400', $zoom = 6)
    {
        global $wpdb;

        //Add a map
        $params                                      = [];
        $params['width_units']                       = '%';
        $params['membershipEnable']                  = 0;
        $params['adapt_map_to_screen_height']        = 0;
        $params['map_type']                          = 'https://server.arcgisonline.com/ArcGIS/rest/services/World_Street_Map/MapServer/tile/{z}/{y}/{x}';
        $params['map_center']                        = array(
            'address' => $address,
            'coord_x' => $lattitude,
            'coord_y' => $longitude
        );
        $params['mouse_wheel_zoom']                  = 1;
        $params['zoom']                              = $zoom;
        $params['zoom_mobile']                       = $zoom + 2;
        $params['zoom_min']                          = 1;
        $params['zoom_max']                          = 21;
        $params['navigation_bar_mode']               = 'full';
        $params['dbl_click_zoom']                    = 1;
        $params['draggable']                         = 'enable';
        $params['marker_title_color']                = '#000000';
        $params['marker_title_size']                 = 19;
        $params['marker_title_size_units']           = 'px';
        $params['marker_desc_size']                  = 13;
        $params['marker_desc_size_units']            = 'px';
        $params['marker_infownd_width']              = 200;
        $params['marker_infownd_width_units']        = 'px';
        $params['marker_infownd_height']             = 400;
        $params['marker_infownd_height_units']       = 'auto';
        $params['marker_infownd_bg_color']           = '#FFFFFF';
        $params['marker_clasterer']                  = 'MarkerClusterer';
        $params['marker_clasterer_grid_size']        = 10;
        $params['marker_clasterer_background_color'] = '#bd2919';
        $params['marker_clasterer_border_color']     = '#bc1907';
        $params['marker_clasterer_text_color']       = 'white';
        $params['marker_hover']                      = 0;
        $params['markers_list_color']                = '#55BA68';

        $htmlOptions = [
            'width'        => '100',
            'height'    => $height
        ];

        //Insert the map in the db
        return TSJIPPY\insertInDb(
            $this->mapTable,
            array(
                'title'        => $name,
                'params'       => serialize($params),
                'html_options' => serialize($htmlOptions),
                'create_date'  => gmdate("Y-m-d G:i:s")
            ),
            [],
            'locations'
        );
    }

    /**
     *
     * Remove an existing map
     *
     * @param    int    $mapId        The id of the map to be removed
     *
     */
    public function removeMap($mapId)
    {
        global $wpdb;

        //remove the map
        TSJIPPY\removeFromDb(
            $this->mapTable, 
            array('id' => $mapId),
            ['%d'],
            'locations'
        );

        //Remove all markers on this map
        $markers     = TSJIPPY\getFromDb(
            "get_marker_id_from_$mapId",
            "locations",
            "SELECT id FROM %i WHERE map_id = %d ",
            $this->markerTable,
            $mapId
        );

        foreach ($markers as $marker) {
            //Delete the marker
            $this->removeMarker($marker->id);
        }
    }

    /**
     * Checks is a given marker id exists
     *
     * @param    int        $markerId The marker id to check
     *
     * @return    bool                True if exists false otherwise
     */
    public function markerExists($markerId)
    {
        global $wpdb;

        $result = TSJIPPY\getFromDb(
            "check_if_marker_exists_$markerId",
            "locations",
            "SELECT id FROM {$this->markerTable} WHERE id = %d LIMIT 1",
            $markerId
        );

        if ($result == null) {
            return false;
        }

        return true;
    }

    public function checkCoordinates($lattitude, $longitude)
    {
        if (strlen($lattitude) < 3 || strlen($longitude) < 3 || !str_contains($lattitude, ' . ') || !str_contains($longitude, ' . ')) {
            return new \WP_Error('maps', 'Please give valid coordinates');
        }

        return true;
    }

    /**
     * Creates a marker for a given user
     *
     * @param    int        $userId        WP_User id
     * @param    array    $location    array containing lat and lon
     *
     */
    public function createUserMarker($userId, $location)
    {
        global $wpdb;

        if (empty($location['latitude']) || empty($location['longitude'])) {
            return false;
        }

        $family    = new TSJIPPY\FAMILY\Family();

        $check    = $this->checkCoordinates($location['latitude'], $location['longitude']);
        if (is_wp_error($check)) {
            return $check;
        }

        $userdata = get_userdata($userId);

        $privacyPreference = (array)get_user_meta($userId, 'tsjippy_privacy_preference', true);

        //Do not continue if privacy dictates so
        if ($privacyPreference == "show_none") {
            return;
        }
        $title    = $family->getFamilyName($userdata);

        //Add the profile picture to the marker description if it is set, and allowed by privacy
        if (empty($privacyPreference['hide_profile_picture'])) {
            $loginName = $userdata->user_login;
            if (is_numeric(get_user_meta($userId, 'tsjippy_profile_picture', true)) && function_exists('TSJIPPY\USERMANAGEMENT\getProfilePictureUrl')) {
                $iconUrl = TSJIPPY\USERMANAGEMENT\getProfilePictureUrl($userId, 'thumbnail');
            } else {
                $iconUrl = "";
            }

            //Save picture as icon and get the icon id
            $iconId = $this->createIcon('', $loginName, $iconUrl, 1);
        } else {
            $iconId = 1;
        }

        //Insert it all in the database
        $markerId = TSJIPPY\insertInDb(
            $this->markerTable, 
            array(
                'title'       => $title,
                'description' => "[tsjippy_markerdescription userid='$userId']",
                'coord_x'     => $location['latitude'],
                'coord_y'     => $location['longitude'],
                'icon'        => $iconId,
                'map_id'      => SETTINGS['users_map_id'] ?? '',
            ),
            [
                '%s',
                '%s',
                '%s',
                '%s',
                '%d',
                '%d'
            ],
            'locations'
        );

        //Add the marker id to the user database
        update_user_meta($userId, "tsjippy_marker_id", $markerId);
    }

    /**
     * Updates the location of a given marker id
     *
     * @param     int        $markerId    the id of th emarker to update
     * @param    array    $location    array containing the lat and lon
     */
    public function updateMarkerLocation($markerId, $location)
    {
        global $wpdb;

        if (is_numeric($markerId) && is_array($location) && !empty($location['latitude']) && !empty($location['longitude'])) {
            $check    = $this->checkCoordinates($location['latitude'], $location['longitude']);
            if (is_wp_error($check)) {
                return $check;
            }

            //Update the marker
            $result = TSJIPPY\updateDbValue(
                $this->markerTable,
                array(
                    'coord_x' => $location['latitude'],
                    'coord_y' => $location['longitude'],
                ),
                array('ID' => $markerId),
                [
                    '%s',
                    '%s',
                ],
                ['%d'],
                'locations'
            );
        }
    }

    /**
     * Updates the title of a given marker id
     *
     * @param     int        $markerId    the id of th emarker to update
     * @param    string    $title        the new marker title
     */
    public function updateMarkerTitle($markerId, $title)
    {
        global $wpdb;

        if (is_numeric($markerId)) {
            //Update the marker
            $result = TSJIPPY\updateDbValue(
                $this->markerTable,
                array(
                    'title' => $title,
                ),
                array('ID' => $markerId),
                ['%s'],
                ['%d'],
                'locations'
            );

            /**
             * Flush db cache
             */
            if(wp_cache_supports( 'flush_group' )){
                wp_cache_flush_group('locations');
            }else{
                wp_cache_flush();
            }
        }
    }

    /**
     * Remove a marker
     *
     * @param    int        $markerId    The marker id of the marker to be removed
     */
    public function removeMarker($markerId)
    {
        global $wpdb;

        //First delete any custom icon
        $this->removeIcon($markerId);

        TSJIPPY\removeFromDb(
            $this->markerTable, 
            array('id' => $markerId),
            ['%d'],
            'locations'
        );
        TSJIPPY\printArray("Removed the marker with id $markerId");
        
    }

    /**
     * Remove the marker belonging to an user
     *
     * @param    int        $userId        the user id
     */
    public function removePersonalMarker($userId)
    {
        //Check if a previous marker exists for this user_id
        $markerId = get_user_meta($userId, "tsjippy_marker_id", true);

        //There exists a previous marker for this person, remove it
        if (is_numeric($markerId)) {
            //Delete the personal marker
            $this->removeMarker($markerId);
            delete_user_meta($userId, 'tsjippy_marker_id');
        }
    }

    /**
     * Remove all markers belonging to a location
     *
     * @param    int        $postId        The id of the WP_Post
     */
    public function removePostMarkers($postId)
    {
        $markerIds = get_post_meta($postId, "tsjippy_marker_ids", true);

        //Marker exist, remove it
        if (is_array($markerIds)) {
            foreach ($markerIds as $markerId) {
                //Delete the marker
                $this->removeMarker($markerId);
            }
        }
    }

    /**
     * Remove the icon of a marker
     *
     * @param    int        $markerId    the id of the marker the icon belongs to
     */
    public function removeIcon($markerId)
    {
        global $wpdb;

        if (!is_numeric($markerId)) {
            TSJIPPY\printArray("No marker to remove");
            return;
        }

        $markerIconId     = TSJIPPY\getFromDb(
            "get_marker_{$markerId}_icon",
            "locations",
            "SELECT icon FROM %i WHERE id = %d LIMIT 1",
            $this->markerTable,
            $markerId
        );

        if (!is_numeric($markerIconId)) {
            if (!empty($markerIconId)) {
                TSJIPPY\printArray("Marker id is $markerId but this marker is not found in the db, marker_icon_id is $markerIconId");
            }

            return;
        }

        //Icons with id 47 and lower belong to the map plugin
        if ($markerIconId > 47) {
            //Remove the icon
            $wpdb->delete($wpdb->prefix . 'ums_icons', array('id' => $markerIconId));

            //Reset the marker id to the default icon
            $result = TSJIPPY\updateDbValue(
                $this->markerTable,
                array('icon' => 1,),
                array('id' => $markerId),
                ['%d'],
                ['%d'],
                'locations'
            );
        } else {
            TSJIPPY\printArray("Icon is already the default");
        }
    }

    /**
     * Create an icon for a marker
     *
     * @param    int        $markerId    The marker id the icon should be added to
     * @param    string    $title        The title for the icon
     * @param    string    $url        The url for the icon image
     * @param    int        $defaultId    The id of the default icon
     *
     * @return    int                    The id of the current or created marker
     */
    public function createIcon($markerId, $title, $url, $defaultId)
    {
        global $wpdb;

        $currentMarkerIconId = $defaultId;
        $icon                = '';

        //Check if marker id is given
        if (is_numeric($markerId)) {

            //Get the icon id of this marker
            $currentMarkerIconId     = TSJIPPY\getFromDb(
                "get_marker_{$markerId}_icon",
                "locations",
                "SELECT icon FROM %i WHERE id = %d LIMIT 1",
                $this->markerTable,
                $markerId
            );

            //check if marker icon id exists
            if (is_numeric($currentMarkerIconId)) {
                $icon     = TSJIPPY\getFromDb(
                    "get_marker_icon_$currentMarkerIconId",
                    "locations",
                    "SELECT * FROM %i WHERE id = %d LIMIT 1", 
                    "{$wpdb->prefix}ums_icons",
                    $currentMarkerIconId
                );
            } else {
                $currentMarkerIconId     = $defaultId;
            }
        }

        //check if an icon exists
        if(empty($icon)){
            $icon     = TSJIPPY\getFromDb(
                "get_marker_icon_$title",
                "locations",
                "SELECT * FROM %i WHERE title = %s LIMIT 1", 
                "{$wpdb->prefix}ums_icons",
                $title
            );
        }

        if (!empty($icon)) {
            $icon    = $icon[0];
        }

        if ($icon->path == $url) {
            //no update needed
            return $icon->id;
        }

        //Marker icon is still the default and there is no icon with this id
        //Potentially the profile image of the partner is used
        if ($currentMarkerIconId == $defaultId) {
            //Create new icon if there is an icon url
            if (!empty($url)) {
                //Insert picture as icon in the database
                $iconId = TSJIPPY\insertInDb(
                    $wpdb->prefix . 'ums_icons', 
                    array(
                        'title'       => $title,
                        'description' => 'custom icon',
                        'path'        => $url,
                        'width'       => '44',
                        'height'      => '44',
                        'is_def'      => '0'
                    ),
                    [
                        '%s',
                        '%s',
                        '%s',
                        '%d',
                        '%d',
                        '%d'
                    ],
                    'locations'
                );

                //Update the marker to use the new icon
                $result = TSJIPPY\updateDbValue(
                    $this->markerTable,
                    array('icon' => $iconId,),
                    array('id'   => $markerId),
                    ['%d'],
                    ['%d'],
                    'locations'
                );
            } else {
                //No icon url, use default icon
                $iconId = $defaultId;
            }
            //Only update if there is an icon url
        } elseif (!empty($url)) {
            //Update marker icon
            $result = TSJIPPY\updateDbValue(
                $wpdb->prefix . 'ums_icons',
                array(
                    'path'     => $url,
                    'title' => $title,
                ),
                array('id' => $icon->id),
                ['%s','%s'],
                ['%d'],
                'locations'
            );

            //Reset the marker id to the custom icon
            if (is_numeric($markerId)) {
                $result = TSJIPPY\updateDbValue(
                    $this->markerTable,
                    array('icon' => $icon->id,),
                    array('id'   => $markerId),
                    ['%d'],
                    ['%d'],
                    'locations'
                );
            }
            $iconId    = $icon->id;
        } else {
            $iconId = $currentMarkerIconId;
        }

        return $iconId;
    }
}
