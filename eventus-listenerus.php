<?php 
/*
Plugin Name: Eventus Listenerus
Plugin URI: 
*/

if ( ! defined( 'ABSPATH' ) ) {
	die; // Oh, snap
}

const EL_POST_TYPE = 'event';

add_action( 'init', 'el_eventus_custom_postype' );
add_action( 'manage_' . EL_POST_TYPE . '_posts_custom_column', 'el_columns_content', 10, 2 );
add_action( 'add_meta_boxes', 'el_add_meta_boxes' );
add_action( 'save_post', 'el_save_meta_boxes' );
add_action( 'admin_enqueue_scripts', 'el_enqueue_datepicker' );

add_filter( 'manage_edit-' . EL_POST_TYPE . '_columns', 'el_custom_columns_head' );
add_filter( 'the_content', 'el_event_content' );

function el_eventus_custom_postype() {
	$has_archive = true;
	$page_attributes = 'page-attributes';
	$show_in_menu = true;
	$labels = array(
		'name' => esc_attr__( 'Events', 'eventus-listenerus' ),
		'singular_name' => esc_attr__( 'Event', 'eventus-listenerus' ),
		'all_items' => esc_attr__( 'All Events', 'eventus-listenerus' ),
		'add_new_item' => esc_attr__( 'Add New Event', 'eventus-listenerus' ),
		'add_new' => esc_attr__( 'New Event', 'eventus-listenerus' ),
		'new_item' => esc_attr__( 'New Event', 'eventus-listenerus' ),
		'edit_item' => esc_attr__( 'Edit Event', 'eventus-listenerus' ),
		'view_item' => esc_attr__( 'View Event', 'eventus-listenerus' ),
		'search_items' => esc_attr__( 'Search Events', 'eventus-listenerus' ),
		'not_found' => esc_attr__( 'No events found', 'eventus-listenerus' ),
		'not_found_in_trash' => esc_attr__( 'No events found in Trash', 'eventus-listenerus' )
	);
	$args = array(
		'labels' => $labels,
		'menu_icon' => 'dashicons-calendar-alt',
		'public' => true,
		'can_export' => true,
		'show_in_nav_menus' => $show_in_menu,
		'has_archive' => $has_archive,
		'show_ui' => true,
		'show_in_rest' => true,
		'capability_type' => 'post',
		'taxonomies' => array( 'event_cat' ),
		'rewrite' => array( 'slug' => esc_attr(EL_POST_TYPE) ),
 		'supports' => array( 'title', 'thumbnail', $page_attributes, 'editor' )
	);	
	register_post_type( 'event', $args );
}

function el_add_meta_boxes() {
	add_meta_box( 'el-event-info-metabox', __( 'Event Specifics', 'eventus-listenerus' ),
		'el_render_event_info_metabox' , EL_POST_TYPE, 'side', 'core' );
}

function el_custom_columns_head( $defaults ) {
	unset( $defaults['date'] );

	$defaults['event_venue']         = __( 'Venue', 'eventus-listenerus', 'text_domain');
	$defaults['event_start_date']    = __( 'Start Date', 'eventus-listenerus', 'text_domain' );
	$defaults['event_end_date']      = __( 'End Date', 'eventus-listenerus', 'text_domain' );
	$defaults['event_url']           = __( 'Event Website', 'eventus-listenerus', 'text_domain' );
	$defaults['google_calendar_url'] = __( 'Google Calendar Link', 'eventus-listenerus', 'text_domain' );
	$defaults['google-maps-url']     = __( 'Google Maps Link', 'eventus-listenerus', 'text_domain' );

	return $defaults;
}

function el_columns_content( $column_name, $post_id ) {
	if ( 'event_start_date' == $column_name ) {
		$start_date = get_post_meta( $post_id, 'el-event-start-date', true );
		echo date_i18n( get_option( 'date_format' ), $start_date );
	}

	if ( 'event_end_date' == $column_name ) {
		$end_date = get_post_meta( $post_id, 'el-event-end-date', true );
		echo date_i18n( get_option( 'date_format' ), $end_date );
	}

	if ( 'event_venue' == $column_name ) {
		$venue = get_post_meta( $post_id, 'el-event-venue', true );
		echo esc_html( $venue );
	}

	if ( 'event_url' == $column_name ) {
		$url = get_post_meta( $post_id, 'el-event-url', true);
		echo esc_html( $url );
	}

	if( 'google_calendar_url' == $column_name ) {
		$calendar_url = get_post_meta( $post_id, 'el-google-calendar-url', true);
		echo esc_html( $calendar_url );
	}

	if( 'google-maps-url' == $column_name ) {
		$maps_url = get_post_meta( $post_id, 'el-google-maps-url', true);
		echo esc_html( $maps_url );
	}
}

