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
function leadtrekker_token_info() {
  $info['types']['source_id'] = array(
    'name' => t('Source ID'),
    'description' => t('Tokens related to Leadtrekker Source IDs.'),
    'needs-data' => 'source_id',
  );

  // Node tokens.
  $info['tokens']['node']['source_id'] = array(
    'name' => t('Source ID on Node'),
    'description' => t("Leadtrekker Source ID attached to node."),
    'type' => 'source_id',
  );

  return $info;
}

/**
 * Implements hook_tokens().
 *
 * @ingroup leadtrekker
 */
function leadtrekker_tokens($type, $tokens, array $data = array(), array $options = array()) {
  $replacements = array();
  $sanitize = !empty($options['sanitize']);

  // Text format tokens.
  if ($type == 'source_id' && !empty($data['source_id'])) {
    $format = $data['source_id'];

    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'id':
          $replacements[$original] = $format->format;
          break;
      }
    }
  }

  // Node tokens.
  if ($type == 'node' && !empty($data['node'])) {
    $node = $data['node'];

    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'source_id':
            /**
             * Winging it here. Just getting the actual node page's info.
             */
          $the_current_page = menu_get_item();
          //Node pages
          if(isset($the_current_page['path']) && $the_current_page['path'] == "node/%") {
              if(isset($the_current_page['page_arguments'][0]->field_leadtrekker_source_id)) {
                  $the_source_id =  $the_current_page['page_arguments'][0]->field_leadtrekker_source_id['und'][0]['source_id'];
                  $replacements[$original] = $sanitize ? filter_xss($the_source_id) : $the_source_id;
              }
          }
          break;
      }
    }

    // Chained token relationships.
    if ($format_tokens = token_find_with_prefix($tokens, 'source_id')) {
      if ($items = field_get_items('node', $node, 'source_id')) {
        $replacements += token_generate('source_id', $format_tokens, array('source_id' => $items), $options);
      }
    }
  }

  return $replacements;
}
