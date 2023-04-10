<?php

declare(strict_types = 1);

namespace Drupal\schemadotorg;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Extension\ExtensionPathResolver;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\help\HelpSectionManager;

/**
 * Schema.org help manager service.
 */
class SchemaDotOrgHelpManager implements SchemaDotOrgHelpManagerInterface {
  use StringTranslationTrait;

  /**
   * Constructs a SchemaDotOrgMappingManager object.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler.
   * @param \Drupal\Core\Extension\ModuleExtensionList $moduleExtensionList
   *   The module extension list.
   * @param \Drupal\Core\Extension\ExtensionPathResolver $extensionPathResolver
   *   The extension path resolver.
   * @param \Drupal\help\HelpSectionManager|null $helpManager
   *   The help section manager.
   */
  public function __construct(
    protected ModuleHandlerInterface $moduleHandler,
    protected ModuleExtensionList $moduleExtensionList,
    protected ExtensionPathResolver $extensionPathResolver,
    protected ?HelpSectionManager $helpManager = NULL
  ) {}

  /**
   * {@inheritdoc}
   */
  public function buildHelpPage(string $route_name, RouteMatchInterface $route_match): ?array {
    if (!str_starts_with($route_name, 'help.page.schemadotorg')) {
      return NULL;
    }

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
      '#url' => Url::fromRoute('schemadotorg.help.videos'),
      '#attributes' => [
        'class' => ['use-ajax', 'button', 'button--small', 'button--extrasmall'],
        'data-dialog-type' => 'modal',
        'data-dialog-options' => Json::encode([
          'width' => 800,
        ]),
      ],
    ];

    // README.md.
    $module_name = str_replace('help.page.', '', $route_name);

    $module_readme = $this->extensionPathResolver->getPath('module', $module_name) . '/README.md';
    if (!file_exists($module_readme)) {
      return $build;
    }

    $contents = file_get_contents($module_readme);

    // Remove the table of contents.
    $contents = preg_replace('/^.*?(Introduction\s+------------)/s', '$1', $contents);

    if (class_exists('\Michelf\Markdown')) {
      $build['readme'] = [
        // phpcs:ignore Drupal.Classes.FullyQualifiedNamespace.UseStatementMissing
        '#markup' => \Michelf\Markdown::defaultTransform($contents),
      ];
    }
    else {
      $build['readme'] = [
        '#plain_text' => $contents,
        '#prefix' => '<pre>',
        '#suffix' => '</pre>',
      ];
    }

    if ($module_name === 'schemadotorg') {
      $build['modules'] = $this->buildModules();
    }

    return $build;
  }

  /**
   * Get a module's video as a renderable array.
   *
   * @return array
   *   A module's videos as a renderable array.
   */
  public function buildVideosPage(): array {
    // Videos.
    $videos = [
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

    $packages = [];
    foreach ($modules as $module_name => $module_info) {
      $package = $module_info['package'];
      if (!isset($packages[$package])) {
        $packages[$package] = [
          'title' => [
            '#markup' => $package,
            '#prefix' => '<h3>',
            '#suffix' => '</h3>',
          ],
          'modules' => [],
        ];
      }

      if ($this->moduleHandler->moduleExists($module_name)) {
        $name = [
          '#type' => 'link',
          '#url' => Url::fromRoute('help.page', ['name' => $module_name]),
          '#title' => $module_info['name'],
          '#prefix' => '<strong>',
          '#suffix' => '</strong><br/>',
        ];
      }
      else {
        $name = [
          '#markup' => $module_info['name'],
          '#prefix' => '<strong>',
          '#suffix' => '</strong><br/>',
        ];
      }

      $packages[$package]['modules'][$module_name] = [
        '#prefix' => '<p>',
        '#suffix' => '</p>',
        'name' => $name,
        'description' => ['#markup' => $module_info['description']],
      ];

      $path = $this->moduleExtensionList->getPath($module_name);
      if (!str_contains($module_name, '_jsonld')
        && file_exists("$path/$module_name.module")
        && str_contains(file_get_contents("$path/$module_name.module"), '_schemadotorg_jsonld_')) {
        $packages[$package]['modules'][$module_name]['jsonld'] = [
          '#markup' => $this->t('Provides Schema.org JSON-LD integration.'),
          '#prefix' => '<br/><em>',
          '#suffix' => '</em>',
        ];
      }
    }
    ksort($packages);
    $packages = ['Schema.org Blueprints Core' => $packages['Schema.org Blueprints Core']] + $packages;

    return [
      '#type' => 'details',
      '#title' => $this->t('Learn more about the Schema.org Blueprints modules'),
      'packages' => $packages,
    ];
  }

  /**
   * Get Schema.org Blueprints helps topics as operations.
   *
   * @return array
   *   An array of operations.
   */
  protected function getHelpTopicsAsOperations(): array {
    /** @var \Drupal\help\HelpSectionPluginInterface $plugin */
    $plugin = $this->helpManager->createInstance('hook_help');

    /** @var \Drupal\Core\Link[] $links */
    $links = $plugin->listTopics();

    $operations = [];
    foreach ($links as $link) {
      $route_parameters = $link->getUrl()->getRouteParameters();
      if (str_starts_with($route_parameters['name'], 'schemadotorg')) {
        $operations[$route_parameters['name']] = [
          'title' => $link->getText(),
          'url' => $link->getUrl(),
        ];
      }
    }

    $operations['schemadotorg']['title'] = $this->t('Learn more about the Schema.org Blueprints modules');

    return $operations;
  }

}
