<?php
    
    /*
        Plugin Name: AppArtig Map
        Description: Plugin for Map with Linked Markers and Backend for editing Locations
        Version:     1.1.1
        Author:      AppArtig e.U.
        Author URI:  https://www.appartig.at
        License:     APPARTIG/AGB
        License URI: https://www.appartig.at/agb
        Text Domain: aamp
    */

    /******************************************************
    ** Install
    ******************************************************/

    register_activation_hook(__FILE__, function(){ });


    /******************************************************
    ** Uninstall
    ******************************************************/

    register_deactivation_hook(__FILE__, function(){ });
    

    /******************************************************
    ** Styles ans Scripts
    ******************************************************/

    add_action('wp_enqueue_scripts', function() {
        wp_enqueue_style('aamp_style_css', plugins_url('/css/style.css', __FILE__ ), null, '1.0.0');
        wp_enqueue_style('aamp_ol_css', plugins_url('/vendor/ol/v6_1_1/ol.css', __FILE__ ), null, '6.1.1');
        
        wp_enqueue_script('aamp_scripts_open_layers', plugins_url('/vendor/ol/v6_1_1/ol.js', __FILE__), array(), '6.1.1');
    });

    /******************************************************
    ** Menu
    ******************************************************/

    add_action('admin_menu', function() { });


    /******************************************************
    ** Add CPT for Locations
    ******************************************************/

    add_action('init', function (){
        
        register_post_type('aamp', array(
            'label'               => __('Orte', 'aamp'),
            'description'         => __('Orte', 'aamp'),
            'labels'              => array(
                'name'                => __('Orte auf der Map', 'aamp'),
                'singular_name'       => __('Ort', 'aamp'),
                'menu_name'           => __('Orte auf der Map', 'aamp'),
                'parent_item_colon'   => __('Übergeordneter Ort', 'aamp'),
                'all_items'           => __('Alle Orte', 'aamp'),
                'view_item'           => __('Location ansehen', 'aamp'),
                'add_new_item'        => __('Neuen Ort', 'aamp'),
                'add_new'             => __('Neuen Ort hinzufügen', 'aamp'),
                'edit_item'           => __('Ort bearbeiten', 'aamp'),
                'update_item'         => __('Ort verändern', 'aamp'),
                'search_items'        => __('Nach Ort suchen', 'aamp'),
                'not_found'           => __('Nicht gefunden', 'aamp'),
                'not_found_in_trash'  => __('Nicht im Papierkorb gefunden', 'aamp'),
            ),
            'supports'            => array('title'),
            'taxonomies'          => array(),
            'menu_icon'			  => 'dashicons-location',
            'hierarchical'        => false,
            'public'              => false,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'show_in_nav_menus'   => true,
            'show_in_admin_bar'   => false,
            'menu_position'       => 80,
            'can_export'          => true,
            'has_archive'         => true,
            'exclude_from_search' => true,
            'publicly_queryable'  => true
        ));

    });


    /******************************************************
    ** Add Meta Box
    ******************************************************/

    add_action('add_meta_boxes', function (){
        
        function aamp_meta_box__cpt_aamp_fields(){
            
            global $post;

            $post_meta = get_post_meta($post->ID);
            
            $lat = $post_meta['aamp_lat'][0] ? floatval($post_meta['aamp_lat'][0]) : "";
            $lng = $post_meta['aamp_lng'][0] ? floatval($post_meta['aamp_lng'][0]) : "";
            $color = $post_meta['aamp_color'][0] ? $post_meta['aamp_color'][0] : "";
            $url = $post_meta['aamp_url'][0] ? $post_meta['aamp_url'][0] : "";
?>
        
            <table class="widefat">
                <thead>
                    <tr>
                        <th>Eigenschaft</th>
                        <th>Wert</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <th><label for="aamp_lat">Latitude</label></th>
                        <td><input type="text" name="aamp_lat" id="aamp_lat" placeholder="Latitude" value="<?php echo $lat; ?>"/></td>
                    </tr>
                    <tr>
                        <th><label for="aamp_lng">Longitude</label></th>
                        <td><input type="text" name="aamp_lng" id="aamp_lng" placeholder="Longitude" value="<?php echo $lng; ?>"/></td>
                    </tr>
                    <tr>
                        <th><label for="aamp_color">Farbe</label></th>
                        <td><input type="color" name="aamp_color" id="aamp_color" placeholder="Farbe" value="<?php echo $color; ?>"/></td>
                    </tr>
                    <tr>
                        <th><label for="aamp_url">URL (deprecated)</label></th>
                        <td><input disabled type="text" name="aamp_url" id="aamp_url" placeholder="URL" value="<?php echo $url; ?>"/></td>
                    </tr>
                </tbody>
            </table>

<?php
            
        }

        add_meta_box(
            'aamp_meta_box__cpt_aamp_fields',
            __('Location', 'aamp'),
            'aamp_meta_box__cpt_aamp_fields',
            'aamp',
            'normal'
        );
    });


    /******************************************************
    ** Save Meta Box - Option Fields CPT
    ******************************************************/

    add_action('save_post', function ($post_id, $post){
        
        $keys = array("aamp_lat", "aamp_lng", "aamp_url", "aamp_color");
        
        if ($post->post_type == "aamp"){
            foreach ($_POST as $the_posted_key => $the_posted_value) {

                if (in_array($the_posted_key, $keys)) update_post_meta($post_id, $the_posted_key, $the_posted_value);
            }
        }

    }, 10, 2);


    /******************************************************
    ** Map Shortcode
    ******************************************************/

    add_shortcode('appartig-map', function ($atts = [], $content = null, $tag = ''){

        
        if (isset($atts["class"])) $class = explode(" ", $atts["class"]);
        $class[] = 'aamp';

?>

    <div class="<?php echo implode(' ', $class); ?>">
        <div id="aamp_map" class="aamp__map"></div>
    </div>
<?php

    });

    add_action('wp_footer', function() {
        
        $locations = get_posts(array(
            'posts_per_page'   => 1000,
            'offset'           => 0,
            'orderby'          => 'title',
            'order'            => 'DESC',
            'post_type'        => 'aamp',
            'post_status'      => 'publish',
        ));
?>

    <script type="text/javascript">
        
        var aamp_map;
        var aamp_bounds;
        var aamp_markers = [];
        var aamp_data = [
<?php 
            
        $min_lat = 999;
        $max_lat = -999;
        $min_lng = 999;
        $max_lng = -999;
        
        foreach($locations as $location){
                
            $meta = get_post_meta($location->ID);
            
            $title = $location->post_title;
            $content = $location->post_content;
            
            $lat 	= floatval($meta['aamp_lat'][0]);
            $lng 	= floatval($meta['aamp_lng'][0]);
            $url 	= $meta['aamp_url'][0];
            $color 	= $meta['aamp_color'][0];
            
            if ($lat < $min_lat) $min_lat = $lat;
            if ($lat > $max_lat) $max_lat = $lat;
            if ($lng < $min_lng) $min_lng = $lng;
            if ($lng > $max_lng) $max_lng = $lng;
            
            $center_lat = $min_lat + abs($max_lat - $min_lat) / 2;
            $center_lng = $min_lng + abs($max_lng - $min_lng) / 2;
        
            echo "{title: '$title', content: '$content', lat: $lat, lng: $lng, url: '$url', color: '$color'},"; 
            
        }

?>
        ];
        
        function aamp_init_map() {

            var aamp_map_zoom_level = 13;
            var aamp_marker_svg = '<svg width="512" height="512" version="1.1" xmlns="http://www.w3.org/2000/svg"> <path d="M256,36.082c-84.553,0-153.105,68.554-153.105,153.106c0,113.559,153.105,286.73,153.105,286.73   s153.106-173.172,153.106-286.73C409.106,104.636,340.552,36.082,256,36.082z M256,253.787c-35.682,0-64.6-28.917-64.6-64.6   s28.918-64.6,64.6-64.6s64.6,28.917,64.6,64.6S291.682,253.787,256,253.787z" fill="#ffffff"/><path d="M794,36.082c-84.553,0-153.105,68.554-153.105,153.106c0,113.559,153.105,286.73,153.105,286.73   s153.106-173.172,153.106-286.73C947.106,104.636,878.552,36.082,794,36.082z M794,253.787c-35.682,0-64.6-28.917-64.6-64.6   s28.918-64.6,64.6-64.6s64.6,28.917,64.6,64.6S829.682,253.787,794,253.787z" fill="#ffffff"/><path d="M-281,36.082c-84.553,0-153.105,68.554-153.105,153.106c0,113.559,153.105,286.73,153.105,286.73   s153.106-173.172,153.106-286.73C-127.894,104.636-196.448,36.082-281,36.082z M-281,253.787c-35.682,0-64.6-28.917-64.6-64.6   s28.918-64.6,64.6-64.6s64.6,28.917,64.6,64.6S-245.318,253.787-281,253.787z" fill="#ffffff"/> </svg>';
            var aamp_marker_icon = 'data:image/svg+xml;utf8,' + encodeURIComponent(aamp_marker_svg); //'<?php echo plugin_dir_url( __FILE__ ); ?>/img/default_marker.png';
            var aamp_center = [<?php echo $center_lng; ?>, <?php echo $center_lat; ?>];

            var aamp_map = new ol.Map({
                target: 'aamp_map',
                layers: [
                    new ol.layer.Tile({
                        source: new ol.source.OSM()
                    })
                ],
                view: new ol.View({
                    center: ol.proj.fromLonLat(aamp_center),
                    zoom: aamp_map_zoom_level
                })
            });
            
            for(var i=0;i<aamp_data.length;i++){

                aamp_markers[i] = new ol.layer.Vector({
                    source: new ol.source.Vector({
                        features: [
                            new ol.Feature({
                                geometry: new ol.geom.Point(
                                    ol.proj.fromLonLat([aamp_data[i].lng, aamp_data[i].lat])
                                ),
                                name: aamp_data[i].title,
                                content: aamp_data[i].content
                            })
                        ]
                    }),
                    style: new ol.style.Style({
                        image: new ol.style.Icon(/** @type {olx.style.IconOptions} */ ({
                            anchor: [0.5,1],
                            opacity: 1,
                            color: aamp_data[i].color,
                            crossOrigin: 'anonymous',
                            src: aamp_marker_icon,
                            scale: 0.18
                        }))
                    })
                });
                
                aamp_map.addLayer(aamp_markers[i]);
            }
        }

        aamp_init_map();
        
    </script>

<?php
        
    });

?>