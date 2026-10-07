<?php

declare(strict_types=1);

use Symplify\EasyCodingStandard\Config\ECSConfig;
use PhpCsFixer\Fixer\ArrayNotation\ArraySyntaxFixer;
use PhpCsFixer\Fixer\Import\NoUnusedImportsFixer;
use PhpCsFixer\Fixer\Strict\DeclareStrictTypesFixer;
use PhpCsFixer\Fixer\Basic\BracesFixer;
use PhpCsFixer\Fixer\ClassNotation\ClassAttributesSeparationFixer;
use PhpCsFixer\Fixer\ControlStructure\NoUselessElseFixer;
use PhpCsFixer\Fixer\FunctionNotation\ReturnTypeDeclarationFixer;
use PhpCsFixer\Fixer\Import\OrderedImportsFixer;
use PhpCsFixer\Fixer\Operator\ConcatSpaceFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocOrderFixer;
use PhpCsFixer\Fixer\Whitespace\BlankLineBeforeStatementFixer;

return ECSConfig::configure()
    ->withPaths([
        __DIR__ . '/app',
        __DIR__ . '/config',
        __DIR__ . '/database',
        __DIR__ . '/routes',
        __DIR__ . '/tests',
    ])
    ->withSkip([
        __DIR__ . '/bootstrap/cache',
        __DIR__ . '/storage',
        __DIR__ . '/vendor',
        '*/migrations/*',
    ])
    ->withPreparedSets(psr12: true)
    ->withRules([
        ArraySyntaxFixer::class,
        NoUnusedImportsFixer::class,
        BracesFixer::class,
        NoUselessElseFixer::class,
        ReturnTypeDeclarationFixer::class,
        OrderedImportsFixer::class,
        PhpdocOrderFixer::class,
    ])
    ->withConfiguredRule(ArraySyntaxFixer::class, [
        'syntax' => 'short',
    ])
    ->withConfiguredRule(ConcatSpaceFixer::class, [
        'spacing' => 'one',
    ])
    ->withConfiguredRule(ClassAttributesSeparationFixer::class, [
        'elements' => ['method' => 'one'],
    ])
    ->withConfiguredRule(BlankLineBeforeStatementFixer::class, [
        'statements' => ['return'],
    ])
    ->withConfiguredRule(OrderedImportsFixer::class, [
        'sort_algorithm' => 'alpha',
    ]);