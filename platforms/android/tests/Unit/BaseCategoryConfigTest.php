<?php

namespace Tests\Unit;

use App\Support\BaseCategoryConfig;
use PHPUnit\Framework\TestCase;

class BaseCategoryConfigTest extends TestCase
{
    public function test_it_loads_the_configured_base_categories_in_expected_order(): void
    {
        $categories = BaseCategoryConfig::all();

        $this->assertCount(15, $categories);
        $this->assertSame('ahorro', $categories[0]['id']);
        $this->assertSame('Ahorro', $categories[0]['nombre']);
        $this->assertSame('uno_mismo', $categories[13]['id']);
        $this->assertSame('vivienda', $categories[14]['id']);
    }

    public function test_each_base_category_contains_the_required_fields(): void
    {
        foreach (BaseCategoryConfig::all() as $category) {
            $this->assertSame(['id', 'nombre', 'icono', 'color'], array_keys($category));
            $this->assertMatchesRegularExpression('/^[a-z0-9_]+$/', $category['id']);
            $this->assertMatchesRegularExpression('/^#[A-F0-9]{6}$/', $category['color']);
            $this->assertNotSame('', $category['nombre']);
            $this->assertNotSame('', $category['icono']);
        }
    }
}
