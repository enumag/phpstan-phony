<?php

declare(strict_types=1);

namespace Eloquent\Phpstan\Phony\Type;

use Eloquent\Phony\Kahlan\Phony as KahlanPhony;
use Eloquent\Phony\Pho\Phony as PhoPhony;
use Eloquent\Phony\Phony;
use Eloquent\Phony\Phpunit\Phony as PhpunitPhony;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptor;
use PHPStan\Type\DynamicClassReturnTypeExtension;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\DynamicStaticMethodReturnTypeExtension;
use PHPStan\Type\Type;

final class MockBuilderReturnType implements
    DynamicClassReturnTypeExtension,
    DynamicFunctionReturnTypeExtension,
    DynamicStaticMethodReturnTypeExtension
{
    public static function getClass(): string
    {
        return Phony::class;
    }

    public function isClassSupported(string $class): bool
    {
        switch ($class) {
            case Phony::class:
            case PhpunitPhony::class:
            case KahlanPhony::class:
            case PhoPhony::class:
                return true;
        }

        return false;
    }

    public function isFunctionSupported(FunctionReflection $reflection): bool
    {
        switch ($reflection->getName()) {
            case 'Eloquent\Phony\mockBuilder':
            case 'Eloquent\Phony\Phpunit\mockBuilder':
            case 'Eloquent\Phony\Kahlan\mockBuilder':
            case 'Eloquent\Phony\Pho\mockBuilder':
                return true;
        }

        return false;
    }

    public function isStaticMethodSupported(MethodReflection $reflection): bool
    {
        return 'mockBuilder' === $reflection->getName();
    }

    public function getTypeFromFunctionCall(
        FunctionReflection $reflection,
        FuncCall $call,
        Scope $scope
    ): Type {
        return $this->getTypeFromCall($reflection, $call->args, $scope);
    }

    public function getTypeFromStaticMethodCall(
        MethodReflection $reflection,
        StaticCall $call,
        Scope $scope
    ): Type {
        return $this->getTypeFromCall($reflection, $call->args, $scope);
    }

    private function getTypeFromCall(
        ParametersAcceptor $reflection,
        array $args,
        Scope $scope
    ): Type {
        if (count($args) === 0) {
            return $reflection->getReturnType();
        }

        $arg = $args[0]->value;

        if (!$arg instanceof ClassConstFetch) {
            return $reflection->getReturnType();
        }

        $class = $arg->class;

        if (!$class instanceof Name) {
            return $reflection->getReturnType();
        }

        $class = (string) $class;

        if ('static' === $class) {
            return $reflection->getReturnType();
        }

        if ('self' === $class) {
            $class = $scope->getClassReflection()->getName();
        }

        return new MockBuilderType($class);
    }
}
