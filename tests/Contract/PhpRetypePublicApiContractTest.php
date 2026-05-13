<?php

declare(strict_types=1);

namespace PhpNoobs\PhpRetype\Tests\Contract;

use PhpNoobs\PhpRetype\Application\PhpRetype;
use PhpNoobs\PhpRetype\Application\PhpRetypeTransaction;
use PhpNoobs\PhpRetype\Domain\Retype\Diagnostic\RetypeDiagnosticCollection;
use PhpNoobs\PhpRetype\Domain\Retype\Plan\RetypePlan;
use PhpNoobs\PhpRetype\Domain\Retype\Plan\RetypeResult;
use PhpNoobs\PhpRetype\Domain\Retype\Step\RetypeStepContext;
use PhpNoobs\PhpRetype\Domain\Retype\Step\RetypeStepResult;
use PhpNoobs\PhpRetype\Domain\Retype\Transaction\RetypeTransactionResult;
use PhpNoobs\PhpRetype\Domain\Retype\Transaction\RetypeTransactionStatus;
use PhpNoobs\PhpSource\VirtualPhpSourceFileCollection;
use PHPUnit\Framework\TestCase;

/**
 * Locks the public API surface consumed by direct users and future orchestrators.
 */
final class PhpRetypePublicApiContractTest extends TestCase
{
    /**
     * Ensures the facade public method names remain stable.
     */
    public function testItExposesTheExpectedFacadeMethods(): void
    {
        self::assertSame([
            'beginTransaction',
            'changeClassConstantType',
            'changeEnumBackingType',
            'changeFunctionParameterType',
            'changeFunctionReturnType',
            'changeMethodParameterType',
            'changeMethodReturnType',
            'changePropertyType',
            'executeStep',
            'executeStepClassConstantTypeChange',
            'executeStepEnumBackingTypeChange',
            'executeStepFunctionParameterTypeChange',
            'executeStepFunctionReturnTypeChange',
            'executeStepMethodParameterTypeChange',
            'executeStepMethodReturnTypeChange',
            'executeStepPropertyTypeChange',
            'fromBuild',
            'fromDirectory',
            'planClassConstantTypeChange',
            'planEnumBackingTypeChange',
            'planFunctionParameterTypeChange',
            'planFunctionReturnTypeChange',
            'planMethodParameterTypeChange',
            'planMethodReturnTypeChange',
            'planPropertyTypeChange',
        ], $this->publicMethodNames(PhpRetype::class));
    }

    /**
     * Ensures the transaction public method names remain stable.
     */
    public function testItExposesTheExpectedTransactionMethods(): void
    {
        self::assertSame([
            'changeClassConstantType',
            'changeEnumBackingType',
            'changeFunctionParameterType',
            'changeFunctionReturnType',
            'changeMethodParameterType',
            'changeMethodReturnType',
            'changePropertyType',
            'commit',
            'commitAndSave',
            'commitAndSaveSourceFile',
            'rollback',
            'status',
        ], $this->publicMethodNames(PhpRetypeTransaction::class));
    }

    /**
     * Ensures facade method return types remain stable.
     */
    public function testItExposesTheExpectedFacadeReturnTypes(): void
    {
        foreach ($this->publicMethodNames(PhpRetype::class) as $methodName) {
            if (str_starts_with($methodName, 'plan')) {
                self::assertSame(RetypePlan::class, $this->returnTypeName(PhpRetype::class, $methodName), $methodName);

                continue;
            }

            if (str_starts_with($methodName, 'change')) {
                self::assertSame(RetypeResult::class, $this->returnTypeName(PhpRetype::class, $methodName), $methodName);

                continue;
            }

            if (str_starts_with($methodName, 'executeStep')) {
                self::assertSame(RetypeStepResult::class, $this->returnTypeName(PhpRetype::class, $methodName), $methodName);
            }
        }

        self::assertSame(PhpRetypeTransaction::class, $this->returnTypeName(PhpRetype::class, 'beginTransaction'));
        self::assertSame('self', $this->returnTypeName(PhpRetype::class, 'fromBuild'));
        self::assertSame('self', $this->returnTypeName(PhpRetype::class, 'fromDirectory'));
    }

    /**
     * Ensures transaction method return types remain stable.
     */
    public function testItExposesTheExpectedTransactionReturnTypes(): void
    {
        foreach ($this->publicMethodNames(PhpRetypeTransaction::class) as $methodName) {
            if (str_starts_with($methodName, 'change')) {
                self::assertSame(RetypeResult::class, $this->returnTypeName(PhpRetypeTransaction::class, $methodName), $methodName);

                continue;
            }

            if ('status' === $methodName) {
                self::assertSame(RetypeTransactionStatus::class, $this->returnTypeName(PhpRetypeTransaction::class, $methodName), $methodName);

                continue;
            }

            self::assertSame(RetypeTransactionResult::class, $this->returnTypeName(PhpRetypeTransaction::class, $methodName), $methodName);
        }
    }

