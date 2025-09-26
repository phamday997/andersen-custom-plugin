<?php
/*
Plugin Name: Andersen Modal
Description: Ajoute un modal personnalisable dans la sidebar de WordPress.
Version: 1.0
*/


// Sécurité
if (!defined('ABSPATH')) exit;

// Inclure le formulaire dans l'administration
add_action('admin_menu', function () {
    add_menu_page(
        'Sidebar Form',
        'Andersen Modal',
        'manage_options',
        'custom-sidebar-form',
        'render_sidebar_form_page',
        'dashicons-admin-generic',
        20
    );
});

function render_sidebar_form_page() {
    ?>
    <div class="wrap">
        <h1>Modal Configuration</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('sidebar_form_settings');
            do_settings_sections('custom-sidebar-form');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

add_action('admin_init', function () {
    $languages = ['fr' => 'Français', 'en' => 'English', 'nl' => 'Nederlands'];
    
    foreach ($languages as $lang_code => $lang_name) {
        register_setting('sidebar_form_settings', "sidebar_form_title_{$lang_code}");
        register_setting('sidebar_form_settings', "sidebar_form_description_{$lang_code}");
        register_setting('sidebar_form_settings', "sidebar_form_button_text_{$lang_code}");
        register_setting('sidebar_form_settings', "sidebar_form_image_{$lang_code}");
    }
    register_setting('sidebar_form_settings', 'sidebar_form_button_action');
    register_setting('sidebar_form_settings', 'sidebar_form_button_url');
    register_setting('sidebar_form_settings', 'sidebar_form_show_modal');

    add_settings_section(
        'sidebar_form_main_section',
        'Form Settings',
        null,
        'custom-sidebar-form'
    );

    // Champs pour chaque langue
    foreach ($languages as $lang_code => $lang_name) {
        add_settings_section(
            "sidebar_form_{$lang_code}_section",
            "Textes en {$lang_name}",
            null,
            'custom-sidebar-form'
        );

        add_settings_field(
            "sidebar_form_title_{$lang_code}",
            "Title ({$lang_name})",
            function () use ($lang_code) {
                $value = get_option("sidebar_form_title_{$lang_code}", '');
                echo '<input type="text" name="sidebar_form_title_' . $lang_code . '" value="' . esc_attr($value) . '" class="regular-text">';
            },
            'custom-sidebar-form',
            "sidebar_form_{$lang_code}_section"
        );

        add_settings_field(
            "sidebar_form_description_{$lang_code}",
            "Description ({$lang_name})",
            function () use ($lang_code) {
                $value = get_option("sidebar_form_description_{$lang_code}", '');
                echo '<textarea name="sidebar_form_description_' . $lang_code . '" class="large-text" rows="5">' . esc_textarea($value) . '</textarea>';
            },
            'custom-sidebar-form',
            "sidebar_form_{$lang_code}_section"
        );

        add_settings_field(
            "sidebar_form_button_text_{$lang_code}",
            "Button Text ({$lang_name})",
            function () use ($lang_code) {
                $value = get_option("sidebar_form_button_text_{$lang_code}", '');
                echo '<input type="text" name="sidebar_form_button_text_' . $lang_code . '" value="' . esc_attr($value) . '" class="regular-text">';
            },
            'custom-sidebar-form',
            "sidebar_form_{$lang_code}_section"
        );

        add_settings_field(
            "sidebar_form_image_{$lang_code}",
            "Image ({$lang_name})",
            function () use ($lang_code) {
                $image_id = get_option("sidebar_form_image_{$lang_code}", '');
                $image_url = $image_id ? wp_get_attachment_url($image_id) : '';
                ?>
                <div class="image-upload-wrapper">
                    <input type="hidden" name="sidebar_form_image_<?php echo $lang_code; ?>" 
                           id="sidebar_form_image_<?php echo $lang_code; ?>" 
                           value="<?php echo esc_attr($image_id); ?>">
                    
                    <div class="preview-wrapper" style="margin-bottom: 10px;">
                        <?php if ($image_url): ?>
                            <img src="<?php echo esc_url($image_url); ?>" 
                                 style="max-width: 200px; display: block; margin-bottom: 5px;">
                        <?php endif; ?>
                    </div>
                    
                    <input type="button" class="button upload_image_button" 
                           data-lang="<?php echo $lang_code; ?>"
                           value="Choisir une image">
                    
                    <?php if ($image_id): ?>
                        <input type="button" class="button remove_image_button" 
                               data-lang="<?php echo $lang_code; ?>"
                               value="Supprimer l'image">
                    <?php endif; ?>
                </div>
                <?php
            },
            'custom-sidebar-form',
            "sidebar_form_{$lang_code}_section"
        );
    }

    add_settings_field(
        'sidebar_form_button_action',
        'Button Action',
        function () {
            $value = get_option('sidebar_form_button_action', 'none');
            $url = get_option('sidebar_form_button_url', '');
            ?>
            <select name="sidebar_form_button_action" id="sidebar_form_button_action">
                <option value="none" <?php selected($value, 'none'); ?>>Don't display</option>
                <option value="close" <?php selected($value, 'close'); ?>>Close</option>
                <option value="link" <?php selected($value, 'link'); ?>>Link</option>
            </select>
            <div id="button_url_field" style="margin-top: 10px; <?php echo $value !== 'link' ? 'display: none;' : ''; ?>">
                <input type="url" name="sidebar_form_button_url" value="<?php echo esc_url($url); ?>" class="regular-text" placeholder="https://...">
            </div>
            <script>
                jQuery(document).ready(function($) {
                    $('#sidebar_form_button_action').on('change', function() {
                        $('#button_url_field').toggle(this.value === 'link');
                    });
                });
            </script>
            <?php
        },
        'custom-sidebar-form',
        'sidebar_form_main_section'
    );

    add_settings_field(
        'sidebar_form_show_modal',
        'Show Modal',
        function () {
            $value = get_option('sidebar_form_show_modal', '1');
            echo '<input type="checkbox" name="sidebar_form_show_modal" value="1" ' . checked(1, $value, false) . '>';
        },
        'custom-sidebar-form',
        'sidebar_form_main_section'
    );
});

add_action('admin_enqueue_scripts', function ($hook) {
    if ($hook !== 'toplevel_page_custom-sidebar-form') return;
    
    wp_enqueue_media();
    wp_add_inline_script('jquery', '
        jQuery(document).ready(function($) {
            $(document).on("click", ".upload_image_button", function(e) {
                e.preventDefault();
                var langCode = $(this).data("lang");
                var button = $(this);
                var wrapper = button.closest(".image-upload-wrapper");
                
                var frame = wp.media({
                    title: "Sélectionner une image",
                    multiple: false
                });

                frame.on("select", function() {
                    var attachment = frame.state().get("selection").first().toJSON();
                    $("#sidebar_form_image_" + langCode).val(attachment.id);
                    wrapper.find(".preview-wrapper").html(
                        "<img src=\"" + attachment.url + "\" style=\"max-width: 200px; display: block; margin-bottom: 5px;\">"
                    );
                    button.after(
                        "<input type=\"button\" class=\"button remove_image_button\" data-lang=\"" + 
                        langCode + "\" value=\"Supprimer l\'image\">"
                    );
                });

                frame.open();
            });

            $(document).on("click", ".remove_image_button", function(e) {
                e.preventDefault();
                var langCode = $(this).data("lang");
                var wrapper = $(this).closest(".image-upload-wrapper");
                
                $("#sidebar_form_image_" + langCode).val("");
                wrapper.find(".preview-wrapper").empty();
                $(this).remove();
            });
        });
    ');
});

add_action('rest_api_init', function () {
    register_rest_route('custom/v1', '/sidebar-form', [
        'methods' => 'GET',
        'callback' => function () {
            $show_modal = get_option('sidebar_form_show_modal', '1');
            
            if ($show_modal !== '1') {
                return ['show_modal' => false];
            }

            $languages = ['fr', 'en', 'nl'];
            $texts = [];

            foreach ($languages as $lang) {
                $image_id = get_option("sidebar_form_image_{$lang}", '');
                $texts[$lang] = [
                    'title' => get_option("sidebar_form_title_{$lang}", ''),
                    'description' => get_option("sidebar_form_description_{$lang}", ''),
                    'button_text' => get_option("sidebar_form_button_text_{$lang}", ''),
                    'image_url' => $image_id ? wp_get_attachment_url($image_id) : '',
                ];
            }

            return [
                'show_modal' => true,
                'texts' => $texts,
                'button_action' => get_option('sidebar_form_button_action', ''),
                'button_url' => get_option('sidebar_form_button_url', ''),
            ];
        },
        'permission_callback' => '__return_true',
    ]);
});
