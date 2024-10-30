<?php

namespace DrupalCodeBuilder\Test\Unit;

use DrupalCodeBuilder\Generator\Render\Yaml;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Unit tests for the value renderer.
 */
class RenderYamlTest extends TestCase {

  /**
   * Data provider.
   */
  public static function dataRenderYaml() {
    $data = [
      'plain' => [
        'data' => [
          'string' => 'bar',
          'bool' => TRUE,
          'null' => NULL,
          'empty' => [],
          'colon' => ':',
        ],
        'expected' => [
          'string: bar',
          'bool: true',
          'null: null',
          'empty: {  }',
          "colon: ':'",
        ],
      ],
      'list' => [
        'data' => [
          'foo' => [
            'bar',
          ],
        ],
        'expected' => [
          'foo:',
          '  - bar',
        ],
      ],
      'keyed' => [
        'data' => [
          'foo' => [
            'key' => 'bar',
          ],
        ],
        'expected' => [
          'foo:',
          '  key: bar',
        ],
      ],
      'nested' => [
        'data' => [
          'foo' => [
            'key' => 'bar',
          ],
        ],
        'expected' => [
          'foo:',
          '  key: bar',
        ],
      ],
      'inline_0' => [
        'data' => [
          'foo' => [
            'key' => [
              'bar',
              'bax',
            ],
          ],
          'bar' => 'bax',
        ],
        'expected' => [
          '{ foo: { key: [bar, bax] }, bar: bax }',
        ],
        'inline_level' => 0,
      ],
      'inline_1' => [
        'data' => [
          'foo' => [
            'key' => [
              'bar',
              'bax',
            ],
          ],
          'bar' => 'bax',
        ],
        'expected' => [
          'foo: { key: [bar, bax] }',
          'bar: bax',
        ],
        'inline_level' => 1,
      ],
      'inline_2' => [
        'data' => [
          'foo' => [
            'key' => [
              'bar',
              'bax',
            ],
          ],
        ],
        'expected' => [
          'foo:',
          '  key: [bar, bax]',
        ],
        'inline_level' => 2,
      ],
      'inline_3' => [
        'data' => [
          'foo' => [
            'key_1' => [
              'key_2' => [
                'bar',
                'bax',
              ],
            ],
          ],
        ],
        'expected' => [
          'foo:',
          '  key_1:',
          '    key_2: [bar, bax]',
        ],
        'inline_level' => 3,
      ],
      'spaced' => [
        'data' => [
          'foo' => [
            'key' => 'bar',
          ],
          'bar' => [
            'key' => 'bar',
          ],
        ],
        'expected' => [
          'foo:',
          '  key: bar',
          '',
          'bar:',
          '  key: bar',
        ],
        'blank_lines_until_level' => 1,
      ],
      // Inner renderer goes inline earlier than outer.
      'inner_render_inline' => [
        'data' => [
          'foo' => Yaml::create(['inner' => 'bar'], inline_from_level: 0),
          'bar' => [
            'key' => 'bar',
          ],
        ],
        'expected' => [
          'foo: { inner: bar }',
          'bar:',
          '  key: bar',
        ],
      ],
      // Inner renderer goes deeper multiline than outer.
      'inner_render_multiline' => [
        'data' => [
          'foo' => Yaml::create(
            [
              'key1' => [
                'key2' => 'bar',
              ],
            ]
          ),
          'bar' => [
            'key1' => [
              'key2' => 'bar',
            ],
          ],
        ],
        'expected' => [
          'foo:',
          '  key1:',
          '    key2: bar',
          'bar: { key1: { key2: bar } }',
        ],
        'inline_level' => 1,
      ],
    ];

    // Fill in defaults.
    // TODO: remove this when presumably PHPUnit supports this.
    foreach ($data as &$set) {
      // These must be in the same order as the test method parameters.
      $template = [
        'data' => NULL,
        'expected' => NULL,
        'inline_level' => -1,
        'blank_lines_until_level' => -1,
      ];
      foreach ($set as $key => $value) {
        $template[$key] = $value;
      }
      $set = $template;
    }

    return $data;
  }

  /**
   * Tests the YAML renderer.
   *
   * @param array $data
   *   The data to render.
   * @param array $expected
   *   The expected code lines.
   */
  #[DataProvider('dataRenderYaml')]
  public function testRenderYaml(array $data, array $expected, int $inline_level, int $blank_lines_until_level): void {
    $yaml_lines = Yaml::create(
      $data,
      inline_from_level: $inline_level,
      blank_lines_until_level: $blank_lines_until_level,
    )->render();

    $this->assertEquals($expected, $yaml_lines);
  }

}
