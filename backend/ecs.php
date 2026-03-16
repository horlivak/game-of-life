<?php

declare(strict_types=1);

use PhpCsFixer\Fixer\ControlStructure\YodaStyleFixer;
use PhpCsFixer\Fixer\Import\NoUnusedImportsFixer;
use PhpCsFixer\Fixer\Operator\ConcatSpaceFixer;
use PhpCsFixer\Fixer\Operator\NotOperatorWithSuccessorSpaceFixer;
use PhpCsFixer\Fixer\Phpdoc\GeneralPhpdocAnnotationRemoveFixer;
use PhpCsFixer\Fixer\PhpUnit\PhpUnitInternalClassFixer;
use PhpCsFixer\Fixer\PhpUnit\PhpUnitStrictFixer;
use PhpCsFixer\Fixer\PhpUnit\PhpUnitTestClassRequiresCoversFixer;
use PhpCsFixer\Fixer\Whitespace\BlankLineBeforeStatementFixer;
use Symplify\EasyCodingStandard\Config\ECSConfig;
use Symplify\EasyCodingStandard\ValueObject\Option;

return ECSConfig::configure()
	->withPreparedSets(
		psr12: true,
		symplify: true,
		arrays: true,
		comments: true,
		docblocks: true,
		spaces: true,
		namespaces: true,
		controlStructures: true,
		phpunit: true,
		strict: true,
		cleanCode: true,
	)
	->withPaths([__DIR__ . '/src', __DIR__ . '/tests', __DIR__ . '/ecs.php', __DIR__ . '/rector.php'])
	->withRules([NoUnusedImportsFixer::class, BlankLineBeforeStatementFixer::class])
	->withSkip([
		PhpUnitInternalClassFixer::class,
		PhpUnitTestClassRequiresCoversFixer::class,
		GeneralPhpdocAnnotationRemoveFixer::class,
		NotOperatorWithSuccessorSpaceFixer::class,
		PhpUnitStrictFixer::class,
	])
	->withSpacing(Option::INDENTATION_TAB)
	->withParallel()
	->withConfiguredRule(ConcatSpaceFixer::class, [
		'spacing' => 'one',
	])
	->withConfiguredRule(YodaStyleFixer::class, [
		'equal' => false,
		'identical' => false,
		'less_and_greater' => false,
	]);
