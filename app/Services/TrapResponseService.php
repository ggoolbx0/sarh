<?php

namespace App\Services;

use App\Models\Trap;
use App\Models\TrapInteraction;
use App\Models\User;
use Illuminate\Http\Request;

class TrapResponseService
{
    /**
     * Full trap trigger flow: log interaction → increment risk → generate fake response.
     *
     * @param  User    $user     The employee who triggered the trap
     * @param  string  $trapCode The unique trap_code (e.g., SALARY_PEEK)
     * @param  Request $request  The HTTP request with metadata
     * @return array   Fake response payload for the PWA to display
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function triggerTrap(User $user, string $trapCode, Request $request): array
    {
        // 1. Resolve the trap from registry
        $trap = Trap::where('trap_code', $trapCode)->firstOrFail();

        // 2. Create the interaction record
        $interaction = TrapInteraction::create([
            'user_id'          => $user->id,
            'trap_id'          => $trap->id,
            'trap_type'        => $trap->trap_code,
            'trap_element'     => $request->input('element', $trap->trap_code),
            'page_url'         => $request->input('page_url', $request->header('Referer', '/')),
            'ip_address'       => $request->ip(),
            'user_agent'       => $request->userAgent(),
            'interaction_data' => $request->input('metadata', []),
            'risk_level'       => $trap->deriveRiskLevel(),
        ]);

        // 3. Recalculate logarithmic risk score
        $newScore = $user->incrementRiskScore();

        // 4. Generate the fake response
        $fakeResponse = $this->generateFakeResponse($trap);

        return [
            'response'   => $fakeResponse,
            'risk_score' => $newScore,
        ];
    }

    /**
     * Produce convincing fake UI feedback per trap type.
     *
     * Each trap_code has a dedicated fake response that looks real to the employee
     * but is entirely fabricated — no real data is ever exposed.
     */
    public function generateFakeResponse(Trap $trap): array
    {
        return match ($trap->trap_code) {
            'SALARY_PEEK' => $this->fakeSalaryTable(),
            'PRIVILEGE_ESCALATION' => $this->fakePrivilegeEscalation(),
            'SYSTEM_BYPASS' => $this->fakeSystemBypass(),
            'DATA_EXPORT' => $this->fakeDataExport(),
            default => $this->defaultFakeResponse($trap),
        };
    }

    /**
     * Fake salary table with randomized dummy data.
     * Employee sees "colleague salaries" — all numbers are fabricated.
     */
    private function fakeSalaryTable(): array
    {
        $fakeNames = [
            __('traps.fake_names.employee_1'),
            __('traps.fake_names.employee_2'),
            __('traps.fake_names.employee_3'),
        ];

        $rows = [];
        foreach ($fakeNames as $name) {
            $rows[] = [
                'name'   => $name,
                'salary' => number_format(rand(4000, 15000), 2),
            ];
        }

        return [
            'type'    => 'table',
            'title'   => __('traps.responses.salary_title'),
            'columns' => ['name', 'salary'],
            'data'    => $rows,
        ];
    }

    /**
     * Fake privilege escalation success message.
     */
    private function fakePrivilegeEscalation(): array
    {
        return [
            'type'    => 'success',
            'message' => __('traps.responses.privilege_granted'),
        ];
    }

    /**
     * Fake system bypass confirmation with warning styling.
     */
    private function fakeSystemBypass(): array
    {
        return [
            'type'    => 'warning',
            'message' => __('traps.responses.system_paused'),
        ];
    }

    /**
     * Fake data export with progress simulation and empty CSV.
     */
    private function fakeDataExport(): array
    {
        return [
            'type'     => 'download',
            'progress' => 100,
            'message'  => __('traps.responses.export_complete'),
            'url'      => '/exports/fake_' . bin2hex(random_bytes(8)) . '.csv',
        ];
    }

    /**
     * Default fallback for unrecognized trap codes.
     */
    private function defaultFakeResponse(Trap $trap): array
    {
        return [
            'type'    => $trap->fake_response_type,
            'message' => __('traps.responses.generic_success'),
        ];
    }
}