function el_render_event_info_metabox( $post ) {
	//generate a nonce field
	wp_nonce_field( 'eventus-listenerus-list', '_event_nonce' );
	//get previously saved meta values (if any)
	$event_start_date = get_post_meta( $post->ID, 'el-event-start-date', true );
	$event_end_date   = get_post_meta( $post->ID, 'el-event-end-date', true );
	$event_venue      = get_post_meta( $post->ID, 'el-event-venue', true );
	$event_url        = get_post_meta( $post->ID, 'el-event-url', true );
	$calendar_url     = get_post_meta( $post->ID, 'el-google-calendar-url', true );
	$maps_url         = get_post_meta( $post->ID, 'el-google-maps-url', true );
	//if there is previously saved value then retrieve it, else set it to the current time
	$event_start_date = ! empty( $event_start_date ) ? $event_start_date : time();
	//we assume that if the end date is not present, event ends on the same day
	$event_end_date = ! empty( $event_end_date ) ? $event_end_date : $event_start_date;

	// set dateformat to match datepicker
	$dateformat = get_option('date_format');
	if ($dateformat == 'j F Y' || $dateformat == 'd/m/Y' || $dateformat == 'd-m-Y') {
		$dateformat = 'd-m-Y';
	} else {
		$dateformat = 'Y-m-d';
	}

	?>
    <p>
        <label for="el-event-start-date"><?php _e( 'Event Start Date:', 'eventus-listenerus' ); ?></label>
        <input type="date" id="el-event-start-date" name="el-event-start-date"
			   class="widefat" placeholder="<?php esc_attr_e( 'Use datepicker', 'eventus-listenerus' ); ?>"
			   value="<?php echo date_i18n( $dateformat, esc_attr( $event_start_date ) ); ?>" />
    </p>
    <p>
        <label for="el-event-end-date"><?php _e( 'Event End Date:', 'eventus-listenerus' ); ?></label>
        <input type="date" id="el-event-end-date" name="el-event-end-date"
			   class="widefat" placeholder="<?php esc_attr_e( 'Use datepicker', 'eventus-listenerus' ); ?>"
			   value="<?php echo date_i18n( $dateformat, esc_attr( $event_end_date ) ); ?>" />
    </p>
    <p>
        <label for="el-event-venue"><?php _e( 'Event Venue:', 'eventus-listenerus' ); ?></label>
        <input type="text" id="el-event-venue" name="el-event-venue" class="widefat"
               value="<?php echo $event_venue; ?>" placeholder="eg. Times Square">
    </p>
	<p>
		<label for="el-event-url"><?php _e( 'Event URL:', 'eventus-listenerus' ); ?></label>
        <input type="url" id="el-event-url" name="el-event-url" class="widefat"
               value="<?php echo $event_url; ?>" placeholder="eg. https://wordpress.com/">
	</p>
	<p>
		<label for="el-google-maps-url"><?php _e( 'Google Maps URL:', 'eventus-listenerus' ); ?></label>
        <input type="text" id="el-google-maps-url" name="el-google-maps-url" class="widefat"
               value="<?php echo $maps_url; ?>" placeholder="eg. iframe">
	</p>
	<p>
		<label for="el-google-calendar-url"><?php _e( 'Google Calendar URL:', 'eventus-listenerus' ); ?></label>
        <input type="text" id="el-google-calendar-url" name="el-google-calendar-url" class="widefat"
               value="<?php echo $calendar_url; ?>" placeholder="eg. iframe">
	</p>


	<?php	
}

