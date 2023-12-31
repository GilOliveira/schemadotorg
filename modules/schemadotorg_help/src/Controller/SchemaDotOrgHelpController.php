<?php

declare(strict_types = 1);

namespace Drupal\schemadotorg_help\Controller;

use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\Html;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller routines for Schema.org Blueprints help routes.
 */
class SchemaDotOrgHelpController extends ControllerBase {

  /**
   * The module handler to invoke the alter hook.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Module extension list.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  protected $moduleExtensionList;

  /**
   * The extension path resolver.
   *
   * @var \Drupal\Core\Extension\ExtensionPathResolver
   */
  protected $extensionPathResolver;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = new static();
    $instance->moduleHandler = $container->get('module_handler');
    $instance->moduleExtensionList = $container->get('extension.list.module');
    $instance->extensionPathResolver = $container->get('extension.path.resolver');
    return $instance;
  }

  /**
   * Prints a page listing general help for a module.
   *
   * @param string $name
   *   A module name to display a help page for.
   *
   * @return array
   *   A render array as expected by
   *   \Drupal\Core\Render\RendererInterface::render().
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   */
  public function helpPage(string $name): array {
    global $base_path;

    $build = [];

    // Navigation and videos.
    $build['navigation'] = [
      '#prefix' => '<p>',
      '#suffix' => '</p>',
    ];
    $build['navigation']['learn_more'] = [
      '#type' => 'operations',
      '#links' => $this->getHelpTopicsAsOperations(),
    ];
    $build['navigation']['or'] = [
      '#prefix' => '&nbsp; ',
      '#markup' => $this->t('or'),
      '#suffix' => ' &nbsp;',
    ];
    $build['navigation']['link'] = [
      '#type' => 'link',
      '#title' => $this->t('► Watch videos'),
      '#url' => Url::fromRoute('schemadotorg_help.videos'),
      '#attributes' => [
        'class' => ['use-ajax', 'button', 'button--small', 'button--extrasmall'],
        'data-dialog-type' => 'modal',
        'data-dialog-options' => Json::encode([
          'width' => 800,
        ]),
      ],
    ];

    $module_path = $this->extensionPathResolver->getPath('module', $name);
    $module_readme = $module_path . '/README.md';
    if (!file_exists($module_readme)) {
      return $build;
    }

    $build['#title'] = $this->moduleHandler()->getName($name);

    $contents = file_get_contents($module_readme);

    // Remove the table of contents.
    $contents = preg_replace('/^.*?(Introduction\s+------------)/s', '$1', $contents);

    if (class_exists('\Michelf\Markdown')) {
      // phpcs:ignore Drupal.Classes.FullyQualifiedNamespace.UseStatementMissing
      $markup = \Michelf\Markdown::defaultTransform($contents);

      // Convert <p><code> to <pre> tag.
      $markup = str_replace('<p><code>', '<pre>', $markup);
      $markup = str_replace('</code></p>', '</pre>', $markup);

      // Add base path to hrefs.
      $markup = preg_replace('#\(/(admin/.*?)\)#', '(<a href="' . $base_path . '$1">/$1</a>)', $markup);

      // Create fake filter object with settings.
      $filter = (object) ['settings' => ['filter_url_length' => 255]];
      $markup = _filter_url($markup, $filter);

      // Replace @see DIAGRAM.html.
      $module_diagram = $module_path . '/DIAGRAM.html';
      if (file_exists($module_diagram) && str_contains($markup, 'DIAGRAM.html')) {
        $document = Html::load(file_get_contents($module_diagram));
        $markup = preg_replace_callback(
          '/<p>@see DIAGRAM\.html#([-_A-Za-z0-9]+)<\/p>/',
          function ($matches) use ($document) {
            $dom_node = $document->getElementById($matches[1]);
            if ($dom_node) {
              return $document->saveXML($dom_node);
            }
            else {
              return '';
            }
          },
          $markup
        );

        $build['#attached']['library'][] = 'schemadotorg/schemadotorg.mermaid';
      }

      $build['readme'] = [
        '#markup' => $markup,
      ];
    }
    else {
      $build['readme'] = [
        '#plain_text' => $contents,
        '#prefix' => '<pre>',
        '#suffix' => '</pre>',
      ];
    }

    if ($name === 'schemadotorg') {
      $build['modules'] = [
        'title' => [
          '#markup' => $this->t('Modules'),
          '#prefix' => '<h2>',
          '#suffix' => '</h2>',
        ],
        'packages' => $this->buildModules(),
        'command' => $this->buildDrushCommand(),
      ];
    }

    return $build;
  }

  /**
   * Returns Schema.org help videos page.
   */
  public function videos(): array {
    // Videos.
    $videos = [
      [
        'title' => $this->t('Schema.org Blueprints for Drupal @ Pittsburgh 2023'),
        'content' => $this->t("This presentation is for anyone who has created a website using Drupal and is interested in discovering a standardized, simpler, and faster way to model and build a website's content and information architecture."),
        'youtube_id' => 'Yo6Vw-s1FtM',
      ],
      [
        'title' => $this->t('Schema.org Blueprints Installation & Setup'),
        'content' => $this->t("This presentation walks-through installing and setting up the Schema.org Blueprints module."),
        'youtube_id' => 'Dludw8Eomh4',
      ],
      [
        'title' => $this->t('Schema.org Blueprints module in 7 minutes'),
        'content' => $this->t('A presentation and demo of the Schema.org Blueprints for Drupal in 7 minutes.'),
        'youtube_id' => 'KzNFAEfbFNw',
      ],
      [
        'title' => $this->t('Defining the goals of the Schema.org Blueprints module for Drupal'),
        'content' => $this->t('This presentation explores implementing a next-generation Content Management System (CMS) that supports progressive decoupling, structured data, advanced content authoring, and omnichannel publishing using the Schema.org Blueprints module for Drupal.'),
        'youtube_id' => '5RgPhNvEC4U',
      ],
      [
        'title' => $this->t('Schema.org Blueprints for Drupal'),
        'content' => $this->t('A session about the Schema.org Blueprints for Drupal from DrupalCamp NJ 2023.'),
        'youtube_id' => 'VG5Hm0Ar95c',
      ],
      [
        'title' => $this->t('Baking a Recipe using the Schema.org Blueprints module for Drupal'),
        'content' => $this->t("This presentation shows how to create a 'recipe' content type in Drupal based entirely on https://Schema.org/Recipe using two possible approaches via the Paragraphs module or Flex Field module to build out the nutrition information."),
        'youtube_id' => 'F31avX4gRm0',
      ],
      [
        'title' => $this->t('Schema.org Blueprints - Short Overview'),
        'content' => $this->t('This short presentation explains the what and why behind the Schema.org Blueprints module and shows how to use it to build a Schema.org Event content type in Drupal.'),
        'youtube_id' => 'XkZP6QjJkWs',
      ],
      [
        'title' => $this->t('Schema.org Blueprints - Full Demo'),
        'content' => $this->t('This extended presentation walks through the background, configuration, and future of the Schema.org Blueprints module. It provides an in-depth demo of building an entire website architecture that leverages Schema.org type, properties, and enumerations in 5 minutes.'),
        'youtube_id' => '_kk97O1SEw0',
      ],
      [
        'title' => $this->t('Schema.org Blueprints Organization Starter Kit'),
        'content' => $this->t("The Schema.org Blueprints Starter Kit: Organization module provides a starting point for building out an Organization's content and information model using Schema.org."),
        'youtube_id' => 'cEJ6pfpBACQ',
      ],
      [
        'title' => $this->t('Schemadotorg Blueprints - Exploration'),
        'content' => $this->t('This video explores the Schema.org Blueprints module for Drupal.'),
        'youtube_id' => 'A2p6ij2E5Qw',
      ],
      [
        'title' => $this->t('What is the Drupal Schema.org Blueprints Module?'),
        'content' => $this->t('A box-opening of the new schema.org blueprints module by the wonderful Jacob Rockowitz!'),
        'youtube_id' => 'mG7Ic91SOq4',
      ],
      [
        'title' => $this->t('Schema.org - What, How, Why?'),
        'content' => $this->t("This presentation explains why search engines now want metadata, how it works, and what you need to know as a dev (as seen in the context of Yandex, Russia's most used search engine, and schema.org)."),
        'youtube_id' => 'hcahQfN5u9Y',
      ],
    ];
    $rows = [];
    foreach ($videos as $video) {
      $video_url = Url::fromUri('https://youtu.be/' . $video['youtube_id']);
      $video_thumbnail = [
        '#theme' => 'image',
        '#uri' => 'https://img.youtube.com/vi/' . $video['youtube_id'] . '/0.jpg',
        '#alt' => $video['title'],
        '#attributes' => [
          'style' => 'display: block',
        ],
      ];

      $row = [];
      $row['thumbnail'] = [
        'data' => [
          '#type' => 'link',
          '#url' => $video_url,
          '#title' => $video_thumbnail,
          '#attributes' => [
            'target' => '_blank',
          ],
        ],
      ];
      // Content.
      $row['content'] = [
        'data' => [
          'title' => [
            '#markup' => $video['title'],
            '#prefix' => '<div><strong>',
            '#suffix' => '</strong></div>',
          ],
          'content' => [
            '#markup' => $video['content'],
            '#prefix' => '<div>',
            '#suffix' => '</div>',
          ],
          'link' => [
            '#type' => 'link',
            '#url' => $video_url,
            '#title' => $this->t('▶ Watch video'),
            '#attributes' => [
              'class' => ['button', 'button--small', 'button--extrasmall'],
              'target' => '_blank',
            ],
          ],
        ],
      ];
      $rows[] = ['data' => $row, 'no_striping' => TRUE];
    }

    return [
      '#theme' => 'table',
      '#header' => [
        'thumbnail' => [
          'data' => '',
          'width' => '200',
          'style' => 'padding:0; border-top-color: transparent',
          'class' => [RESPONSIVE_PRIORITY_LOW],
        ],
        'content' => [
          'data' => '',
          'style' => 'padding:0; border-top-color: transparent',
        ],
      ],
      '#rows' => $rows,
      '#attributes' => [
        'border' => 0,
        'cellpadding' => 2,
        'cellspacing' => 0,
      ],
    ];
  }

  /**
   * Build a list of Schema.org Blueprints drush commands to enable sub-modules.
   *
   * @return array
   *   A renderable array containing a list of Schema.org Blueprints
   *   Drush commands to enable sub-modules.
   */
  protected function buildDrushCommand(): array {
    // Get all Schema.org Blueprint packages and modules.
    $packages = [];
    $production_modules = [];
    $development_modules = [];
    foreach ($this->moduleExtensionList->getAllAvailableInfo() as $module_name => $module_info) {
      $package = $module_info['package'];

      // Skip test modules and none schemadotorg modules.
      if ($package === 'Schema.org Blueprints Test'
        || str_starts_with($module_name, 'schemadotorg')) {
        continue;
      }

      // Collect packages.
      if (str_starts_with($package, 'Schema.org Blueprints')) {
        $packages[$package] = $package;
      }

      // Collect production and development modules.
      if (!empty($module_info['schemadotorg_production'])) {
        $production_modules[$module_name] = $module_name;
      }
      else {
        $development_modules[$module_name] = $module_name;
      }
    }
    ksort($packages);
    ksort($production_modules);
    ksort($development_modules);

    $packages = ['Schema.org Blueprints Core' => 'Schema.org Blueprints Core'] + $packages;

    $commands = [];
    $commands[] = '# Install all Schema.org Blueprints modules.';
    $commands[] = 'drush pm-list --format=list | grep schemadotorg | xargs drush install -y';
    $commands[] = '';
    $commands[] = '------------------------------------------------------------------------------------------';
    $commands[] = '';
    foreach ($packages as $package) {
      $commands[] = "# Install $package modules.";
      $commands[] = "drush pm-list --format=list --package='$package' | xargs drush install -y";
      $commands[] = '';
    }
    $commands[] = '------------------------------------------------------------------------------------------';
    $commands[] = '';
    $commands[] = "# Install Schema.org Blueprints production modules.";
    $commands[] = "# The below modules should always be enabled on the production environment.";
    $commands[] = "drush install -y " . implode('\\' . PHP_EOL . '  ', $production_modules) . ';';
    $commands[] = '';
    $commands[] = "# Install Schema.org Blueprints development modules.";
    $commands[] = "# The below modules should generally be enabled on only development environments.";
    $commands[] = "drush install -y " . implode('\\' . PHP_EOL . '  ', $development_modules) . ';';

    return [
      '#type' => 'details',
      '#title' => $this->t('Drush commands'),
      '#description' => $this->t('Use the below Drush commands to install all or a package of Schema.org Blueprints modules.'),
      '#open' => TRUE,
      'commands' => [
        '#plain_text' => implode(PHP_EOL, $commands),
        '#prefix' => '<pre>',
        '#suffix' => '</pre>',
      ],
    ];
  }

  /**
   * Build a list of Schema.org Blueprints sub-modules.
   *
   * @return array
   *   A renderable array containing Schema.org Blueprints sub-modules.
   */
  protected function buildModules(): array {
    $modules = array_filter($this->moduleExtensionList->getAllAvailableInfo(), function (array $info): bool {
      return str_starts_with($info['package'], 'Schema.org Blueprints');
    });
    ksort($modules);

    $header = [
      'title' => [
        'data' => $this->t('Title / Description'),
        'width' => '55%',
      ],
      'status' => [
        'data' => $this->t('Module status'),
        'class' => [RESPONSIVE_PRIORITY_LOW],
        'width' => '10%',
      ],
      'production' => [
        'data' => $this->t('Recommended for production'),
        'class' => [RESPONSIVE_PRIORITY_LOW],
        'width' => '10%',
      ],
      'jsonld' => [
        'data' => $this->t('JSON-LD integration'),
        'class' => [RESPONSIVE_PRIORITY_LOW],
        'width' => '10%',
      ],
      'configuration' => [
        'data' => $this->t('Configuration'),
        'class' => [RESPONSIVE_PRIORITY_LOW],
        'width' => '15%',
      ],
    ];

    $build = [];
    foreach ($modules as $module_name => $module_info) {
      $package = $module_info['package'];
      // Skip test modules.
      if ($package === 'Schema.org Blueprints Test') {
        continue;
      }

      // Build package details widget.
      if (!isset($build[$package])) {
        $build[$package] = [
          '#type' => 'details',
          '#title' => $package,
          '#open' => TRUE,
          'table' => [
            '#type' => 'table',
            '#header' => $header,
          ],
        ];
      }

      $row = [];
      if ($this->moduleHandler->moduleExists($module_name)) {
        $row['title'] = [
          'data' => [
            'name' => [
              '#type' => 'link',
              '#url' => Url::fromRoute('schemadotorg_help.page', ['name' => $module_name]),
              '#title' => $module_info['name'],
              '#prefix' => '<strong>',
              '#suffix' => '</strong><br/>',
            ],
            'description' => ['#markup' => $module_info['description']],
          ],
        ];
        $row['status'] = $this->t('Installed');
      }
      else {
        $row['title'] = [
          'data' => [
            'name' => [
              '#markup' => $module_info['name'],
              '#prefix' => '<strong>',
              '#suffix' => '</strong><br/>',
            ],
            'description' => ['#markup' => $module_info['description']],
          ],
        ];
        $row['status'] = '';
      }
      $row['production'] = (empty($module_info['schemadotorg_production']))
        ? ''
        : $this->t('Yes');
      $path = $this->moduleExtensionList->getPath($module_name);
      if (!str_contains($module_name, '_jsonld')
        && file_exists("$path/$module_name.module")
        && str_contains(file_get_contents("$path/$module_name.module"), '_schemadotorg_jsonld_')) {
        $row['jsonld'] = $this->t('Yes');
      }
      else {
        $row['jsonld'] = '';
      }

      if ($this->moduleHandler->moduleExists($module_name)
        && !empty($module_info['configure'])) {
        $row['configuration'] = [
          'data' => [
            '#type' => 'link',
            '#url' => Url::fromRoute($module_info['configure']),
            '#title' => $this->t('Configure'),
            '#attributes' => [
              'style' => 'min-width:5rem',
              'class' => ['button', 'button--small', 'button--extrasmall'],
            ],
          ],
        ];
      }
      elseif (!$this->moduleHandler->moduleExists($module_name)) {
        $row['configuration'] = [
          'data' => [
            '#type' => 'link',
            '#url' => Url::fromRoute('system.modules_list'),
            '#title' => $this->t('Install'),
            '#attributes' => [
              'style' => 'min-width:5rem',
              'class' => ['button', 'button--small', 'button--extrasmall'],
            ],
          ],
        ];
      }
      else {
        $row['configuration'] = [];
      }

      $build[$package]['table']['#rows'][$module_name] = $row;
    }

    return $build;
  }

  /**
   * Get Schema.org Blueprints helps topics as operations.
   *
   * @return array
   *   An array of operations.
   */
  protected function getHelpTopicsAsOperations(): array {
    $modules = array_filter($this->moduleExtensionList->getAllInstalledInfo(), function (array $info): bool {
      return str_starts_with($info['package'], 'Schema.org Blueprints');
    });
    ksort($modules);

    $operations = [];
    foreach ($modules as $module_name => $module_info) {
      $title = $module_info['name'];
      $title = str_replace('Schema.org Blueprints ', '', $title);
      $url = Url::fromRoute('schemadotorg_help.page', ['name' => $module_name]);
      $operations[$module_name] = [
        'title' => $title,
        'url' => $url,
      ];
    }

    $operations['schemadotorg']['title'] = $this->t('Learn more about the Schema.org Blueprints modules');

    return $operations;
  }

}
