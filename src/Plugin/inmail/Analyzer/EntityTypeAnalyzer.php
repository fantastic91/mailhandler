<?php

namespace Drupal\mailhandler_d8\Plugin\inmail\Analyzer;

use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\inmail\DefaultAnalyzerResult;
use Drupal\inmail\DefaultAnalyzerResultInterface;
use Drupal\inmail\MIME\MessageInterface;
use Drupal\inmail\Plugin\inmail\Analyzer\AnalyzerBase;
use Drupal\inmail\ProcessorResultInterface;

/**
 * An entity type and bundle analyzer.
 *
 * @ingroup analyzer
 *
 * @Analyzer(
 *   id = "entity_type",
 *   label = @Translation("Entity type and bundle Analyzer")
 * )
 */
class EntityTypeAnalyzer extends AnalyzerBase {

  /**
   * {@inheritdoc}
   */
  public function analyze(MessageInterface $message, ProcessorResultInterface $processor_result) {
    $result = $processor_result->getAnalyzerResult(DefaultAnalyzerResult::TOPIC);

    $this->findEntityType($message, $result);
  }

  /**
   * Analyzes the message subject.
   *
   * @param \Drupal\inmail\MIME\MessageInterface $message
   *   The mail message.
   * @param \Drupal\inmail\DefaultAnalyzerResultInterface $result
   *   The analyzed result.
   */
  protected function findEntityType(MessageInterface $message, DefaultAnalyzerResultInterface $result) {
    $subject = $result->getSubject() ?: $message->getSubject();
    $entity_type = NULL;
    $bundle = NULL;

    // Match entity type.
    if (preg_match('/^\[(\w+)\]/', $subject, $matches)) {
      $entity_type = \Drupal::entityTypeManager()->hasDefinition($matches[1]) ? $matches[1] : NULL;
      $subject = str_replace(reset($matches), '', $subject);
      // In case entity type was identified successfully, continue to bundle.
      if ($entity_type && preg_match('/^\[(\w+)\]\s+/', $subject, $matches)) {
        $bundle = $this->getBundle($entity_type, $matches[1]);
        $subject = str_replace(reset($matches), '', $subject);
      }
    }

    // Add entity type context.
    $context_data = [
      'entity_type' => $entity_type,
      'bundle' => $bundle,
    ];
    $context_definition = new ContextDefinition('any', $this->t('Entity type context'));
    $context = new Context($context_definition, $context_data);
    $result->addContext('entity_type', $context);

    $result->setSubject($subject);
  }

  /**
   * Returns the extracted bundle or null if it is not valid.
   *
   * @param string $entity_type
   *   The extracted entity type.
   * @param string $bundle
   *   The extracted bundle.
   *
   * @return string|null
   *   The bundle or null.
   */
  protected function getBundle($entity_type, $bundle) {
    if (\Drupal::entityTypeManager()->getDefinition($entity_type, FALSE)->hasKey('bundle')) {
      $bundles = \Drupal::service('entity_type.bundle.info')->getBundleInfo($entity_type);
      if (in_array($bundle, array_keys($bundles))) {
        return $bundle;
      }
    }
    return NULL;
  }

}
