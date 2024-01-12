<?php $data =
array (
  'boolean' => 
  array (
    'type' => 'boolean',
    'label' => 'Boolean',
    'description' => 'Field to store a true or false value.',
    'default_widget' => 'boolean_checkbox',
    'default_formatter' => 'boolean',
  ),
  'text' => 
  array (
    'type' => 'text',
    'label' => 'Text (formatted)',
    'description' => 'Ideal for titles and names that need to support markup such as bold, italics or links',
    'default_widget' => 'text_textfield',
    'default_formatter' => 'text_default',
  ),
  'string' => 
  array (
    'type' => 'string',
    'label' => 'Text (plain)',
    'description' => 'Ideal for titles and names',
    'default_widget' => 'string_textfield',
    'default_formatter' => 'string',
  ),
);