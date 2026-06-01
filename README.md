This module adds a custom post type 'locations'.<br>
Locations can be used to share shops, hotels ministries etc.<br>
They will bevisible on a map.<br>
<br>
It adds one shortcode:<br>
<code>[ministry_description name=SOMENAME]</code>

== External services ==

This plugin connects to an the Google Services to use Google Maps to get a route

== Hooks ==
# FILTERS
- apply_filters('sim-locations-array', []);
- apply_filters('sim-empty-taxonomy', 'There are no locations submitted yet.', 'location'); 
- apply_filters('sim_empty_description', 'No content found...', $post);
- apply_filters('sim-single-template-bottom', '', 'location');
- apply_filters('sim-empty-taxonomy', "There are no $name locations yet", 'location'); ?>