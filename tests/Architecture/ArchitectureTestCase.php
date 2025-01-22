<?php

declare(strict_types=1);

namespace Tests\Tempest\Architecture;

use Attribute;
use PHPat\Selector\Selector;
use PHPat\Test\Builder\Rule;
use PHPat\Test\PHPat;
use Tempest\Framework\Testing\IntegrationTest;

final class ArchitectureTestCase
{
    public function test_validation_rules_implement_rule_interface(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::inNamespace('Tempest\Validation\Rules'))
            ->shouldImplement()
            ->classes(Selector::classname(\Tempest\Validation\Rule::class));
    }

    public function test_validation_rules_are_attributes(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::inNamespace('Tempest\Validation\Rules'))
            ->shouldApplyAttribute()
            ->classes(Selector::classname(Attribute::class));
    }

    public function test_validation_rules_are_final(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::inNamespace('Tempest\Validation\Rules'))
            ->shouldBeFinal();
    }

    public function test_validation_rules_are_readonly(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::inNamespace('Tempest\Validation\Rules'))
            ->shouldBeReadonly();
    }

    public function test_unit_tests_do_not_rely_on_application_classes(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::inNamespace('Tests\Tempest\Unit'))
            ->shouldNotDependOn()
            ->classes(
                Selector::inNamespace('Tests\Tempest\Integration'),
                Selector::inNamespace('Tempest\Testing'),
            );
    }

    public function test_integration_tests_do_not_rely_on_unit_test_fixtures(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::inNamespace('Tests\Tempest\Integration'))
            ->shouldNotDependOn()
            ->classes(
                Selector::inNamespace('Tests\Tempest\Unit'),
            );
    }

    public function test_all_classes_should_be_final(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::AND(
                Selector::inNamespace('Tempest'),
                Selector::NOT(Selector::isInterface()),
                Selector::NOT(Selector::isAbstract()),
            ))
            ->shouldBeFinal();
    }

    public function test_unit_tests_should_not_depend_on_infrastructure_test_tools(): Rule
    {
        return PHPat::rule()
            ->classes(
                Selector::inNamespace('/^Tempest\\\\.+\\\\Tests/', true),
            )
            ->shouldNotExtend()
            ->classes(
                Selector::inNamespace('Tests\Tempest\Integration'),
                Selector::inNamespace('Tempest\Console\Testing'),
                Selector::inNamespace('Tempest\Framework\Testing'),
                Selector::classname(IntegrationTest::class),
            )
            ->because('Unit tests should test classes in isolation without booting the framework.');
    }

    public function test_unit_tests_should_not_depend_on_integration_tests(): Rule
    {
        return PHPat::rule()
            ->classes(
                Selector::inNamespace('/^Tempest\\\\.+\\\\Tests/', true),
            )
            ->shouldNotDependOn()
            ->classes(
                Selector::inNamespace('Tests\Tempest\Integration'),
            )
            ->because('Unit tests should not rely on integration fixtures.');
    }

    public function test_integration_tests_should_not_depend_on_unit_tests(): Rule
    {
        return PHPat::rule()
            ->classes(
                Selector::inNamespace('Tests\Tempest\Integration'),
            )
            ->shouldNotDependOn()
            ->classes(
                Selector::inNamespace('/^Tempest\\\\.+\\\\Tests/', true),
            )
            ->because('Integration tests should not rely on unit test fixtures.');
    }

    public function test_unit_tests_should_not_depend_on_framework_fixtures(): Rule
    {
        return PHPat::rule()
            ->classes(
                Selector::inNamespace('/^Tempest\\\\.+\\\\Tests/', true),
            )
            ->shouldNotDependOn()
            ->classes(
                Selector::inNamespace('Tests\Tempest\Fixtures'),
            )
            ->because('Unit tests should test objects in isolation, so they should not depend on framework fixtures.');
    }
}
