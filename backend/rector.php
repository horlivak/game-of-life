<?php

declare(strict_types=1);

use App\Infrastructure\Rector\AddNamedArgumentsToCallsRector;
use App\Infrastructure\Rector\AddNamedArgumentsToConstructorRector;
use App\Infrastructure\Rector\PromoteConstructorPropertyRector;
use Rector\CodeQuality\Rector\Identical\FlipTypeControlToUseExclusiveTypeRector;
use Rector\Config\RectorConfig;
use Rector\Php80\Rector\Class_\ClassPropertyAssignToConstructorPromotionRector;
use Rector\PHPUnit\AnnotationsToAttributes\Rector\Class_\CoversAnnotationWithValueToAttributeRector;
use Rector\TypeDeclaration\Rector\ClassMethod\ReturnNeverTypeRector;

return RectorConfig::configure()
	->withPreparedSets(
		deadCode: true,
		codeQuality: true,
		codingStyle: true,
		typeDeclarations: true,
		privatization: true,
		instanceOf: true,
		earlyReturn: true,
	)
	->withAttributesSets(symfony: true, doctrine: true, phpunit: true, sensiolabs: true)
	->withSets(sets: [])
	->withImportNames()
	->withPhpSets()
	->withPaths(
		paths: [__DIR__ . '/bin', __DIR__ . '/src', __DIR__ . '/tests', __DIR__ . '/rector.php', __DIR__ . '/ecs.php'],
	)
	->withRootFiles()
	->withSymfonyContainerXml(symfonyContainerXmlFile: __DIR__ . '/var/cache/dev/App_KernelDevDebugContainer.xml')
	->withParallel()
	->withRules(rules: [
		AddNamedArgumentsToCallsRector::class,
		AddNamedArgumentsToConstructorRector::class,
		PromoteConstructorPropertyRector::class,
	])
	->withSkip(skip: [
		'tests/bootstrap.php',
		FlipTypeControlToUseExclusiveTypeRector::class,
		ReturnNeverTypeRector::class => [__DIR__ . '/tests/'],
		CoversAnnotationWithValueToAttributeRector::class,
		ClassPropertyAssignToConstructorPromotionRector::class,
	]);
