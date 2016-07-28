<?php

namespace Drupal\Tests\mailhandler_d8\Kernel;

use Drupal\inmail\Entity\AnalyzerConfig;
use Drupal\inmail\ProcessorResult;
use Drupal\inmail\DefaultAnalyzerResult;

/**
 * Tests the Body Analyzer plugin.
 *
 * @group mailhandler_d8
 */
class BodyAnalyzerKernelTest extends AnalyzerTestBase {

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
  }

  /**
   * Tests features of Body Analyzer plugin.
   */
  public function testBodyAnalyzer() {
    $raw_message = $this->getFileContent('eml/Plain.eml');
    /** @var \Drupal\inmail\MIME\MessageInterface $message */
    $message = $this->parser->parseMessage($raw_message);

    $result = new ProcessorResult();
    $body_analyzer = AnalyzerConfig::load('body');

    /** @var \Drupal\mailhandler_d8\Plugin\inmail\Analyzer\BodyAnalyzer $analyzer */
    $analyzer = $this->analyzerManager->createInstance($body_analyzer->getPluginId(), $body_analyzer->getConfiguration());
    $analyzer->analyze($message, $result);

    $result = $result->getAnalyzerResult(DefaultAnalyzerResult::TOPIC);
    $expected_processed_body = <<<EOF
Hello, Drupal!<br />
<br />
--<br />
Milos Bovan<br />
milos@example.com
EOF;

    // New lines are replaced with <br /> HTML tag.
    $this->assertEquals($expected_processed_body, $result->getBody());
  }

}
