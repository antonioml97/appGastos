<?php

namespace Tests\Unit;

use App\Domain\Accounts\Projectors\SavingsAccountProjector;
use Tests\TestCase;

class SavingsAccountProjectorTest extends TestCase
{
    public function test_only_the_ahorro_category_is_a_savings_contribution(): void
    {
        $projector = app(SavingsAccountProjector::class);

        $this->assertTrue($projector->isSavingsCategory('Ahorro', 'ahorro'));
        $this->assertFalse($projector->isSavingsCategory('Seguros', 'ahorro'));
        $this->assertFalse($projector->isSavingsCategory('Vivienda', 'ahorro'));
    }
}
