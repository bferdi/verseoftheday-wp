<?php
/**
 * Plugin Name: Verse of the Day
 * Description: A plugin to display and schedule a verse of the day for your website via a shortcode.
 * Version: 1.0
 * Name: Ben Ferdinands
 */


// Create custom post type 'verse'
function custom_verse_plugin_register_post_type() {
    register_post_type('verse', array(
        'label' => 'Verses',
        'public' => true,
        'supports' => array('title', 'editor'),
    ));
}
add_action('init', 'custom_verse_plugin_register_post_type');

// Add custom field for verse date
function custom_verse_plugin_add_date_field() {
    add_meta_box('verse_date', 'Verse Date', 'custom_verse_plugin_date_callback', 'verse', 'side');
}
add_action('add_meta_boxes', 'custom_verse_plugin_add_date_field');

function custom_verse_plugin_date_callback($post) {
    $date = get_post_meta($post->ID, 'verse_date', true);
    echo '<label for="verse_date">Verse Date:</label>';
    echo '<input type="date" id="verse_date" name="verse_date" value="' . esc_attr($date) . '">';
}

function custom_verse_plugin_save_date_field($post_id) {
    if (isset($_POST['verse_date'])) {
        update_post_meta($post_id, 'verse_date', sanitize_text_field($_POST['verse_date']));
    }
}
add_action('save_post', 'custom_verse_plugin_save_date_field');

// Create shortcode to display verses
function custom_verse_plugin_shortcode($atts) {
    $attributes = shortcode_atts(array(
        'query_string' => '',
    ), $atts);

    $args = array(
        'post_type' => 'verse',
        'posts_per_page' => -1,
        'meta_key' => 'verse_date',
        'orderby' => 'meta_value',
        'order' => 'ASC',
        'meta_query' => array(
            array(
                'key' => 'verse_date',
                'value' => date('Y-m-d'),
                'compare' => '=',
                'type' => 'DATE',
            ),
        ),
    );

    if (!empty($attributes['query_string']) && isset($_GET[$attributes['query_string']])) {
        $args['meta_query'][] = array(
            'key' => 'your_query_string_key',
            'value' => 'your_query_string_value',
            'compare' => '=',
        );
    }

    $verses = new WP_Query($args);

    ob_start();
    if ($verses->have_posts()) {
        while ($verses->have_posts()) {
            $verses->the_post();
            echo '<div class="verse">';
            the_title('<h2>', '</h2>');
            the_content();
            echo '</div>';
        }
    } else {
        echo '<p>No verses found.</p>';
    }
    wp_reset_postdata();

    return ob_get_clean();
}
add_shortcode('custom_verse', 'custom_verse_plugin_shortcode');

// Add custom column to the verse list table
function custom_verse_plugin_add_custom_column($columns) {
    $columns['verse_date'] = 'Verse Date';
    return $columns;
}
add_filter('manage_verse_posts_columns', 'custom_verse_plugin_add_custom_column');

// Display custom data in the custom column
function custom_verse_plugin_custom_column_data($column, $post_id) {
    if ($column === 'verse_date') {
        $date = get_post_meta($post_id, 'verse_date', true);
        echo $date;
    }
}
add_action('manage_verse_posts_custom_column', 'custom_verse_plugin_custom_column_data', 10, 2);


// Add settings submenu under "Verses" menu
function custom_verse_plugin_settings_submenu() {
    add_submenu_page(
        'edit.php?post_type=verse',  // Slug of the "Verses" menu
        'Custom Verse Plugin Settings',
        'Settings',
        'manage_options',
        'custom-verse-settings',      // Use 'admin-verse-settings.php' without the '.php' extension
        'custom_verse_plugin_render_settings'  // This is the function that renders the settings page
    );
}
add_action('admin_menu', 'custom_verse_plugin_settings_submenu');

// Render the settings page
function custom_verse_plugin_render_settings() {
    $shortcode = '[custom_verse]'; // Replace with your actual shortcode

    ?>
    <div class="wrap">
        <h2>Custom Verse Plugin Settings</h2>
        <p>Shortcode: <code>[custom_verse]</code></p>
        <button id="copy-shortcode" class="button button-primary">Copy Shortcode</button>
        <p class="description">Click the button to copy the shortcode to your clipboard.</p>
    </div>
    <script>
       document.addEventListener('DOMContentLoaded', function () {
            var copyButton = document.getElementById('copy-shortcode');
            copyButton.addEventListener('click', function () {
                var textarea = document.createElement('textarea');
                textarea.value = '<?php echo esc_js($shortcode); ?>';
                document.body.appendChild(textarea);
                textarea.select();
                document.execCommand('copy');
                document.body.removeChild(textarea);
                alert('Shortcode copied to clipboard!');
            });
        });
    </script>
<?php
	}

// Register settings and fields
function custom_verse_plugin_register_settings() {
    register_setting('custom-verse-plugin-settings', 'custom_verse_settings', 'custom_verse_plugin_sanitize_settings');

    add_settings_section(
        'custom-verse-plugin-general',
        'General Settings',
        'custom_verse_plugin_section_callback',
        'custom-verse-plugin-settings'
    );

    add_settings_field(
        'custom-verse-timezone',
        'Timezone',
        'custom_verse_plugin_timezone_field_callback',
        'custom-verse-plugin-settings',
        'custom-verse-plugin-general'
    );

    add_settings_field(
        'custom-verse-query-string',
        'Query String',
        'custom_verse_plugin_query_string_field_callback',
        'custom-verse-plugin-settings',
        'custom-verse-plugin-general'
    );
}
add_action('admin_init', 'custom_verse_plugin_register_settings');

// Sanitize settings
//function custom_verse_plugin_sanitize_settings($input) {
  //  $sanitized_input = array();

   // if (isset($input['timezone'])) {
    //    $sanitized_input['timezone'] = sanitize_text_field($input['timezone']);
   // }

  //  if (isset($input['query_string'])) {
  //      $sanitized_input['query_string'] = sanitize_text_field($input['query_string']);
 //   }

 //   return $sanitized_input;
//}

// Section callback
//function custom_verse_plugin_section_callback() {
  //  echo '<p>Configure general settings for the Custom Verse Plugin.</p>';
//}

// Timezone field callback
//function custom_verse_plugin_timezone_field_callback() {
 //   $options = get_option('custom_verse_settings');
 //   $timezone = isset($options['timezone']) ? $options['timezone'] : '';

  //  echo '<input type="text" name="custom_verse_settings[timezone]" value="' . esc_attr($timezone) . '" class="regular-text">';
  //  echo '<p class="description">Enter the timezone for verse dates (e.g., America/New_York).</p>';
//}

// Query string field callback
//function custom_verse_plugin_query_string_field_callback() {
 //   $options = get_option('custom_verse_settings');
 //   $query_string = isset($options['query_string']) ? $options['query_string'] : '';

  //  echo '<input type="text" name="custom_verse_settings[query_string]" value="' . esc_attr($query_string) . '" class="regular-text">';
  //  echo '<p class="description">Enter the query string parameter for hiding verses (e.g., my_custom_query).</p>';
//}
