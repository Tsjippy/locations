<?php
namespace SIM\LOCATIONS;
use SIM;

const MODULE_VERSION		= '8.0.9';

DEFINE(__NAMESPACE__.'\MODULE_PATH', plugin_dir_path(__DIR__));

//module slug is the same as grandparent folder name
DEFINE(__NAMESPACE__.'\MODULE_SLUG', strtolower(basename(dirname(__DIR__))));

// check for dependicies
add_filter('sim_submenu_locations_description', __NAMESPACE__.'\moduleDescription');
function moduleDescription($description){
	ob_start();

	?>
	<p>
		<strong>Auto created page:</strong><br>
		<a href='<?php echo home_url('/locations');?>'>Locations</a><br>
	</p>
	<?php

	return $description.ob_get_clean();
}

add_filter('sim_submenu_locations_options', __NAMESPACE__.'\moduleOptions', 10, 2);
function moduleOptions($optionsHtml, $settings){
	global $wpdb;
	
	$query 		= 'SELECT * FROM `'.$wpdb->prefix .'ums_icons` WHERE 1';
	$results 	= $wpdb->get_results($query);
	$icons		= [];

	foreach($results as $icon){
		$icons[$icon->id]	= $icon;
	}

	ob_start();
	wp_enqueue_style('sim_locations_admin_style', SIM\pathToUrl(MODULE_PATH.'css/admin.min.css'), array(), MODULE_VERSION);
	wp_enqueue_script('sim_locations_admin_script', SIM\pathToUrl(MODULE_PATH.'js/locations_admin.min.js'), array(), MODULE_VERSION, true);

	if(empty($settings['page-gallery-background-color'])){
		$settings['page-gallery-background-color']	= '#FFFFFF';
	}
	if(empty($settings['media-gallery-background-color'])){
		$settings['media-gallery-background-color']	= '#FFFFFF';
	}
	?>
	<label>
		Give Google API key for location lookup. See <a href='https://developers.google.com/maps/documentation/javascript/get-api-key'>here</a><br>
		<input type='text' name='google-maps-api-key' value='<?php echo $settings['google-maps-api-key'];?>' style='width:400px;'>
	</label>
	<br>
	<br>
	<label>
		Select a background color for any page galleries on location pages<br>
		<input type='color' name='page-gallery-background-color' value='<?php echo $settings['page-gallery-background-color'];?>'>
	</label>
	<br>
	<br>
	<label>
		Select a background color for any media galleries on location pages<br>
		<input type='color' name='media-gallery-background-color' value='<?php echo $settings['media-gallery-background-color'];?>'>
	</label>
	<br>
	<br>
	<label>
		<input type='checkbox' name='gallery-background-color-gradient' value='1' <?php if(!empty($settings['gallery-background-color-gradient'])){echo 'checked';}?>>
		Smooth the edges of the gallery background colors
	</label>
	<br>

	<?php
	
	$categories = get_categories( array(
		'orderby' 	=> 'name',
		'order'   	=> 'ASC',
		'taxonomy'	=> 'locations',
		'hide_empty'=> false,
	) );

	foreach($categories as $locationtype){
		$name 				= $locationtype->slug;
		$mapName			= $name."_map";
		$iconName			= $name."_icon";
		?>
		<label for="<?php echo $mapName;?>">Map showing <?php echo strtolower($name);?></label>
		<select name="<?php echo $mapName;?>" id="<?php echo $mapName;?>">
			<option value="">---</option>
			<?php echo getMaps($settings[$mapName]); ?>
		</select>
		
		<label>Icon on the map used for <?php echo $name;?></label>
		<div class='icon_select_wrapper'>
			<input type='hidden' class='icon_id' name='<?php echo $iconName;?>' value='<?php echo $settings[$iconName];?>'>
			<br>
			<div class="dropdown">
				<?php
				if(is_numeric($settings[$iconName])){
					$url		= $icons[$settings[$iconName]]->path;

					if(!str_contains($url, '://' )){
						$url = SIM\pathToUrl(MODULE_PATH."ultimate-maps-by-supsystic/modules/icons/icons_files/def_icons/$url");
					}
					$img		= "<img src='$url' class='icon' data-id='{$settings[$iconName]}' loading='lazy'>";
					$buttonText	= "Change";
				}else{
					$img	= "";
					$buttonText	= "Select";
				}
				?>
				<div class="icon_preview">
					<?php echo $img;?>
				</div>

				<button type='button' class='dropbtn'><?php echo $buttonText;?> Icon</button>

				<div class="dropdown-content">
					<?php
					foreach($icons as $icon){
						if($icon->description == 'custom icon'){
							continue;
						}
						$url = plugins_url('ultimate-maps-by-supsystic/modules/icons/icons_files/def_icons/'.$icon->path);
						echo "<div class='icon'><img src='$url' class='icon' data-id='$icon->id' loading='lazy'> $icon->description</div><br>";
					}
					?>
				</div>
			</div>
		</div>
		<br>
		<br>
		<?php
	}
	?>

	<?php
	return $optionsHtml.ob_get_clean();
}

/**
 * Location maps
 */
function getMaps($optionValue){
	$mapOptions = "";

	$maps	= new Maps();
	
	foreach ( $maps->getMaps() as $map ) {
		if ($optionValue == $map->id){
			$selected='selected=selected';
		}else{
			$selected="";
		}
		$mapOptions .= "<option value='$map->id' $selected>$map->title</option>";
	}
	return $mapOptions;
}

//run on module activation
add_filter('sim_module_locations_after_save', __NAMESPACE__.'\moduleUpdated');
function moduleUpdated($options){
	SIM\ADMIN\installPlugin('ultimate-maps-by-supsystic/ums.php');

	$maps		= new Maps();

	foreach(['Directions', 'Users'] as $title){
		$mapKey	= strtolower($title).'_map_id';

		//Check if defined in settings already
		if($options[$mapKey]){
			// Double check the defined map exists
			$map	= $maps->getMaps("`id`='{$options[$mapKey]}'");

			// map exist
			if(!empty($map)){
				continue;
			}

		}

		// create the map
		$options[$mapKey]	= $maps->addMap($title, '9.910260', '8.889170', '', '400', 6);
	}

	if(!get_term_by('slug', 'ministry', 'locations')){
		wp_insert_term( 'Ministries', 'locations', ['slug' => 'ministry']);
	}

	if(!SIM\getModuleOption('pagegallery', 'enable')){
		SIM\ADMIN\enableModule('pagegallery');
	}

	return $options;
}

//run on module activation
add_action('sim_module_locations_activated', __NAMESPACE__.'\moduleActivated');
function moduleActivated($options){
	// add an extra form setting column in db
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    require_once ABSPATH . 'wp-admin/install-helper.php';

	$forms	= new SIM\FORMS\SimForms();

	maybe_add_column($forms->tableName, 'google_maps_api', "ALTER TABLE $forms->tableName ADD COLUMN `google_maps_api` bool");
}