function el_save_meta_boxes( $post_id ) {
	//checking for the 'save' status
	$is_autosave    = wp_is_post_autosave( $post_id );
	$is_revision    = wp_is_post_revision( $post_id );
	$is_valid_nonce = isset( $_POST['_event_nonce'] ) && wp_verify_nonce( $_POST['_event_nonce'], 'eventus-listenerus-list' );

	//exit depending on the save status or if the nonce is not valid
	if ( $is_autosave || $is_revision || ! $is_valid_nonce ) {
		return;
	}

	//checking for the values and performing necessary actions
	if ( isset( $_POST['el-event-start-date'] ) ) {
		update_post_meta( $post_id, 'el-event-start-date', sanitize_text_field( strtotime( $_POST['el-event-start-date'] ) ) );
	}

	if ( isset( $_POST['el-event-end-date'] ) ) {
		update_post_meta( $post_id, 'el-event-end-date', sanitize_text_field( strtotime( $_POST['el-event-end-date'] ) ) ) ;
	}

	if ( isset( $_POST['el-event-venue'] ) ) {
		update_post_meta( $post_id, 'el-event-venue', sanitize_text_field( $_POST['el-event-venue'] ) ) ;
	}
	
	if ( isset( $_POST['el-event-url'] ) ) {
		update_post_meta( $post_id, 'el-event-url', esc_url_raw( $_POST['el-event-url'] ) );
	}

	if ( isset( $_POST['el-google-calendar-url'] ) ) {
		update_post_meta( $post_id, 'el-google-calendar-url', esc_html( $_POST['el-google-calendar-url'] ) );
	}

	if( isset( $_POST['el-google-maps-url'] ) ) {
		update_post_meta( $post_id, 'el-google-maps-url', esc_html( $_POST['el-google-maps-url'] ) );
	}
}


function el_enqueue_datepicker() {
	global $wp_locale;
	global $post_type;

	if( 'event' != $post_type )
	return;
	//jQuery UI date picker file
	wp_enqueue_script( 'datepicker_script', plugins_url( '/js/datepicker.js' , __FILE__ ), array('jquery', 'jquery-ui-datepicker') );
    //jQuery UI theme css file
    wp_enqueue_style('e2b-admin-ui-css','http://ajax.googleapis.com/ajax/libs/jqueryui/1.9.0/themes/base/jquery-ui.css',false,"1.9.0",false);
}

// adding all the content to the event post as html
function el_event_content( $content ) {
	if ( is_singular( 'event' ) || is_post_type_archive( 'event' ) ) {

		$event_start_date 			= get_post_meta( get_the_ID(), 'el-event-start-date', true );
		$event_end_date   			= get_post_meta( get_the_ID(), 'el-event-end-date', true );
		$event_venue      			= get_post_meta( get_the_ID(), 'el-event-venue', true );
		$event_url        			= get_post_meta( get_the_ID(), 'el-event-url', true);
		$event_google_calendar_url  = get_post_meta( get_the_ID(), 'el-google-calendar-url', true);
		$event_google_maps_url      = get_post_meta( get_the_ID(), 'el-google-maps-url', true);

		// Google calendar button right on top looks nice
		if($event_google_calendar_url != ''){
			$event =  html_entity_decode( $event_google_calendar_url );
			$event .= '<p><strong>' . __( 'Event Start Date:', 'eventus-listenerus' ) . '</strong><br>'. date_i18n( get_option( 'date_format' ), $event_start_date ) . '</p>';
		} else {
			$event = '<p><strong>' . __( 'Event Start Date:', 'eventus-listenerus' ) . '</strong><br>'. date_i18n( get_option( 'date_format' ), $event_start_date ) . '</p>';
		}
		$event .= '<p><strong>' . __( 'Event End Date:', 'eventus-listenerus' ) . '</strong><br>'. date_i18n( get_option( 'date_format' ), $event_end_date ) . '</p>';
		$event .= '<p><strong>' . __( 'Event URL:', 'eventus-listenerus' ) . '</strong><br><a href='. esc_url_raw( $event_url ) . '> For more info </a></p>';
		$event .= '<p><strong>' . __( 'Event Venue:', 'eventus-listenerus' ) . '</strong><br>'. sanitize_text_field( $event_venue ) . '</p>';
		if($event_google_maps_url != ''){
			$event .=  html_entity_decode( $event_google_maps_url );
		}

		$content = $event . $content;
	}

	return $content;
}