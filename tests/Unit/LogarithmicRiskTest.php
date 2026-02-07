<?php

namespace Tests\Unit;

use App\Models\Trap;
use App\Models\TrapInteraction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LogarithmicRiskTest extends TestCase
{
    use RefreshDatabase;

    private function createTrapUser(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'status' => 'active',
        ], $overrides));
    }

    private function createTrap(string $code = 'SALARY_PEEK', int $weight = 6): Trap
    {
        return Trap::create([
            'name_ar'            => 'مصيدة اختبار',
            'name_en'            => 'Test Trap',
            'trap_code'          => $code,
            'description_ar'     => 'وصف اختبار',
            'description_en'     => 'Test description',
            'risk_weight'        => $weight,
            'fake_response_type' => 'success',
            'is_active'          => true,
        ]);
    }

    private function createInteraction(User $user, Trap $trap): TrapInteraction
    {
        return TrapInteraction::create([
            'user_id'          => $user->id,
            'trap_id'          => $trap->id,
            'trap_type'        => $trap->trap_code,
            'trap_element'     => $trap->trap_code,
            'page_url'         => '/test',
            'ip_address'       => '127.0.0.1',
            'risk_level'       => $trap->deriveRiskLevel(),
            'interaction_data' => [],
        ]);
    }

    /**
     * TC-RISK-001: First Trigger = 10 Points
     * Formula: 10 × (2^1 − 1) = 10
     */
    public function test_first_trigger_equals_10_points(): void
    {
        $user = $this->createTrapUser();
        $trap = $this->createTrap();

        // Create 1 interaction first, then calculate
        $this->createInteraction($user, $trap);
        $score = $user->incrementRiskScore();

        $this->assertEquals(10, $score);
        $this->assertEquals(10, $user->fresh()->risk_score);
    }

    /**
     * TC-RISK-002: Second Trigger = 30 Points
     * Formula: 10 × (2^2 − 1) = 30
     */
    public function test_second_trigger_equals_30_points(): void
    {
        $user = $this->createTrapUser();
        $trap = $this->createTrap();

        $this->createInteraction($user, $trap);
        $this->createInteraction($user, $trap);
        $score = $user->incrementRiskScore();

        $this->assertEquals(30, $score);
    }

    /**
     * TC-RISK-003: Fifth Trigger = 310 Points
     * Formula: 10 × (2^5 − 1) = 310
     */
    public function test_fifth_trigger_equals_310_points(): void
    {
        $user = $this->createTrapUser();
        $trap = $this->createTrap();

        for ($i = 0; $i < 5; $i++) {
            $this->createInteraction($user, $trap);
        }
        $score = $user->incrementRiskScore();

        $this->assertEquals(310, $score);
    }

    /**
     * TC-RISK-004: Score Progression Sequence
     * Expected: [10, 30, 70, 150, 310]
     */
    public function test_score_progression_sequence(): void
    {
        $user = $this->createTrapUser();
        $trap = $this->createTrap();
        $expected = [10, 30, 70, 150, 310];
        $scores = [];

        for ($i = 0; $i < 5; $i++) {
            $this->createInteraction($user, $trap);
            $scores[] = $user->incrementRiskScore();
        }

        $this->assertEquals($expected, $scores);
    }

    /**
     * TC-RISK-005: Risk Level Classification
     */
    public function test_risk_level_classification(): void
    {
        $user = $this->createTrapUser();

        // score < 30 → low
        $user->forceFill(['risk_score' => 10])->save();
        $this->assertEquals('low', $user->fresh()->risk_level);

        // score < 100 → medium
        $user->forceFill(['risk_score' => 30])->save();
        $this->assertEquals('medium', $user->fresh()->risk_level);

        // score < 300 → high
        $user->forceFill(['risk_score' => 150])->save();
        $this->assertEquals('high', $user->fresh()->risk_level);

        // score >= 300 → critical
        $user->forceFill(['risk_score' => 310])->save();
        $this->assertEquals('critical', $user->fresh()->risk_level);
    }

    /**
     * TC-RISK-006: risk_score NOT Mass-Assignable
     */
    public function test_risk_score_not_mass_assignable(): void
    {
        // Use fill() to test mass-assignment protection (NOT factory which uses forceFill)
        $user = new User();
        $user->fill(['risk_score' => 999]);

        // risk_score is NOT in $fillable, so fill() should NOT set it
        $this->assertNull($user->risk_score);
    }
}
