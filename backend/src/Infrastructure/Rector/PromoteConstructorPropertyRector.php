<?php

declare(strict_types=1);

namespace App\Infrastructure\Rector;

use PhpParser\Modifiers;
use PhpParser\Node;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Param;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\Property;
use Rector\Rector\AbstractRector;
use Rector\ValueObject\MethodName;
use Rector\ValueObject\PhpVersion;
use Rector\VersionBonding\Contract\MinPhpVersionInterface;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

final class PromoteConstructorPropertyRector extends AbstractRector implements MinPhpVersionInterface
{
	public function getRuleDefinition(): RuleDefinition
	{
		return new RuleDefinition(description: 'Promote class properties to constructor property promotion', codeSamples: [
			new CodeSample(
				badCode: <<<'CODE_SAMPLE'
					class Foo
					{
						private string $name;

						public function __construct(string $name)
						{
							$this->name = $name;
						}
					}
					CODE_SAMPLE
				,
				goodCode: <<<'CODE_SAMPLE'
					class Foo
					{
						public function __construct(
							private string $name,
						) {
						}
					}
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
		return [Class_::class];
	}

	public function refactor(Node $node): ?Node
	{
		if (!$node instanceof Class_) {
			return null;
		}

		$constructor = $node->getMethod(name: MethodName::CONSTRUCT);

		if (!$constructor instanceof ClassMethod || $constructor->params === []) {
			return null;
		}

		$properties = $this->getClassProperties(class: $node);

		if ($properties === []) {
			return null;
		}

		$hasChanged = $this->promoteProperties(class: $node, constructor: $constructor, properties: $properties);

		if (!$hasChanged) {
			return null;
		}

		$constructor->stmts = array_values(array: $constructor->stmts ?? []);

		return $node;
	}

	public function provideMinPhpVersion(): int
	{
		return PhpVersion::PHP_80;
	}

	/**
	 * @param array<string, Property> $properties
	 */
	private function promoteProperties(Class_ $class, ClassMethod $constructor, array $properties): bool
	{
		$hasChanged = false;

		foreach ($constructor->params as $param) {
			$paramName = $this->getName(node: $param->var);

			if ($paramName === null) {
				continue;
			}

			if ($param->flags !== 0) {
				continue;
			}

			if (!$this->canPromoteParam(paramName: $paramName, properties: $properties, constructor: $constructor)) {
				continue;
			}

			$this->applyPromotion(
				param: $param,
				paramName: $paramName,
				property: $properties[$paramName],
				class: $class,
				constructor: $constructor
			);
			$hasChanged = true;
		}

		return $hasChanged;
	}

	/**
	 * @param array<string, Property> $properties
	 */
	private function canPromoteParam(string $paramName, array $properties, ClassMethod $constructor): bool
	{
		if (!array_key_exists(key: $paramName, array: $properties)) {
			return false;
		}

		$property = $properties[$paramName];

		if ($this->shouldSkipProperty(property: $property)) {
			return false;
		}

		$assignStmtIndex = $this->findAssignmentIndex(constructor: $constructor, paramName: $paramName);

		if ($assignStmtIndex === null) {
			return false;
		}

		return !$this->isPropertyAssignedMultipleTimes(constructor: $constructor, paramName: $paramName);
	}

	private function applyPromotion(
		Param $param,
		string $paramName,
		Property $property,
		Class_ $class,
		ClassMethod $constructor,
	): void {
		$param->flags = $property->flags;

		if ($param->type === null && $property->type instanceof Node) {
			$param->type = $property->type;
		}

		if ($property->attrGroups !== []) {
			$param->attrGroups = array_merge($param->attrGroups, $property->attrGroups);
		}

		$this->removePropertyFromClass(class: $class, property: $property);

		$assignStmtIndex = $this->findAssignmentIndex(constructor: $constructor, paramName: $paramName);

		if ($assignStmtIndex !== null) {
			unset($constructor->stmts[$assignStmtIndex]);
		}
	}

	/**
	 * @return array<string, Property>
	 */
	private function getClassProperties(Class_ $class): array
	{
		$properties = [];

		foreach ($class->stmts as $stmt) {
			if (!$stmt instanceof Property) {
				continue;
			}

			if (count(value: $stmt->props) !== 1) {
				continue;
			}

			$propertyName = $this->getName(node: $stmt->props[0]);
			$properties[$propertyName] = $stmt;
		}

		return $properties;
	}

	private function shouldSkipProperty(Property $property): bool
	{
		if ($property->isStatic()) {
			return true;
		}

		if ($property->hooks !== []) {
			return true;
		}

		return ($property->flags & (Modifiers::PUBLIC | Modifiers::PROTECTED | Modifiers::PRIVATE)) === 0;
	}

	private function findAssignmentIndex(ClassMethod $constructor, string $paramName): ?int
	{
		foreach ($constructor->stmts ?? [] as $index => $stmt) {
			if ($this->isSimplePropertyAssignment(stmt: $stmt, paramName: $paramName)) {
				return $index;
			}
		}

		return null;
	}

	private function isSimplePropertyAssignment(Node $stmt, string $paramName): bool
	{
		if (!$stmt instanceof Expression) {
			return false;
		}

		if (!$stmt->expr instanceof Assign) {
			return false;
		}

		$assign = $stmt->expr;

		if (!$this->isThisPropertyFetch(node: $assign->var, propertyName: $paramName)) {
			return false;
		}

		return $assign->expr instanceof Variable && $this->isName(node: $assign->expr, name: $paramName);
	}

	private function isThisPropertyFetch(Node $node, string $propertyName): bool
	{
		if (!$node instanceof PropertyFetch) {
			return false;
		}

		if (!$node->var instanceof Variable || !$this->isName(node: $node->var, name: 'this')) {
			return false;
		}

		return $this->isName(node: $node->name, name: $propertyName);
	}

	private function isPropertyAssignedMultipleTimes(ClassMethod $constructor, string $paramName): bool
	{
		$count = 0;

		foreach ($constructor->stmts ?? [] as $stmt) {
			if (!$this->isThisPropertyAssignment(stmt: $stmt, paramName: $paramName)) {
				continue;
			}

			++$count;

			if ($count > 1) {
				return true;
			}
		}

		return false;
	}

	private function isThisPropertyAssignment(Node $stmt, string $paramName): bool
	{
		if (!$stmt instanceof Expression || !$stmt->expr instanceof Assign) {
			return false;
		}

		return $this->isThisPropertyFetch(node: $stmt->expr->var, propertyName: $paramName);
	}

	private function removePropertyFromClass(Class_ $class, Property $property): void
	{
		foreach ($class->stmts as $index => $stmt) {
			if ($stmt === $property) {
				unset($class->stmts[$index]);
				$class->stmts = array_values(array: $class->stmts);

				return;
			}
		}
	}
}
