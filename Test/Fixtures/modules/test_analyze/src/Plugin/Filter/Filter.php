<?php

namespace Drupal\test_analyze\Plugin\Filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\filter\Plugin\FilterBase;

/**
 * TODO: class docs.
 *
 * @Filter(
 *   id = "test_analyze_filter",
 *   provider = "TODO: replace this with a value",
 *   title = @Translation("TODO: replace this with a value"),
 *   description = "TODO: replace this with a value",
 *   weight = "TODO: replace this with a value",
 *   status = "TODO: replace this with a value",
 *   settings = "TODO: replace this with a value",
 * )
 */
class Filter extends FilterBase {

  /**
   * {@inheritdoc}
   */
  public function getType() {
    // Returns the processing type of this filter plugin.
    module_invoke_all('test_analyze_plugin_all', $param);
    module_invoke('other_module', 'test_analyze_plugin_single', $param);
    drupal_alter('test_analyze_plugin_alter', $param);
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    // Returns the administrative label for this filter plugin.
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    // Returns the administrative description for this filter plugin.
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    // Generates a filter's settings form.
  }

  /**
   * {@inheritdoc}
   */
  public function prepare($text, $langcode) {
    // Prepares the text for processing.
  }

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    // Performs the filter processing.
  }

  /**
   * {@inheritdoc}
   */
  public function getHTMLRestrictions() {
    // Returns HTML allowed by this filter's configuration.
  }

  /**
   * {@inheritdoc}
   */
  public function tips($long = FALSE) {
    // Generates a filter's tip.
  }

}
