<?php

namespace Tests\Unit;

use App\Models\Exercise;
use App\Support\MuscleMap;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class MuscleMapTest extends TestCase
{
    public function test_labels_use_only_the_canonical_spanish_groups(): void
    {
        $this->assertSame([
            'chest', 'back', 'shoulders', 'biceps', 'triceps', 'forearms',
            'abs', 'glutes', 'quadriceps', 'hamstrings', 'calves',
        ], array_keys(MuscleMap::labels()));
        $this->assertSame('Cuádriceps', MuscleMap::labels()['quadriceps']);
    }

    #[DataProvider('groupAliases')]
    public function test_catalog_groups_accept_common_spanish_aliases(string $group, string $id): void
    {
        $map = MuscleMap::forExercise(new Exercise(['title' => 'Ejercicio del catálogo', 'muscle_group' => $group]));

        $this->assertSame([$id], $map['primary']);
        $this->assertSame([], $map['secondary']);
        $this->assertSame(MuscleMap::labels()[$id], $map['label']);
        $this->assertTrue($map['available']);
    }

    public static function groupAliases(): array
    {
        return [
            ['Pectorales', 'chest'], ['ESPALDA', 'back'], ['Hombros', 'shoulders'],
            ['Bíceps', 'biceps'], ['Tríceps', 'triceps'], ['Antebrazos', 'forearms'],
            ['Abdominales', 'abs'], [' Glúteos ', 'glutes'], ['Cuádriceps', 'quadriceps'],
            ['Isquiotibiales', 'hamstrings'], ['Femorales', 'hamstrings'], ['Gemelos', 'calves'],
            ['chest', 'chest'], ['hamstrings', 'hamstrings'],
        ];
    }

    #[DataProvider('legAliases')]
    public function test_broad_leg_group_is_resolved_only_for_recognized_movements(string $title, string $id): void
    {
        $map = MuscleMap::forExercise(new Exercise(['title' => $title, 'muscle_group' => 'piernas']));

        $this->assertSame([$id], $map['primary']);
        $this->assertTrue($map['available']);
    }

    public static function legAliases(): array
    {
        return [
            ['Sillón de cuádriceps', 'quadriceps'], ['Extensiones de cuádriceps', 'quadriceps'],
            ['Lever leg extension', 'quadriceps'], ['Camilla de isquios', 'hamstrings'],
            ['Curl femoral', 'hamstrings'], ['Lever lying leg curl', 'hamstrings'],
            ['Gemelos', 'calves'], ['Elevación de talones', 'calves'],
            ['Sentadilla con barra', 'quadriceps'], ['Estocadas', 'quadriceps'],
            ['Sentadilla búlgara', 'quadriceps'], ['Prensa', 'quadriceps'],
            ['Prensa de piernas', 'quadriceps'], ['Prensa unilateral', 'quadriceps'],
            ['Peso muerto rumano', 'hamstrings'], ['Romanian deadlift', 'hamstrings'],
        ];
    }

    public function test_curated_compound_movements_have_defensible_secondary_groups(): void
    {
        $press = MuscleMap::forExercise(new Exercise(['title' => 'Press banca con barra', 'muscle_group' => 'pecho']));
        $squat = MuscleMap::forExercise(new Exercise(['title' => 'Sentadilla', 'muscle_group' => 'piernas']));

        $this->assertSame(['chest'], $press['primary']);
        $this->assertSame(['shoulders', 'triceps'], $press['secondary']);
        $this->assertSame(['quadriceps'], $squat['primary']);
        $this->assertSame(['glutes'], $squat['secondary']);
    }

    #[DataProvider('compoundVariants')]
    public function test_common_gym_variants_include_their_secondary_muscles(string $title, string $group, array $secondary): void
    {
        $map = MuscleMap::forExercise(new Exercise(['title' => $title, 'muscle_group' => $group]));
        $this->assertSame($secondary, $map['secondary']);
    }

    public static function compoundVariants(): array
    {
        return [
            ['Press banca en máquina', 'Pecho', ['shoulders', 'triceps']],
            ['Press pecho plano en máquina', 'Pecho', ['shoulders', 'triceps']],
            ['Press inclinado con mancuernas', 'Pecho', ['shoulders', 'triceps']],
            ['Smith bench press', 'Pecho', ['shoulders', 'triceps']],
            ['Press militar en máquina', 'Hombros', ['triceps']],
            ['Press militar con mancuernas', 'Hombros', ['triceps']],
            ['Remo en máquina', 'Espalda', ['biceps']],
            ['Jalón al pecho', 'Espalda', ['biceps']],
            ['Press cerrado con barra', 'Tríceps', ['chest', 'shoulders']],
            ['Cruce en polea', 'Pecho', []],
            ['Elevaciones laterales', 'Hombros', []],
        ];
    }

    public function test_romanian_deadlift_preserves_concrete_catalog_group_and_maps_its_known_partner(): void
    {
        $legs = MuscleMap::forExercise(new Exercise(['title' => 'Peso muerto rumano', 'muscle_group' => 'piernas']));
        $glutes = MuscleMap::forExercise(new Exercise(['title' => 'Peso muerto rumano', 'muscle_group' => 'gluteos']));

        $this->assertSame(['hamstrings'], $legs['primary']);
        $this->assertSame(['glutes'], $legs['secondary']);
        $this->assertSame(['glutes'], $glutes['primary']);
        $this->assertSame(['hamstrings'], $glutes['secondary']);
    }

    public function test_catalog_primary_group_prevails_over_a_conflicting_title(): void
    {
        $map = MuscleMap::forExercise(new Exercise(['title' => 'Press banca con barra', 'muscle_group' => 'biceps']));

        $this->assertSame(['biceps'], $map['primary']);
        $this->assertSame([], $map['secondary']);
    }

    public function test_unknown_or_unrepresented_groups_do_not_invent_an_anatomical_mapping(): void
    {
        foreach (['desconocido', 'cardio', 'fullbody', '', 'piernas', 'aductores'] as $group) {
            $map = MuscleMap::forExercise(new Exercise(['title' => 'Movimiento sin correspondencia', 'muscle_group' => $group]));

            $this->assertSame([
                'primary' => [], 'secondary' => [],
                'label' => 'Mapa muscular no disponible', 'available' => false,
            ], $map);
        }

        $map = MuscleMap::forExercise(new Exercise(['title' => 'Press banca con barra', 'muscle_group' => 'desconocido']));
        $this->assertFalse($map['available']);
    }
}
