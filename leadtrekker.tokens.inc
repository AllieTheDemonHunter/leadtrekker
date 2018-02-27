<?php

/**
 * @file
 * Token callbacks for the leadtrekker module.
 */

/**
 * Implements hook_token_info().
 *
 * @ingroup leadtrekker
 */
function leadtrekker_token_info()
{
    $info['types']['source_id'] = array(
        'name' => t('Source ID'),
        'description' => t('Tokens related to Leadtrekker Source IDs.'),
        'needs-data' => 'source_id',
    );

    $info['tokens']['current-page']['source_id'] = array(
        'name' => t('Source ID on Entity'),
        'description' => t("Leadtrekker Source ID attached to entity."),
        'type' => 'source_id',
    );

    $info['tokens']['term']['source_id'] = array(
        'name' => t('Source ID on Entity'),
        'description' => t("Leadtrekker Source ID attached to entity."),
        'type' => 'source_id',
    );

    return $info;
}

/**
 * Implements hook_tokens().
 *
 * @ingroup leadtrekker
 */
function leadtrekker_tokens($type, $tokens, array $data = array(), array $options = array())
{
    $replacements = array();
    $sanitize = !empty($options['sanitize']);

    /**
     * All the defined fields, each containing a 'type' key which contains the base field_widget
     */
    $field_types = field_info_field_map();
    $current_page = menu_get_object() ? menu_get_object() : menu_get_item();

    /**
     * Check for 'source_id' type field set on this entity.
     */
    foreach ($field_types as $field_name => $field_info) {
        /**
         * Terms (page)
         */
        if (is_array($current_page) && $current_page['page_callback'] == "taxonomy_term_page") {
            if (is_object($current_page['page_arguments'][0])) {
                if ($field_info['type'] == "source_id") {
                    $registered_source_id_fields[$field_name] = $field_info;
                    /**
                     * TEST for the field's presence on this entity.
                     */
                    if (isset($current_page['page_arguments'][0]->$field_name)) {
                        $source_id = $current_page['page_arguments'][0]->$field_name[LANGUAGE_NONE][0]['source_id'];
                        $source_id = $sanitize ? filter_xss($source_id) : $source_id;
                    }
                }
            }
        } /**
         * Terms
         */
        elseif (is_array($current_page) && isset($current_page['path']) && $current_page['path'] == "taxonomy/term/%") {
            if ($field_info['type'] == "source_id") {
                $registered_source_id_fields[$field_name] = $field_info;
                /**
                 * Load term
                 */
                $term = entity_metadata_wrapper('taxonomy_term',taxonomy_term_load(array_pop($current_page['original_map'])));
                /**
                 * TEST for the field's presence on this entity.
                 */
                if (isset($term->raw()->$field_name)) {
                    $source_id = $term->raw()->$field_name[LANGUAGE_NONE][0]['source_id'];
                    $source_id = $sanitize ? filter_xss($source_id) : $source_id;
                }
            }
        } /**
         * Nodes
         */
        elseif (!empty($field_info) && isset($field_info['type'])) {
            if ($field_info['type'] == "source_id") {
                $registered_source_id_fields[$field_name] = $field_info;
                /**
                 * TEST for the field's presence on this entity.
                 */
                if (isset($current_page->$field_name)) {
                    $source_id = $current_page->$field_name[LANGUAGE_NONE][0]['source_id'];
                    $source_id = $sanitize ? filter_xss($source_id) : $source_id;
                }
            }
        }
    }

    if (isset($source_id)) {
        foreach ($tokens as $name => $original) {
            switch ($name) {
                case 'source_id':
                    $replacements[$original] = $source_id;
                    break;
            }
        }
    }

    return $replacements;
}
