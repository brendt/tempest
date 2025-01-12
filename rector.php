<?php

declare(strict_types=1);

use Rector\Arguments\Rector\ClassMethod\ArgumentAdderRector;
use Rector\Caching\ValueObject\Storage\FileCacheStorage;
use Rector\CodingStyle\Rector\Encapsed\EncapsedStringsToSprintfRector;
use Rector\Config\RectorConfig;
use Rector\DeadCode\Rector\PropertyProperty\RemoveNullPropertyInitializationRector;
use Rector\Php70\Rector\StaticCall\StaticCallOnNonStaticToInstanceCallRector;
use Rector\Php74\Rector\Closure\ClosureToArrowFunctionRector;
use Rector\Php74\Rector\Property\RestoreDefaultNullToNullableTypePropertyRector;
use Rector\Php74\Rector\Ternary\ParenthesizeNestedTernaryRector;
use Rector\Php81\Rector\Array_\FirstClassCallableRector;
use Rector\Php81\Rector\FuncCall\NullToStrictStringFuncCallArgRector;
use Rector\Php81\Rector\Property\ReadOnlyPropertyRector;
use Rector\Php82\Rector\Class_\ReadOnlyClassRector;
use Rector\Php82\Rector\Param\AddSensitiveParameterAttributeRector;
use Rector\Php83\Rector\ClassMethod\AddOverrideAttributeToOverriddenMethodsRector;
use Rector\Php84\Rector\Param\ExplicitNullableParamTypeRector;
use Rector\TypeDeclaration\Rector\ArrowFunction\AddArrowFunctionReturnTypeRector;
use Rector\TypeDeclaration\Rector\ClassMethod\ReturnNeverTypeRector;
use Rector\TypeDeclaration\Rector\Closure\ClosureReturnTypeRector;
use Rector\TypeDeclaration\Rector\Empty_\EmptyOnNullableObjectToInstanceOfRector;

return RectorConfig::configure()
    ->withCache('./.cache/rector', FileCacheStorage::class)
    ->withPaths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    ->withConfiguredRule(AddSensitiveParameterAttributeRector::class, [
        'sensitive_parameters' => [
            'password',
            'secret',
        ],
    ])
    ->withRules([
        ParenthesizeNestedTernaryRector::class,
        ExplicitNullableParamTypeRector::class,
    ])
    ->withSkip([
        AddOverrideAttributeToOverriddenMethodsRector::class,
        ArgumentAdderRector::class,
        ClosureToArrowFunctionRector::class,
        EmptyOnNullableObjectToInstanceOfRector::class,
        FirstClassCallableRector::class,
        NullToStrictStringFuncCallArgRector::class,
        ReadOnlyClassRector::class,
        ReadOnlyPropertyRector::class,
        RemoveNullPropertyInitializationRector::class,
        AddSensitiveParameterAttributeRector::class,
        RestoreDefaultNullToNullableTypePropertyRector::class,
        ReturnNeverTypeRector::class,
        StaticCallOnNonStaticToInstanceCallRector::class,
        ClosureReturnTypeRector::class,
        EncapsedStringsToSprintfRector::class,
        AddArrowFunctionReturnTypeRector::class,
    ])
    ->withSkipPath(__DIR__  .'/src/Tempest/Router/src/Exceptions/HttpProductionErrorHandler.php')
    ->withSkipPath(__DIR__ . '/src/Tempest/Router/src/Exceptions/exception.php')
    ->withParallel(300, 10, 10)
    ->withPreparedSets(
        codeQuality: false,
        codingStyle: true,
        privatization: true,
        naming: false,
        earlyReturn: true,
    )
    ->withDeadCodeLevel(40)
    ->withMemoryLimit('3G')
    ->withPhpSets(php83: true)
    ->withTypeCoverageLevel(37);
