<?php

declare(strict_types=1);

namespace App\Infrastructure\Rector;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use Rector\Rector\AbstractRector;
use Rector\Reflection\ReflectionResolver;
use Rector\ValueObject\PhpVersion;
use Rector\VersionBonding\Contract\MinPhpVersionInterface;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

final class AddNamedArgumentsToConstructorRector extends AbstractRector implements MinPhpVersionInterface
{
	public function __construct(
		private readonly ReflectionResolver $reflectionResolver,
	) {
	}

	public function getRuleDefinition(): RuleDefinition
	{
		return new RuleDefinition(description: 'Add named arguments to constructor calls (new)', codeSamples: [
			new CodeSample(
				badCode: <<<'CODE_SAMPLE'
					new Foo('bar', 123);
					CODE_SAMPLE
				,
				goodCode: <<<'CODE_SAMPLE'
					new Foo(name: 'bar', count: 123);
					CODE_SAMPLE
				,
			),
		]);
	}

	/**
	 * @return array<class-string<Node>>
	 */
	public function getNodeTypes(): array
	{
		return [New_::class];
	}

	public function refactor(Node $node): ?Node
	{
		if (!$node instanceof New_) {
			return null;
		}

		if (!$this->shouldProcess(node: $node)) {
			return null;
		}

		$methodReflection = $this->reflectionResolver->resolveMethodReflectionFromNew(new: $node);

		if (!$methodReflection instanceof MethodReflection) {
			return null;
		}

		return $this->addNamedArguments(node: $node, reflection: $methodReflection);
	}

	public function provideMinPhpVersion(): int
	{
		return PhpVersion::PHP_80;
	}

	private function shouldProcess(New_ $node): bool
	{
		if ($node->isFirstClassCallable()) {
			return false;
		}

		$args = $node->getArgs();

		if ($args === []) {
			return false;
		}

		if ($this->allArgsNamed(args: $args)) {
			return false;
		}

		if ($this->hasSpreadArg(args: $args)) {
			return false;
		}

		return $node->class instanceof Name;
	}

	private function addNamedArguments(New_ $node, MethodReflection $reflection): ?Node
	{
		$parameters = ParametersAcceptorSelector::combineAcceptors(acceptors: $reflection->getVariants())->getParameters();

		if ($parameters === []) {
			return null;
		}

		$hasChanged = false;

		foreach ($node->getArgs() as $index => $arg) {
			if ($arg->name instanceof Identifier) {
				continue;
			}

			if (!array_key_exists(key: $index, array: $parameters)) {
				break;
			}

			if ($parameters[$index]->isVariadic()) {
				break;
			}

			$arg->name = new Identifier(name: $parameters[$index]->getName());
			$hasChanged = true;
		}

		return $hasChanged ? $node : null;
	}

	/**
	 * @param Arg[] $args
	 */
	private function allArgsNamed(array $args): bool
	{
		foreach ($args as $arg) {
			if (!$arg->name instanceof Identifier) {
				return false;
			}
		}

		return true;
	}

	/**
	 * @param Arg[] $args
	 */
	private function hasSpreadArg(array $args): bool
	{
		foreach ($args as $arg) {
			if ($arg->unpack) {
				return true;
			}
		}

		return false;
	}
}
