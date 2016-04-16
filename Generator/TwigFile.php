<?php

/**
 * @file
 * Contains DrupalCodeBuilder\Generator\TwigFile.
 */

namespace DrupalCodeBuilder\Generator;

/**
 * Generator for a twig template.
 */
class TwigFile extends File {

  /**
   * Return the data for the file this component provides.
   */
  public function getFileInfo() {
    $files['templates/' . $this->name] = array(
      'path' => 'templates',
      'filename' => $this->name,
      'body' => [
        $this->getTwigContents(),
      ],
      'join_string' => "\n",
    );
    return $files;
  }

  protected function getTwigContents() {
    $theme_hook_name = $this->component_data['theme_hook_name'];

    $twig = <<<EOT
{#
/**
 * @file
 * Default theme implementation to display a $theme_hook_name.
 *
 * Available variables:
 * - todo:
 *
 * @ingroup themeable
 */
#}
<article>
  Content here.
</article>
EOT;

    return $twig;
  }

}