    /**
     * Ensures property retype methods keep accepting one or multiple property names.
     */
    public function testItKeepsPropertyNameInputsAsStringOrArray(): void
    {
        self::assertSame(['array', 'string'], $this->parameterUnionTypeNames(PhpRetype::class, 'planPropertyTypeChange', 'propertyNames'));
        self::assertSame(['array', 'string'], $this->parameterUnionTypeNames(PhpRetype::class, 'changePropertyType', 'propertyNames'));
        self::assertSame(['array', 'string'], $this->parameterUnionTypeNames(PhpRetype::class, 'executeStepPropertyTypeChange', 'propertyNames'));
        self::assertSame(['array', 'string'], $this->parameterUnionTypeNames(PhpRetypeTransaction::class, 'changePropertyType', 'propertyNames'));
    }

    /**
     * Ensures step context remains an immutable orchestration DTO.
     */
    public function testItExposesStableRetypeStepContextProperties(): void
    {
        $reflection = new \ReflectionClass(RetypeStepContext::class);

        self::assertTrue($reflection->isReadOnly());
        self::assertSame([
            'currentBuild',
        ], $this->publicPropertyNames(RetypeStepContext::class));
    }

    /**
     * Ensures step result remains an immutable orchestration DTO.
     */
    public function testItExposesStableRetypeStepResultProperties(): void
    {
        $reflection = new \ReflectionClass(RetypeStepResult::class);

        self::assertTrue($reflection->isReadOnly());
        self::assertSame([
            'applied',
            'context',
            'diagnostics',
            'plan',
            'requiresGraphRefresh',
            'retypeResult',
            'touchedFiles',
        ], $this->publicPropertyNames(RetypeStepResult::class));

        self::assertSame(RetypeStepContext::class, $this->propertyTypeName(RetypeStepResult::class, 'context'));
        self::assertSame(RetypePlan::class, $this->propertyTypeName(RetypeStepResult::class, 'plan'));
        self::assertSame(RetypeResult::class, $this->propertyTypeName(RetypeStepResult::class, 'retypeResult'));
        self::assertSame(RetypeDiagnosticCollection::class, $this->propertyTypeName(RetypeStepResult::class, 'diagnostics'));
        self::assertSame(VirtualPhpSourceFileCollection::class, $this->propertyTypeName(RetypeStepResult::class, 'touchedFiles'));
        self::assertSame('bool', $this->propertyTypeName(RetypeStepResult::class, 'applied'));
        self::assertSame('bool', $this->propertyTypeName(RetypeStepResult::class, 'requiresGraphRefresh'));
    }

    /**
     * Returns public methods declared directly on a class.
     *
     * @param class-string $className the class name to inspect
     *
     * @return list<string>
     */
    private function publicMethodNames(string $className): array
    {
        $reflection = new \ReflectionClass($className);
        $methodNames = [];

        foreach ($reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->getDeclaringClass()->getName() !== $className) {
                continue;
            }

            if ($method->isConstructor()) {
                continue;
            }

            $methodNames[] = $method->getName();
        }

        sort($methodNames);

        return $methodNames;
    }

    /**
     * Returns public properties declared on a class.
     *
     * @param class-string $className the class name to inspect
     *
     * @return list<string>
     */
    private function publicPropertyNames(string $className): array
    {
        $reflection = new \ReflectionClass($className);
        $propertyNames = [];

        foreach ($reflection->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
            $propertyNames[] = $property->getName();
        }

        sort($propertyNames);

        return $propertyNames;
    }

    /**
     * Returns the named return type for one method.
     *
     * @param class-string $className  the class name to inspect
     * @param string       $methodName the method name to inspect
     */
    private function returnTypeName(string $className, string $methodName): string
    {
        $returnType = new \ReflectionMethod($className, $methodName)->getReturnType();

        self::assertInstanceOf(\ReflectionNamedType::class, $returnType);

        return $returnType->getName();
    }

    /**
     * Returns the named type for one property.
     *
     * @param class-string $className    the class name to inspect
     * @param string       $propertyName the property name to inspect
     */
    private function propertyTypeName(string $className, string $propertyName): string
    {
        $propertyType = new \ReflectionProperty($className, $propertyName)->getType();

        self::assertInstanceOf(\ReflectionNamedType::class, $propertyType);

        return $propertyType->getName();
    }

    /**
     * Returns sorted union type names for one parameter.
     *
     * @param class-string $className     the class name to inspect
     * @param string       $methodName    the method name to inspect
     * @param string       $parameterName the parameter name to inspect
     *
     * @return list<string>
     */
    private function parameterUnionTypeNames(string $className, string $methodName, string $parameterName): array
    {
        $parameter = new \ReflectionParameter([$className, $methodName], $parameterName);
        $parameterType = $parameter->getType();

        self::assertInstanceOf(\ReflectionUnionType::class, $parameterType);

        $typeNames = [];

        foreach ($parameterType->getTypes() as $type) {
            self::assertInstanceOf(\ReflectionNamedType::class, $type);

            $typeNames[] = $type->getName();
        }

        sort($typeNames);

        return $typeNames;
    }
}
