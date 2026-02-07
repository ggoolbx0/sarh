<?php

namespace Tests\Feature;

use App\Models\Trap;
use App\Models\TrapInteraction;
use App\Models\User;
use App\Services\TrapResponseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class TrapSystemTest extends TestCase
{
    use RefreshDatabase;

    private TrapResponseService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new TrapResponseService();
    }

    private function seedTraps(): void
    {
        $this->seed(\Database\Seeders\TrapsSeeder::class);
    }

    private function createUser(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'status' => 'active',
        ], $overrides));
    }

    /**
     * TC-TRAP-001: Trigger Creates Interaction Record
     */
    public function test_trigger_creates_interaction_record(): void
    {
        $this->seedTraps();
        $user = $this->createUser();
        $request = Request::create('/traps/trigger', 'POST', [
            'trap_code' => 'SALARY_PEEK',
            'element'   => 'btn-salary',
            'page_url'  => '/dashboard',
        ]);
        $request->setUserResolver(fn () => $user);

        $result = $this->service->triggerTrap($user, 'SALARY_PEEK', $request);

        $this->assertDatabaseHas('trap_interactions', [
            'user_id'   => $user->id,
            'trap_type' => 'SALARY_PEEK',
        ]);
        $this->assertArrayHasKey('response', $result);
        $this->assertArrayHasKey('risk_score', $result);
    }

    /**
     * TC-TRAP-002: Fake Salary Table Response
     */
    public function test_fake_salary_table_response(): void
    {
        $this->seedTraps();
        $trap = Trap::where('trap_code', 'SALARY_PEEK')->first();

        $response = $this->service->generateFakeResponse($trap);

        $this->assertEquals('table', $response['type']);
        $this->assertArrayHasKey('data', $response);
        $this->assertCount(3, $response['data']);
        $this->assertArrayHasKey('name', $response['data'][0]);
        $this->assertArrayHasKey('salary', $response['data'][0]);
    }

    /**
     * TC-TRAP-003: Fake Export Progress Response
     */
    public function test_fake_export_response(): void
    {
        $this->seedTraps();
        $trap = Trap::where('trap_code', 'DATA_EXPORT')->first();

        $response = $this->service->generateFakeResponse($trap);

        $this->assertEquals('download', $response['type']);
        $this->assertEquals(100, $response['progress']);
        $this->assertStringContainsString('/exports/fake_', $response['url']);
        $this->assertStringEndsWith('.csv', $response['url']);
    }

    /**
     * TC-TRAP-004: Risk Weight → Risk Level Mapping
     */
    public function test_risk_weight_to_risk_level_mapping(): void
    {
        // risk_weight 2 → low
        $low = Trap::create([
            'name_ar' => 'اختبار', 'name_en' => 'Test', 'trap_code' => 'TEST_LOW',
            'risk_weight' => 2, 'fake_response_type' => 'success', 'is_active' => true,
        ]);
        $this->assertEquals('low', $low->deriveRiskLevel());

        // risk_weight 5 → medium
        $medium = Trap::create([
            'name_ar' => 'اختبار', 'name_en' => 'Test', 'trap_code' => 'TEST_MEDIUM',
            'risk_weight' => 5, 'fake_response_type' => 'success', 'is_active' => true,
        ]);
        $this->assertEquals('medium', $medium->deriveRiskLevel());

        // risk_weight 8 → high
        $high = Trap::create([
            'name_ar' => 'اختبار', 'name_en' => 'Test', 'trap_code' => 'TEST_HIGH',
            'risk_weight' => 8, 'fake_response_type' => 'success', 'is_active' => true,
        ]);
        $this->assertEquals('high', $high->deriveRiskLevel());

        // risk_weight 10 → critical
        $critical = Trap::create([
            'name_ar' => 'اختبار', 'name_en' => 'Test', 'trap_code' => 'TEST_CRITICAL',
            'risk_weight' => 10, 'fake_response_type' => 'success', 'is_active' => true,
        ]);
        $this->assertEquals('critical', $critical->deriveRiskLevel());
    }

    /**
     * TC-TRAP-005: Controller Returns 200 With Fake Payload
     */
    public function test_controller_returns_200_with_fake_payload(): void
    {
        $this->seedTraps();
        $user = $this->createUser();

        $response = $this->actingAs($user)
            ->postJson('/traps/trigger', ['trap_code' => 'SALARY_PEEK']);

        $response->assertStatus(200)
            ->assertJsonStructure(['response', 'risk_score']);
    }

    /**
     * TC-TRAP-006: Invalid trap_code Returns 422
     */
    public function test_invalid_trap_code_returns_422(): void
    {
        $user = $this->createUser();

        $response = $this->actingAs($user)
            ->postJson('/traps/trigger', ['trap_code' => 'NONEXISTENT']);

        $response->assertStatus(422);
    }
}
