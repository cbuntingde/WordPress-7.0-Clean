<?php

/**
 * For WordPress projects, DO NOT use PHP CS Fixer.
 * Use PHPCS + PHPCBF for WordPress Coding Standards instead.
 *
 * This config exists for any custom code you add.
 */
$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__ . '/tests')
    ->exclude('vendor')
    ->notName('*~');

return (new PhpCsFixer\Config())
    ->setFinder($finder)
    ->setRules([
        '@PSR12' => true,
    ])
    ->setRiskyAllowed(false);