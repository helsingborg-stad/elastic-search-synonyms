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
                    add_action('network_admin_menu', array($this, 'addSynonymsOptionsPage'));
                } else {
                    add_action('admin_menu', array($this, 'addSynonymsOptionsPage'));
                }

                add_filter('ep_config_mapping', 'elasticPressSynonymMapping');
            }
        });
    }

    public function loadJson($paths)
    {
        $paths[] = SEARCHENHANCER_PATH . 'source/acf-json';
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
        if (! isset($mapping) || ! is_array($mapping)) {
            return false;
        }

        // ensure we have filters and is array
        if (!isset($mapping['settings']['analysis']['filter'])|| ! is_array($mapping['settings']['analysis']['filter'])) {
            return false;
        }

        // ensure we have analyzers and is array
        if (!isset($mapping['settings']['analysis']['analyzer']['default']['filter']) || ! is_array($mapping['settings']['analysis']['analyzer']['default']['filter'])) {
            return false;
        }

        $synonyms = get_field('elasticpress_synonyms', 'options');

        foreach ($synonyms as $synonym) {
            $filterKey = 'elasticpress_synonyms_' . sanitize_title($synonym['word']);
            $synonymsList = str_replace(', ', ',', $synonym['synonyms']);

            $mapping['settings']['analysis']['filter'][$filterKey] = array(
                'type' => 'synonym',
                'synonyms' => array(
                    $synonymsList,
                ),
            );

            // tell the analyzer to use our newly created filter
            $mapping['settings']['analysis']['analyzer']['default']['filter'][] = $filterKey;
        }

        return $mapping;
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
}
