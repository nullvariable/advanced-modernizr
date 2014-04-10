<?php

class am_settings
{
    const opt_group = "advanced_modernizr_option_group";
    const setting_section = "advanced_modernizr_settings_section";
    const setting_section_script = "advanced_modernizr_script_settings_section";
    const page = 'advanced-modernizr';
    const hide_admin_option = 'advanced_modernizr_hide_admin_scripts';
    public $total_scripts = 0;
    private $script_types = array();
    function __construct()
    {
        $this->script_types = array(
            "wp-admin"           => "admin",
            "wp-includes"           => "core",
            "wp-content/plugins"    => "plugin",
            "wp-content/themes"     => "theme",
            ""                      => "other",
        );
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
        add_filter('set-screen-option', array($this, 'save_screen_opt'), 10, 3);
        add_action('update_option_'.AM_SLUG, array($this, 'clear_cache'));

        $opts = get_option(AM_SLUG, array());
        $this->settings = array(
            self::setting_section => array(
                'lazy_load_for_logged_in' => array(
                    'title' => 'Lazy Load',
                    'label' => 'All front end pages',
                    'helptext' => "Lazy load is on for all anonymous users, activate for all front end pages?",
                    'type' => "checkbox",
                    'value' => (isset($opts['lazy_load_for_logged_in'])) ? $opts['lazy_load_for_logged_in'] : false,
                ),
                'lazy_load_scripts' => array(
                    'title' => "Scripts to Lazy Load",
                    'type' => 'scripts',
                    'value' => (isset($opts['lazy_load_scripts'])) ? $opts['lazy_load_scripts'] : false,
                ),
            ),
            self::setting_section_script => array(
                'load_via_cdn' => array(
                    'title' => 'Load Modernizr ' . AM_SCRIPT_VERSION,
                    'label' => '',
                    'helptext' => 'Select how Modernizr should be loaded',
                    'type' => 'loadm',
                    'value' => (isset($opts['load_via_cdn'])) ? $opts['load_via_cdn'] : false,
                ),
                'load_via_cdn_custom' => array(
                    'title' => '',
                    'label' => '',
                    'helptext' => 'Upload a file, or enter a cdn url for a custom version.',
                    'type' => 'custom_m',
                    'value' => (isset($opts['load_via_cdn_custom'])) ? $opts['load_via_cdn_custom'] : false,
                )
            ),
        );
//        add_filter('hidden_meta_boxes',  array($this, 'debug'), 9, 3);
    }
    function debug($hidden, $screen, $use_defaults) {
        $args = func_get_args();
        d(get_defined_vars());
        return $hidden;
    }
    /*
     * Hooked to updating our option, we'll clear our transients whenever that is saved so they're rebuilt.
     */
    function clear_cache() {
        $t = array(
            AM_SLUG.'_lazyloader',
            AM_SLUG.'_force_load',
        );
        foreach ($t as $d) { delete_transient($d); }
    }
    function admin_menu()
    {
        $page = add_submenu_page("tools.php", "Advanced Modernizr", "Advanced Modernizr", 'manage_options', self::page, array($this, 'admin_page'));
        $this->screen = $page;
        add_action('load-' . $page, array($this, 'pageload'));
    }

