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

    if (isset($data['entity_type']) && isset($data['entity'])) {
        $source_id = _has_source_id_field(entity_metadata_wrapper($data['entity_type'], $data['entity']));
    } else {
        /**
         * All the defined fields, each containing a 'type' key which contains the base field_widget
         */
        $current_page = menu_get_item();
        $content_suggestion = "";

        if (is_array($current_page['load_functions']) && in_array("taxonomy_term_load", $current_page['load_functions'])) {
            $content_suggestion = "taxonomy_term";
        } elseif (is_array($current_page['load_functions']) && in_array("node_load", $current_page['load_functions'])) {
            $content_suggestion = "node";
        }

        if($content_suggestion == 'taxonomy_term'
            && isset($current_page['page_arguments'])
            && !path_is_admin($current_page['path'])) {
            foreach($current_page['page_arguments'] as $key => $value) {
                $entity = entity_metadata_wrapper($content_suggestion, $value);
                $source_id = _has_source_id_field($entity);

            }
        }

        if($content_suggestion == 'node' && isset($current_page['page_arguments'])) {
            foreach($current_page['page_arguments'] as $key => $value) {
                $entity = entity_metadata_wrapper($value->type, $value);
                $source_id = _has_source_id_field($entity);
            }
        }
    }

    if (isset($source_id)) {
        $source_id = $sanitize ? filter_xss($source_id) : $source_id;
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

/**
 * Check custom field types for the "Source Id" fieldtype.
 * @return array
 */
function _registered_source_id_fields()
{
    $light_weight_map = field_info_field_map();
    foreach ($light_weight_map as $field_name => $field_info) {
        if ($field_info['type'] == "source_id") {
            $registered_source_id_fields[$field_name] = $field_info;
        }
    }

    return isset($registered_source_id_fields) ? $registered_source_id_fields : [];
}

/**
 * Returns a source_id if found.
 * @param EntityMetadataWrapper $data
 * @return bool
 */
function _has_source_id_field(\EntityMetadataWrapper $data)
{
    try {
        $fields_defined = _registered_source_id_fields();
        $data_values = (array)$data->value();
        foreach ($data_values as $field_name => $field_info) {
            if (array_key_exists($field_name, $fields_defined) && isset($data->value()->$field_name[LANGUAGE_NONE][0])) {
                return $data->value()->$field_name[LANGUAGE_NONE][0]['source_id'];
            }
        }
    } catch (Exception $exception) {
        return false;
    }
}