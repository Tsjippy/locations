<?php
namespace TSJIPPY\LOCATIONS;
use TSJIPPY;

use function TSJIPPY\addRawHtml;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AdminMenu extends \TSJIPPY\ADMIN\SubAdminMenu{

    /**
     * AdminMenu constructor.
     * 
     * @param array $settings The settings for the plugin
     * @param string $name The name of the plugin
     */
    public function __construct($settings, $name){
        parent::__construct($settings, $name);
    }

    public function settings($parent){
        global $wpdb;
	
        $query 		= ;
        $results 	= $wpdb->get_results(
                $wpdb->prepare(
                    'SELECT * FROM %i WHERE 1',
                    $wpdb->prefix .'ums_icons'
                )
        );
        $icons		= [];

        foreach($results as $icon){
            $icons[$icon->id]	= $icon;
        }

        ob_start();
        wp_enqueue_style('tsjippy_locations_admin_style', TSJIPPY\pathToUrl(PLUGINPATH.'css/admin.min.css'), array(), PLUGINVERSION);
        wp_enqueue_script('tsjippy_locations_admin_script', TSJIPPY\pathToUrl(PLUGINPATH.'js/locations_admin.min.js'), array(), PLUGINVERSION, true);

        if(empty($this->settings['page-gallery-background-color'])){
            $this->settings['page-gallery-background-color']	= '#FFFFFF';
        }
        if(empty($this->settings['media-gallery-background-color'])){
            $this->settings['media-gallery-background-color']	= '#FFFFFF';
        }
        ?>
        <label>
            Give Google API key for location lookup. See <a href='https://developers.google.com/maps/documentation/javascript/get-api-key'>here</a><br>
            <input type='text' name='google-maps-api-key' value='<?php echo $this->settings['google-maps-api-key'] ?? '';?>' style='width:400px;'>
        </label>
        <br>
        <br>
        <label>
            Select a background color for any page galleries on location pages<br>
            <input type='color' name='page-gallery-background-color' value='<?php echo $this->settings['page-gallery-background-color'] ?? '';?>'>
        </label>
        <br>
        <br>
        <label>
            Select a background color for any media galleries on location pages<br>
            <input type='color' name='media-gallery-background-color' value='<?php echo $this->settings['media-gallery-background-color'] ?? '';?>'>
        </label>
        <br>
        <br>
        <label>
            <input type='checkbox' name='gallery-background-color-gradient' value='1' <?php if(!empty($this->settings['gallery-background-color-gradient'] ?? '')){echo 'checked';}?>>
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
            <label for="<?php echo esc_attr($mapName);?>">Map showing <?php echo strtolower($name);?></label>
            <select name="<?php echo esc_attr($mapName);?>" id="<?php echo esc_attr($mapName);?>">
                <option value="">---</option>
                <?php echo $this->getMaps($this->settings[$mapName] ?? ''); ?>
            </select>
            
            <label>Icon on the map used for <?php echo esc_attr($name);?></label>
            <div class='icon-select-wrapper'>
                <input type='hidden' class='no-reset' class='icon-id' name='<?php echo esc_attr($iconName);?>' value='<?php echo $this->settings[$iconName] ?? '';?>'>
                <br>
                <div class="dropdown">
                    <?php
                    if(is_numeric($this->settings[$iconName])){
                        $url		= $icons[$this->settings[$iconName] ?? '']->path;

                        if(!str_contains($url, '://' )){
                            $url = TSJIPPY\pathToUrl(WP_PLUGIN_DIR."ultimate-maps-by-supsystic/modules/icons/icons_files/def_icons/$url");
                        }
                        $img		= "<img src='$url' class='icon' data-id='{$this->settings[$iconName]}' loading='lazy'>";
                        $buttonText	= "Change";
                    }else{
                        $img	= "";
                        $buttonText	= "Select";
                    }
                    ?>
                    <div class="icon-preview">
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

        addRawHtml(ob_get_clean(), $parent);
        
        return true;
    }

    public function emails($parent){
        return false;
    }

    public function data($parent=''){

        return false;
    }

    public function functions($parent){

        return false;
    }

    /**
     * Function to do extra actions from $_POST data. Overwrite if needed
     */
    public function postActions(){
        return '';
    }

    /**
     * Schedules the tasks for this plugin
     *
    */
    public function postSettingsSave(){
        $maps		= new Maps();

        $message    = '';

        foreach(['Directions', 'Users'] as $title){
            $mapKey	= strtolower($title).'_map_id';

            //Check if defined in settings already
            if($this->settings[$mapKey]){
                // Double check the defined map exists
                $map	= $maps->getMaps("`id`='{$this->settings[$mapKey]}'");

                // map exist
                if(!empty($map)){
                    continue;
                }

            }

            // create the map
            $this->settings[$mapKey]	= $maps->addMap($title, '9.910260', '8.889170', '', '400', 6);

            $message    .= "Map created: '$title'<br>";
        }

        if(!get_term_by('slug', 'ministry', 'locations')){
            wp_insert_term( 'Ministries', 'locations', ['slug' => 'ministry']);
        }

        update_option("tsjippy_locations_settings", $this->settings);
    
        return $message;
    }

    /**
     * Location maps
     */
    public function getMaps($optionValue){
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
}