    function pageload()
    {
        /*define('SCRIPT_DEBUG', true);
        define('CONCATENATE_SCRIPTS', false);*/
        add_action('admin_enqueue_scripts',
            function () {
                wp_enqueue_media();
/*                wp_deregister_script('common');
                wp_register_script('common', '/wp-admin/js/common.js', array('jquery', 'hoverIntent', 'utils'), false, false);*/
                /*wp_enqueue_script('common');*/
                wp_enqueue_script(AM_SLUG . '-admin-js');
                wp_enqueue_style(AM_SLUG . '-admin-css');
                wp_localize_script(AM_SLUG . '-admin-js', AM_SLUG . '_scripts_per_screen', am_settings::user_scripts_per_page());
            }
        );
        $screen = get_current_screen();
        if (is_object($screen)) {
            $screen->add_help_tab(array(
                'id' => __(AM_SLUG . '-help-tab', AM_SLUG),
                'title' => __('How it Works', AM_SLUG),
                'content' => __("Check the boxes below to enable lazy loading of those scripts if they
                    are enqueued. Scripts will only be loaded if they are enqueued on the current page. You can force a
                    script to be loaded on any page by selecting the \"Always Load\" box. You can force a script to be
                    removed from loading site wide by using the \"Never Load\" option; it will be unqueued while this
                    plugin is active. Scripts that are not loaded or registered with WordPress properly can not be
                    controlled from this screen.", AM_SLUG)
            ));
            add_screen_option('per_page', array(
                'label' => __("Scripts per screen", AM_SLUG),
                'default' => 10,
                'option' => AM_SLUG . '_scripts_per_screen'
            ));
            add_filter('manage_' . $screen->id . '_columns', array($this, 'hack_table_column'));
        }

    }

    //http://pippinsplugins.com/add-screen-options-tab-to-your-wordpress-plugin/
    function user_scripts_per_page() {
        $user = get_current_user_id();
        $screen = get_current_screen();
        $screen_option = $screen->get_option('per_page', 'option');
        $per_page = get_user_meta($user, $screen_option, true);
        if (empty ($per_page) || $per_page < 1) {
            // get the default value if none is set
            $per_page = $screen->get_option('per_page', 'default');
        }
        return $per_page;
    }
    function hack_table_column() {
        return array(self::hide_admin_option=>__("Hide Admin Scripts", AM_SLUG));
    }
    function hide_admin_scripts() {
        //return true/false if hidden or not
        $screen = get_current_screen();
        //$hiddencols = get_user_meta($user, 'manage'.$screen->id.'columnshidden'); //'managetools_page_advanced-modernizrcolumnshidden
        $hiddencols = get_hidden_columns($screen);
        foreach ($hiddencols as $col) {
            if ($col == self::hide_admin_option) { return false; }
        }
        return true;
    }

    function save_screen_opt($status, $option, $value)
    {
        if (AM_SLUG . '_scripts_per_screen' == $option) return $value;
//        if ('hide_admin_scripts-hide' == $option) return $value;
    }

    function admin_styles()
    {

    }

    function admin_page()
    {
        ?>
        <div class="wrap">
            <h2><?php _e("Advanced Modernizr", AM_SLUG); ?></h2>

            <form method="post" action="options.php">
                <?php
                // This prints out all hidden setting fields
                settings_fields(self::opt_group);
                do_settings_sections(self::page);
                submit_button();
                ?>
            </form>
        </div>
    <?php
    }

    function admin_init()
    {
        wp_register_style(AM_SLUG . '-admin-css', AM_ROOT_URI . 'css/admin.css');
        wp_register_script(AM_SLUG . '-admin-js', AM_ROOT_URI . 'js/admin.js', array('jquery', 'common'), false);
        register_setting(
            self::opt_group, // Option group
            AM_SLUG // Option name
        );

        add_settings_section(
            self::setting_section, // ID
            __('Configure Loading Settings', AM_SLUG), // Title
            array($this, 'print_section_info'), // Callback
            self::page // Page
        );
        add_settings_section(
            self::setting_section_script,
            __("Configure Modernizr Build", AM_SLUG),
            array($this, 'print_section_info'),
            self::page
        );
        foreach ($this->settings as $section => $settings) {
            foreach ($settings as $key => $setting) {
                add_settings_field($key, $setting['title'], array($this, 'setting_field'), self::page, $section, array('id' => $key, 'details' => $setting));
            }
        }
    }

    function setting_field($args)
    {
        switch ($args['details']['type']) {
            case 'checkbox':
                print '<input type="checkbox" name="' . AM_SLUG . '[' . $args['id'] . ']" id="' . $args['id'] . '" ' . checked($args['details']['value'], "on", false) . '">';
                print '<label for="' . $args['id'] . '">' . $args['details']['label'] . '</label>';
                if (isset($args['details']['helptext']) && strlen($args['details']['helptext']) > 0) {
                    print '<p class="description">' . $args['details']['helptext'] . '</p>';
                }
                break;
            case 'scripts':
                $sorted_scripts = $this->sort_scripts();
                $items = $this->total_scripts;
                $pages = floor($this->total_scripts/$this->user_scripts_per_page());
                //$this->hide_admin_scripts();
                ?>
                <p class="description"><?php _e("The help screen contains detailed information on these settings.", AM_SLUG); ?></p>
                <table id="fake_column">
                    <tr><th scope="col" id="<?php print self::hide_admin_option; ?>" class="manage-column column-<?php print self::hide_admin_option; ?>" style=""></th></tr>
                    <tr><td class="column-<?php print self::hide_admin_option; ?>"></td></tr>
                </table>

                <div class="tablenav top">
                    <span><?php _e("Filter scripts", AM_SLUG); ?></span>
                    <input id="scripts_filter" placeholder="script title" autocomplete="off" type="search">
                    <!--                    <span class="dashicons dashicons-no-alt" id="scripts_filter_close"></span>-->
                    <select id="scripts_filter_select">
                        <option data-filter="all"><?php _e("All Script Types", AM_SLUG); ?></option>
                        <?php foreach ($this->script_types as $filter) {
                            if ($filter == "admin" && $this->hide_admin_scripts()) { continue; }
                            printf('<option data-filter="%s">%s</option>', $filter, __($filter, AM_SLUG));
                        } ?>
                    </select>

                    <div class="tablenav-pages" id="am-screen-nav">
                        <span class="displaying-num"><?php printf(_n("1 item", "%s items", $items, AM_SLUG), $items); ?></span>
                        <span class="pagination-links"><a class="first-page disabled" data-page="1" title="<?php _e("Go to the first screen", AM_SLUG);?>">«</a>
                            <a class="prev-page disabled" data-page="-1" title="<?php _e("Go to the previous screen", AM_SLUG);?>">‹</a>
                            <span class="paging-input">
                                <input class="current-page" title="Current screen" type="text" name="paged" value="1" size="1"> of <span class="total-pages"><?php print $pages; ?></span>
                            </span>
                            <a class="next-page" data-page="+1" title="<?php _e("Go to the next screen", AM_SLUG);?>" >›</a>
                            <a class="last-page" data-page="<?php print $pages; ?>" title="<?php _e("Go to the last screen", AM_SLUG);?>" >»</a>
                        </span>
                    </div>
                </div>
                <table id="scripts_table" class="wp-list-table widefat fixed">
                <thead>
                <tr>
                    <th scope="col" class="manage-column script"><span><?php _e("Script", AM_SLUG); ?></span></th>
                    <th scope="col" class="manage-column check-column"><span><?php _e("Lazy Load", AM_SLUG); ?></span>
                    </th>
                    <th scope="col" class="manage-column check-column">
                        <span><?php _e("Default Load", AM_SLUG); ?></span></th>
                    <th scope="col" class="manage-column check-column"><span><?php _e("Always Load", AM_SLUG); ?></span>
                    </th>
                    <th scope="col" class="manage-column check-column"><span><?php _e("Never Load", AM_SLUG); ?></span>
                    </th>
                </tr>
                </thead>
                <tbody>
                <?php
                    if (count($sorted_scripts) > 0) {
                        foreach ($sorted_scripts as $script => $details) {
                            if ($script == advanced_modernizr::script_slug) { continue; /*lets skip letting people manipulate our own script since that'd probably foobar something.*/ }
                            $type = $this->where_script($details->src);
                            if ($type == "admin" && $this->hide_admin_scripts()) { continue; }
                            print "<tr class=\"script $type\"><td>";
                            print "<abbr title=\"{$details->src}\">$script</abbr>";
                            print "</td><td>";
                            $on = (isset($args['details']['value'][$script]['ll'])) ? $args['details']['value'][$script]['ll'] : "";
                            print '<input type="checkbox" class="" id="ll_' . $script . '" name="' . AM_SLUG . '[' . $args['id'] . '][' . $script . '][ll]" ' . checked($on, 'on', false) . '>';
                            print "</td>";
                            $on2 = (isset($args['details']['value'][$script]['loadtype'])) ? $args['details']['value'][$script]['loadtype'] : "";
                            $radios = array(
                                'default' => array(
                                    'title' => __('Default', AM_SLUG),
                                ),
                                'forceload' => array(
                                    'title' => __('Always', AM_SLUG),
                                ),
                                'forceunload' => array(
                                    'title' => __('Never', AM_SLUG),
                                ),
                            );
                            foreach ($radios as $radio => $details) {
                                $format = '<td><input type="radio" class="" id="f_%1$s_%2$s" name="%3$s" value="%1$s" %4$s title="%5$s"></td>';
                                $name = AM_SLUG . '[' . $args['id'] . '][' . $script . '][loadtype]';
                                $checked = checked($on2, $radio, false);
                                if ((!isset($on2) || $on2 == '') && $radio == 'default') {
                                    $checked = checked(true, true, false);
                                }
                                printf($format, $radio, $script, $name, $checked, $details['title']);
                            }
                            print "</tr>";
                        }
                    }

                print("</tbody></table>");
                break;
            case 'loadm':
                $dl_opts = array(
                    'cdn' => array(
                        'title' => __('<a href="http://cdnjs.com/">cdnjs</a> CDN', AM_SLUG),
                        ////cdnjs.cloudflare.com/ajax/libs/modernizr/2.7.1/modernizr.min.js
                    ),
                    'local-dev' => array(
                        'title' => __('Local|Dev', AM_SLUG),
                    ),
                    'local-min' => array(
                        'title' => __('Local|Minified', AM_SLUG),
                    ),
                    'local-custom' => array(
                        'title' => __('Custom', AM_SLUG),
                    ),
                );
                $name = AM_SLUG . '[' . $args['id'] . ']';
                printf('<ul id="%s">', $args['id']);
                foreach ($dl_opts as $key => $dl) {
                    printf('<li><input type="radio" name="%1$s" id="%2$s" value="%2$s" %3$s>%4$s</li>', $name, $key, checked($args['details']['value'], $key, false), $dl['title']);
                }
                print "</ul>";
                break;
            case 'custom_m':
                printf('<input type="text" name="%s" value="%s">', AM_SLUG . '[' . $args['id'] . ']', $args['details']['value']);
                printf('<button class="" id="upload_custom">%s</button>', __("Upload Custom", AM_SLUG));
                printf('<p class="description">%s</p>', $args['details']['helptext']);
                break;
        }
    }

    function sort_scripts()
    {
        global $wp_scripts;
        global $wp_styles;

        $pre_enqueue_scripts = $wp_scripts->queue;
        $pre_enqueue_styles = $wp_styles->queue;
        do_action('wp_enqueue_scripts'); // we want to grab theme scripts, but don't want to execute them.
        $post_enqueue_scripts = $wp_scripts->queue;
        $post_enqueue_styles = $wp_styles->queue;

        $this->clear_extras( //some folks enqueue styles on this hook,  so we have to remove both scripts and styles that we don't actually want to load.
            array(
                'scripts' => array(
                    'pre' => $pre_enqueue_scripts,
                    'post' => $post_enqueue_scripts),
                'styles' => array(
                    'pre' => $pre_enqueue_styles,
                    'post' => $post_enqueue_styles)
            )
        );

        $scripts = array();
        $this->total_scripts = count($wp_scripts->registered);
        foreach ($wp_scripts->registered as $script => $details) {
            $scripts[$script] = $details;
        }
        asort($scripts);
        return $scripts;
    }

    function clear_extras($args = array())
    {
        foreach ($args as $type => $values) {
            $diff = array_diff($values['post'], $values['pre']);
            if (is_array($diff) && count($diff) > 0) {
                foreach ($diff as $resource) {
                    switch ($type) {
                        case "scripts":
                            wp_dequeue_script($resource); //here we get rid of the scripts that weren't registered until they were enqueued. They'll stick around in the registered scripts.
                            break;
                        case "styles":
                            wp_dequeue_style($resource);
                            break;
                    }
                }
            }
        }
    }

    function where_script($path = 'types')
    {
        $paths = $this->script_types;
        foreach ($paths as $location => $type) {
            if (stripos($path, $location) !== false) {
                return $type;
            }
        }
        return "other";
    }

    /**
     * Print the Section text
     */
    public function print_section_info($args)
    {
        if (isset($args['id']) && $args['id'] === self::setting_section) {
            _e("Configure the Advanced Modernizr plugin using the settings below. Scripts will only Lazy Load if you select
            them below. You can also enable/disable lazy loading for logged in users.", AM_SLUG);
        }
        if (isset($args['id']) && $args['id'] === self::setting_section_script) {
            _e("Select the options for your build of Modernizr below.", AM_SLUG);
        }
    }

    /**
     * Get the settings option array and print one of its values
     */
    public function id_number_callback()
    {
        printf(
            '<input type="text" id="id_number" name="my_option_name[id_number]" value="%s" />',
            isset($this->options['id_number']) ? esc_attr($this->options['id_number']) : ''
        );
    }

    /**
     * Get the settings option array and print one of its values
     */
    public function title_callback()
    {
        printf(
            '<input type="text" id="title" name="my_option_name[title]" value="%s" />',
            isset($this->options['title']) ? esc_attr($this->options['title']) : ''
        );
    }
}
