<?php
	
	/*
		Plugin Name: AppArtig Map
		Description: Plugin for Map with Linked Markers and Backend for editing Locations
		Version:     1.0.0
		Author:      AppArtig e.U.
		Author URI:  https://www.appartig.at
		License:     APPARTIG/AGB
		License URI: https://www.appartig.at/agb
		Text Domain: aamp
	*/


	/******************************************************
	** Konstanten
	******************************************************/

	define ('AAMP_MAP_API_KEY', 'AIzaSyCsw2iuIsv3Og5L6p4Jyf97fpm5Z2uq-eQ');


	/******************************************************
	** Install
	******************************************************/

	function aamp_install() {
		
	}

	register_activation_hook(__FILE__, 'aamp_install');


	/******************************************************
	** Uninstall
	******************************************************/

	function aamp_uninstall() {
		
	}

	register_deactivation_hook(__FILE__, 'aamp_uninstall');
	

	/******************************************************
	** Styles ans Scripts
	******************************************************/

	function aamp_style_scripts() {
		wp_register_style('aamp_style_css', plugins_url('/css/style.css', __FILE__ ), false, '1.0.0');
		wp_enqueue_style('aamp_style_css');
		
		wp_enqueue_script('aamp_scripts_app', plugin_dir_url(__FILE__) . '/js/app.min.js', array(), '1.0.0', true);
        wp_enqueue_script('aamp_scripts_googlemaps', 'https://maps.googleapis.com/maps/api/js?key=' . AAMP_MAP_API_KEY . '&callback=aamp_init_map', array(), '1.0.0', true );
	}

	add_action('wp_enqueue_scripts', 'aamp_style_scripts');

	/******************************************************
	** Menu
	******************************************************/

	function aamp_user_menu (){
		
	}

    add_action('admin_menu', 'aamp_user_menu');


	/******************************************************
	** Add CPT for Locations
	******************************************************/

	function aamp_custom_post_type(){
		
		register_post_type('aamp', array(
			'label'               => __('Locations', 'aamp'),
			'description'         => __('Locations', 'aamp'),
			'labels'              => array(
				'name'                => __('Locations', 'aamp'),
				'singular_name'       => __('Location', 'aamp'),
				'menu_name'           => __('Locations', 'aamp'),
				'parent_item_colon'   => __('Übergeordnete Location', 'aamp'),
				'all_items'           => __('Alle Locations', 'aamp'),
				'view_item'           => __('Location ansehen', 'aamp'),
				'add_new_item'        => __('Neue Location', 'aamp'),
				'add_new'             => __('Neue Location hinzufügen', 'aamp'),
				'edit_item'           => __('Location bearbeiten', 'aamp'),
				'update_item'         => __('Location verändern', 'aamp'),
				'search_items'        => __('Nach Location Suchen', 'aamp'),
				'not_found'           => __('Nicht gefunden', 'aamp'),
				'not_found_in_trash'  => __('Nicht im Papierkorb gefunden', 'aamp'),
			),
			'supports'            => array('title', 'editor'),
			'taxonomies'          => array(),
			'menu_icon'			  => 'dashicons-location',
			'hierarchical'        => false,
			'public'              => true,
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
	}

	add_action('init', 'aamp_custom_post_type');


	/******************************************************
	** Add Meta Box
	******************************************************/

	function aamp_meta_boxes(){
		
		function aamp_meta_box__cpt_aamp_fields(){
			
			global $post;

			$post_meta = get_post_meta($post->ID);
			
			$lat = $post_meta['aamp_lat'][0] ? floatval($post_meta['aamp_lat'][0]) : "";
			$lng = $post_meta['aamp_lng'][0] ? floatval($post_meta['aamp_lng'][0]) : "";
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
						<th><label for="aamp_lat">Longitude</label></th>
						<td><input type="text" name="aamp_lng" id="aamp_lng" placeholder="Longitude" value="<?php echo $lng; ?>"/></td>
					</tr>
					<tr>
						<th><label for="aamp_lat">URL</label></th>
						<td><input type="text" name="aamp_url" id="aamp_url" placeholder="URL" value="<?php echo $url; ?>"/></td>
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
	}
	add_action('add_meta_boxes', 'aamp_meta_boxes');


	/******************************************************
	** Save Meta Box - Option Fields CPT
	******************************************************/

	function aamp_save_meta($post_id, $post){
		
		$keys = array("aamp_lat", "aamp_lng", "aamp_url");
		
		if ($post->post_type == "aamp"){
			foreach ($_POST as $the_posted_key => $the_posted_value) {

				if (in_array($the_posted_key, $keys)) update_post_meta($post_id, $the_posted_key, $the_posted_value);
			}
		}
	}

	add_action('save_post', 'aamp_save_meta' , 10, 2);


	/******************************************************
	** Map Shortcode
	******************************************************/

	function aamp_map_shortcode(){

?>

	<div class="aamp">
		<div id="aamp_map" class="aamp__map">
			
		</div>
	</div>
<?php
		
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
			
			$lat = floatval($meta['aamp_lat'][0]);
			$lng = floatval($meta['aamp_lng'][0]);
			$url = $meta['aamp_url'][0];
			
			if ($lat < $min_lat) $min_lat = $lat;
			if ($lat > $max_lat) $max_lat = $lat;
			if ($lng < $min_lng) $min_lng = $lng;
			if ($lng > $max_lng) $max_lng = $lng;
			
			$center_lat = $min_lat + abs($max_lat - $min_lat) / 2;
			$center_lng = $min_lng + abs($max_lng - $min_lng) / 2;
		
			echo "{title: '$title', content: '$content', lat: $lat, lng: $lng, url: '$url'},"; 
			
		}

?>
		];
		
		function aamp_init_map() {
	
			var aamp_center = {lat: <?php echo $center_lat; ?>, lng: <?php echo $center_lng; ?>};

			aamp_map = new google.maps.Map(document.getElementById('aamp_map'), {
				zoom: 1,
				center: aamp_center
			});
			
			aamp_bounds = new google.maps.LatLngBounds();
			
			for(var i=0;i<aamp_data.length;i++){

				aamp_markers[i] = new google.maps.Marker({
					position: aamp_data[i],
					map: aamp_map
				});
				
				aamp_markers[i].url = aamp_data[i].url;
				
				aamp_markers[i].addListener('click', function() {
					console.log(this.url);
				});
				
				aamp_bounds.extend(aamp_markers[i].getPosition());
			}

			aamp_map.fitBounds(aamp_bounds);
		}
		
	</script>

<?php
		
	}

	add_shortcode('aamp_map', 'aamp_map_shortcode');

?>