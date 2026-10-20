<?php

namespace Tests\Feature;

use App\Filament\Resources\DietPlanDays\Pages\CreateDietPlanDay;
use App\Filament\Resources\DietPlanDays\Pages\EditDietPlanDay;
use App\Filament\Resources\RoutineDays\Pages\CreateRoutineDay;
use App\Filament\Resources\RoutineDays\Pages\EditRoutineDay;
use App\Models\DietPlan;
use App\Models\DietPlanDay;
use App\Models\Gym;
use App\Models\Routine;
use App\Models\RoutineDay;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ChildDayAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public static function dayResources(): array
    {
        return [
            'routine days' => [Routine::class, RoutineDay::class, CreateRoutineDay::class, EditRoutineDay::class, 'routine_id', ['day_number' => 1, 'title' => 'Training day']],
            'diet plan days' => [DietPlan::class, DietPlanDay::class, CreateDietPlanDay::class, EditDietPlanDay::class, 'diet_plan_id', ['day_of_week' => 'lunes', 'notes' => 'Meal day']],
        ];
    }

    #[DataProvider('dayResources')]
    public function test_coach_cannot_create_a_day_under_a_foreign_parent(string $parentClass, string $dayClass, string $createPage, string $editPage, string $parentKey, array $fields): void
    {
        [$coach, $own, $other] = $this->parents($parentClass);
        $this->actingAs($coach);

        Livewire::test($createPage)
            ->fillForm($fields + [$parentKey => $other->id])
            ->call('create')
            ->assertHasFormErrors([$parentKey]);

        $this->assertSame(0, $dayClass::count());
    }

    #[DataProvider('dayResources')]
    public function test_coach_cannot_reparent_an_existing_day_to_a_foreign_parent(string $parentClass, string $dayClass, string $createPage, string $editPage, string $parentKey, array $fields): void
    {
        [$coach, $own, $other] = $this->parents($parentClass);
        $day = $dayClass::create($fields + [$parentKey => $own->id]);
        $this->actingAs($coach);

        Livewire::test($editPage, ['record' => $day->getRouteKey()])
            ->fillForm([$parentKey => $other->id])
            ->call('save')
            ->assertHasFormErrors([$parentKey]);

        $this->assertSame($own->id, $day->fresh()->{$parentKey});
    }

    #[DataProvider('dayResources')]
    public function test_coach_can_create_and_reparent_days_within_their_gym(string $parentClass, string $dayClass, string $createPage, string $editPage, string $parentKey, array $fields): void
    {
        [$coach, $own] = $this->parents($parentClass);
        $anotherOwn = $parentClass::create(['gym_id' => $own->gym_id, 'coach_id' => $coach->id, 'title' => 'Another own parent']);
        $this->actingAs($coach);

        Livewire::test($createPage)
            ->fillForm($fields + [$parentKey => $own->id])
            ->call('create')
            ->assertHasNoFormErrors();

        $day = $dayClass::sole();
        Livewire::test($editPage, ['record' => $day->getRouteKey()])
            ->fillForm([$parentKey => $anotherOwn->id])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame($anotherOwn->id, $day->fresh()->{$parentKey});
    }

    #[DataProvider('dayResources')]
    public function test_super_admin_can_create_and_reparent_days_across_gyms(string $parentClass, string $dayClass, string $createPage, string $editPage, string $parentKey, array $fields): void
    {
        [$coach, $own, $other] = $this->parents($parentClass);
        $superAdmin = User::factory()->create(['role' => 'super_admin', 'gym_id' => $own->gym_id]);
        $this->actingAs($superAdmin);

        Livewire::test($createPage)
            ->fillForm($fields + [$parentKey => $other->id])
            ->call('create')
            ->assertHasNoFormErrors();

        $day = $dayClass::sole();
        $this->assertSame($other->id, $day->{$parentKey});

        Livewire::test($editPage, ['record' => $day->getRouteKey()])
            ->fillForm([$parentKey => $own->id])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame($own->id, $day->fresh()->{$parentKey});
    }

    private function parents(string $parentClass): array
    {
        $gym = Gym::create(['name' => 'Own gym']);
        $otherGym = Gym::create(['name' => 'Other gym']);
        $coach = User::factory()->create(['role' => 'coach', 'gym_id' => $gym->id]);
        $otherCoach = User::factory()->create(['role' => 'coach', 'gym_id' => $otherGym->id]);

        return [
            $coach,
            $parentClass::create(['gym_id' => $gym->id, 'coach_id' => $coach->id, 'title' => 'Own parent']),
            $parentClass::create(['gym_id' => $otherGym->id, 'coach_id' => $otherCoach->id, 'title' => 'Foreign parent']),
        ];
    }
}
