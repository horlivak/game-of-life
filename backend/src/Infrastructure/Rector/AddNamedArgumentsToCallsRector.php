<?php

declare(strict_types=1);

namespace App\Infrastructure\Rector;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParameterReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use Rector\Rector\AbstractRector;
use Rector\Reflection\ReflectionResolver;
use Rector\ValueObject\PhpVersion;
use Rector\VersionBonding\Contract\MinPhpVersionInterface;
use ReflectionException;
use ReflectionFunction;
use ReflectionParameter;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

final class AddNamedArgumentsToCallsRector extends AbstractRector implements MinPhpVersionInterface
{
	public function __construct(
		private readonly ReflectionResolver $reflectionResolver,
	) {
	}

	public function getRuleDefinition(): RuleDefinition
	{
		return new RuleDefinition(description: 'Add named arguments to function/method/static calls', codeSamples: [
			new CodeSample(
				badCode: <<<'CODE_SAMPLE'
					strlen('hello');
					CODE_SAMPLE
				,
				goodCode: <<<'CODE_SAMPLE'
					strlen(string: 'hello');
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
		return [FuncCall::class, MethodCall::class, StaticCall::class];
	}

	public function refactor(Node $node): ?Node
	{
		if (!$node instanceof FuncCall && !$node instanceof MethodCall && !$node instanceof StaticCall) {
			return null;
		}

		if (!$this->shouldProcess(node: $node)) {
			return null;
		}

		$reflection = $this->reflectionResolver->resolveFunctionLikeReflectionFromCall(callLike: $node);

		if (!$reflection instanceof FunctionReflection && !$reflection instanceof MethodReflection) {
			return null;
		}

		if ($this->hasNoNamedArguments(reflection: $reflection)) {
			return null;
		}

		return $this->addNamedArguments(node: $node, reflection: $reflection);
	}

	public function provideMinPhpVersion(): int
	{
		return PhpVersion::PHP_80;
	}

	private function shouldProcess(FuncCall|MethodCall|StaticCall $node): bool
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

		return !$this->isDynamicCall(node: $node);
	}

	private function addNamedArguments(
		FuncCall|MethodCall|StaticCall $node,
		FunctionReflection|MethodReflection $reflection,
	): ?Node {
		$parameters = ParametersAcceptorSelector::combineAcceptors(acceptors: $reflection->getVariants())->getParameters();

		if ($parameters === []) {
			return null;
		}

		if ($this->hasVariadicParameter(parameters: $parameters)) {
			return null;
		}

		$nativeParamNames = $this->resolveNativeParamNames(node: $node);

		$hasChanged = false;

		foreach ($node->getArgs() as $index => $arg) {
			if ($arg->name instanceof Identifier) {
				continue;
			}

			if (!array_key_exists(key: $index, array: $parameters)) {
				break;
			}

			$paramName = $nativeParamNames[$index] ?? $parameters[$index]->getName();
			$arg->name = new Identifier(name: $paramName);
			$hasChanged = true;
		}

		return $hasChanged ? $node : null;
	}

	/**
	 * @param list<ParameterReflection> $parameters
	 */
	private function hasVariadicParameter(array $parameters): bool
	{
		foreach ($parameters as $parameter) {
			if ($parameter->isVariadic()) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @return list<string>|null
	 */
	private function resolveNativeParamNames(FuncCall|MethodCall|StaticCall $node): ?array
	{
		if (!$node instanceof FuncCall || !$node->name instanceof Name) {
			return null;
		}

		try {
			$rf = new ReflectionFunction(function: $node->name->toString());
		} catch (ReflectionException) {
			return null;
		}

		if (!$rf->isInternal()) {
			return null;
		}

		return array_map(static fn (ReflectionParameter $p): string => $p->getName(), $rf->getParameters());
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

	private function hasNoNamedArguments(FunctionReflection|MethodReflection $reflection): bool
	{
		$docComment = $reflection->getDocComment();

		if ($docComment !== null && str_contains(haystack: $docComment, needle: '@no-named-arguments')) {
			return true;
		}

		if ($reflection instanceof MethodReflection) {
			$classDoc = $reflection->getDeclaringClass()
				->getNativeReflection()
				->getDocComment();

			if ($classDoc !== false && str_contains(haystack: $classDoc, needle: '@no-named-arguments')) {
				return true;
			}
		}

		return false;
	}

	private function isDynamicCall(FuncCall|MethodCall|StaticCall $node): bool
	{
		if ($node instanceof FuncCall) {
			return !$node->name instanceof Name;
		}

		return !$node->name instanceof Identifier;
	}
}
