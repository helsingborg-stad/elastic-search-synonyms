<?php

namespace ElasticPressSynonyms;

class App
{
    public function __construct()
    {
        add_filter('acf/settings/load_json', array($this, 'loadJson'));

        add_action('init', function () {
            if ($this->isElasticPress()) {
                if (is_multisite()) {
                    add_filter('wp_redirect', function ($location) {
                        if (strpos($location, 'admin.php?page=acf-options-synonyms') > -1) {
                            $location = network_admin_url(basename($location));
                        }
                        return $location;
                    });

                    if (is_network_admin()) {
                        acf_add_options_page(array(
                            'page_title' => __('Synonyms', 'municipio')
                        ));
                    }

                    add_action('network_admin_menu', array($this, 'multsiteAddSynonymsOptionsPage'));
                } else {
                    add_action('admin_menu', array($this, 'addSynonymsOptionsPage'));
                }

                //add_filter('ep_config_mapping', 'elasticPressSynonymMapping');
            }
        });
    }

    public function loadJson($paths)
    {
        $paths[] = ELASTICPRESS_SYNONYMS_PATH . 'source/acf-json';
        return $paths;
    }

    /**
     * Setup synonym mapping for elasticpress
     * @param  array $mapping
     * @return array
     */
    public function elasticPressSynonymMapping($mapping)
    {
        // bail early if $mapping is missing or not array
        if (!isset($mapping) || !is_array($mapping)) {
            return $mapping;
        }

        // ensure we have filters and is array
        if (!isset($mapping['settings']['analysis']['filter']) || !is_array($mapping['settings']['analysis']['filter'])) {
            return $mapping;
        }

        // ensure we have analyzers and is array
        if (!isset($mapping['settings']['analysis']['analyzer']['default']['filter']) || !is_array($mapping['settings']['analysis']['analyzer']['default']['filter'])) {
            return $mapping;
        }

        $this->fields();

        $synonyms = array();
        if (is_multisite()) {
            switch_to_blog(BLOG_ID_CURRENT_SITE);
            $synonyms = get_field('elasticpress_synonyms', 'options');
            restore_current_blog();
        } else {
            $synonyms = get_field('elasticpress_synonyms', 'options');
        }

        if (!$synonyms || empty($synonyms)) {
            return $mapping;
        }

        $synonymData = array();
        foreach ($synonyms as $synonym) {
            $data = array_merge(
                (array)$synonym['word'],
                explode(',', $synonym['synonyms'])
            );

            $data = array_map('trim', $data);
            $data = implode(',', $data);

            $synonymData[] = $data;
        }

        $mapping['settings']['analysis']['filter']['elasticpress_synonyms_filter'] = array(
            'type' => 'synonym',
            'synonyms' => $synonymData
        );

        $mapping['settings']['analysis']['analyzer']['elasticpress_synonyms'] = array(
            'tokenizer' => 'standard',
            'filter' => array(
                'lowercase',
                'elasticpress_synonyms_filter'
            )
        );

        return $mapping;
    }

    public function multsiteAddSynonymsOptionsPage()
    {
        if (!class_exists('EP_Modules') || !function_exists('acf_add_options_page')) {
            return;
        }

        if (!isset($GLOBALS['acf_options_pages']['acf-options-synonyms'])) {
            return;
        }

        $optionsPage = new \acf_pro_options_page;
        $this->fields();

        $page = $GLOBALS['acf_options_pages']['acf-options-synonyms'];
        $slug = add_submenu_page('elasticpress', $page['page_title'], $page['menu_title'], $page['capability'], $page['menu_slug'], function () {
            acf_pro_get_view('options-page', array(
                'page' => $GLOBALS['acf_options_pages']['acf-options-synonyms']
            ));
        });

        add_action("load-{$slug}", array($optionsPage, 'admin_load'));
    }

    /**
     * Adds synonyms wordlist options page
     */
    public function addSynonymsOptionsPage()
    {
        if (!class_exists('EP_Modules') || !function_exists('acf_add_options_page')) {
            return;
        }

        acf_add_options_page(array(
            'page_title' => __('Synonyms', 'municipio'),
            'parent_slug' => 'elasticpress'
        ));
    }

    /**
     * Checks if ElasticPress search is activated
     * @return boolean
     */
    public function isElasticPress()
    {
        if (!class_exists('EP_Modules')) {
            return false;
        }

        $modules = \EP_Modules::factory();
        $activeModules = $modules->get_active_modules();

        if (isset($activeModules['search'])) {
            return true;
        }

        return false;
    }

    public function fields()
    {
        if (!function_exists('acf_add_local_field_group')) {
            return;
        }

        acf_add_local_field_group(array(
            'key' => 'group_57fcc7ef3b815',
            'title' => 'Synonyms',
            'fields' => array(
                array(
                    'key' => 'field_57fcc7f8c8862',
                    'label' => 'Synonyms',
                    'name' => 'elasticpress_synonyms',
                    'type' => 'repeater',
                    'instructions' => '',
                    'required' => 0,
                    'conditional_logic' => 0,
                    'wrapper' => array(
                        'width' => '',
                        'class' => '',
                        'id' => '',
                    ),
                    'collapsed' => '',
                    'min' => '',
                    'max' => '',
                    'layout' => 'table',
                    'button_label' => 'Add word',
                    'sub_fields' => array(
                        array(
                            'key' => 'field_57fcc813c8863',
                            'label' => 'Word',
                            'name' => 'word',
                            'type' => 'text',
                            'instructions' => 'The original word',
                            'required' => 1,
                            'conditional_logic' => 0,
                            'wrapper' => array(
                                'width' => '20',
                                'class' => '',
                                'id' => '',
                            ),
                            'default_value' => '',
                            'placeholder' => '',
                            'prepend' => '',
                            'append' => '',
                            'maxlength' => '',
                        ),
                        array(
                            'key' => 'field_57fcc820c8864',
                            'label' => 'Synonyms',
                            'name' => 'synonyms',
                            'type' => 'text',
                            'instructions' => 'Comma separated list of synonyms',
                            'required' => 1,
                            'conditional_logic' => 0,
                            'wrapper' => array(
                                'width' => '',
                                'class' => '',
                                'id' => '',
                            ),
                            'default_value' => '',
                            'placeholder' => '',
                            'prepend' => '',
                            'append' => '',
                            'maxlength' => '',
                        ),
                    ),
                ),
            ),
            'location' => array(
                array(
                    array(
                        'param' => 'options_page',
                        'operator' => '==',
                        'value' => 'acf-options-synonyms',
                    ),
                ),
            ),
            'menu_order' => 0,
            'position' => 'normal',
            'style' => 'default',
            'label_placement' => 'top',
            'instruction_placement' => 'label',
            'hide_on_screen' => '',
            'active' => 1,
            'description' => '',
        ));
    }
}
