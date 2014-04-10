<?php
class advanced_modernizr {
    public static $lazy_for_logged_in = "";
    const script_slug = 'amodernizr';
    function __construct() {
        $this->opts = get_option(AM_SLUG, array());
        self::$lazy_for_logged_in = ($this->opts['lazy_load_for_logged_in'] == 'on') ? true : false;//
        if (is_admin()) {
            require AM_ROOT.'classes/am_settings.class.php';
            new am_settings();
        }
        add_action('wp_print_scripts', array($this, 'print_scripts'), 9999);
        add_action('wp_head', array($this, 'add_inline_js'),9);
        add_action('wp_enqueue_scripts', array($this, 'load_am_js'));
    }
    function load_am_js() {
        switch ($this->opts['load_via_cdn']) {
            case 'local-dev':
                $source = AM_ROOT_URI.'js/modernizr.'.AM_SCRIPT_VERSION.'.dev.js';
                break;
            case 'local-min':
                $source = AM_ROOT_URI.'js/modernizr.'.AM_SCRIPT_VERSION.'.min.js';
                break;
            case 'local-custom':
                $source = $this->opts['load_via_cdn_custom'];
                break;
            case 'cdn':
                //cdnjs.cloudflare.com/ajax/libs/modernizr/2.7.1/modernizr.min.js
                $source = "//cdnjs.cloudflare.com/ajax/libs/modernizr/".AM_SCRIPT_VERSION."/modernizr.min.js";
                break;
            default:
                $source = AM_ROOT_URI.'js/modernizr.'.AM_SCRIPT_VERSION.'.dev.js';
        }
        wp_register_script(self::script_slug, $source, array(), false, false);
        wp_enqueue_script(self::script_slug);
    }
    function add_inline_js() {
        global $wp_filter;
        print "<script>Modernizr.load([{ load: am_ll_scripts } ]);</script>\n"; //HTML5, we're not supposed to need  type="text/javascript"
    }
    function print_scripts() {
        //hijack the scripts.
        if ( (self::$lazy_for_logged_in && is_user_logged_in()) || !is_user_logged_in() ) {
            //if the setting for lazy load is enabled for logged in users, or the current request is not logged in, do the lazy loader.
            $this->lazyload();
        }
    }
    function lazyload() {
        $lazy_loader = get_transient(AM_SLUG.'_lazyloader');

        if (!isset($lazy_loader['lazy']) || $lazy_loader == false || is_user_logged_in() ) {
            //nothing in the cache, build the js from scratch
            $lazy_loader = $this->build_ll();
        } else {
        }
        foreach ($lazy_loader as $action => $scripts) {
            switch ($action) {
                case 'dequeue':
                    foreach ($scripts as $script) {
                        wp_dequeue_script($script);
                    }
                    break;
                case 'enqueue':
                    foreach ($scripts as $script) {
                        wp_enqueue_script($script);
                    }
                    break;
            }
        }
        wp_localize_script(self::script_slug, 'am_ll_scripts', $lazy_loader['lazy']);
        set_transient(AM_SLUG.'_lazyloader', $lazy_loader);
    }
    function build_ll() {
        $ll = array();
        //set scripts to load,
        $script_settings = $this->opts['lazy_load_scripts'];

        //force load anything with that setting
        //unless it's lazy load too, then ignore it since it will get scooped up later.
        $fl = $this->force_load();
        foreach ($fl as $script => $details) {
            if (!isset($details['ll']) || $details['ll'] != 'on')
                $ll['enqueue'][$script] = $script;
        }

        //test queued scripts
        global $wp_scripts;
        foreach ($wp_scripts->queue as $queued) {
            if (!isset($script_settings[$queued]['loadtype'])) { $script_settings[$queued]['loadtype'] = "default"; }
            switch ($script_settings[$queued]['loadtype']) {
                case 'forceunload':
                    $ll['dequeue'][] = $queued;
                    break;

                default:
                    if (isset($script_settings[$queued]['ll']) && $script_settings[$queued]['ll'] == 'on') {
                        $ll['lazy'][$queued] = $wp_scripts->registered[$queued]->src;
                    }
            }
        }
        if (!isset($ll['lazy']) || count($ll['lazy']) <= 0) {
            $ll['lazy'][] = "";
        }
        return $ll;
    }
    //return all handles that are set to force load
    function force_load() {
        $fl = get_transient(AM_SLUG.'_force_load');
        if (!isset($fl) || $fl === false) {
            $fl = array_filter($this->opts['lazy_load_scripts'], function($v){
                if ($v['loadtype'] == 'forceload') {
                    return true;
                }
                return false;
            });
        }
        set_transient(AM_SLUG.'_force_load', $fl);
        return $fl;
    }
}
