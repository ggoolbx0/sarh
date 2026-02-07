<?php

namespace Tests\Feature;

use App\Livewire\WhistleblowerForm;
use App\Livewire\WhistleblowerTrack;
use App\Models\WhistleblowerReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class WhistleblowerFormTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    /**
     * TC-WB-001: Whistleblower page accessible without authentication.
     */
    public function test_whistleblower_page_accessible_without_auth(): void
    {
        $response = $this->get(route('whistleblower.form'));

        $response->assertStatus(200);
        $response->assertSeeLivewire(WhistleblowerForm::class);
    }

    /**
     * TC-WB-002: Anonymous report submission encrypts content and generates ticket.
     */
    public function test_anonymous_report_submission_creates_encrypted_record(): void
    {
        $content = 'This is a detailed report about financial irregularities found in department X.';

        Livewire::test(WhistleblowerForm::class)
            ->set('category', 'fraud')
            ->set('severity', 'high')
            ->set('content', $content)
            ->call('submit')
            ->assertSet('submitted', true)
            ->assertNotSet('ticketNumber', '')
            ->assertNotSet('anonymousToken', '');

        $this->assertDatabaseCount('whistleblower_reports', 1);

        $report = WhistleblowerReport::first();

        // Verify ticket format: WB-{8hex}-{yymmdd}
        $this->assertMatchesRegularExpression('/^WB-[A-F0-9]{8}-\d{6}$/', $report->ticket_number);

        // Verify content is encrypted (not plain text)
        $this->assertNotEquals($content, $report->encrypted_content);

        // Verify decryption works
        $this->assertEquals($content, decrypt($report->encrypted_content));

        // Verify no user association
        $this->assertNull($report->assigned_to);
        $this->assertEquals('new', $report->status);
    }

    /**
     * TC-WB-003: Validation rejects short content and missing category.
     */
    public function test_validation_rejects_invalid_submission(): void
    {
        Livewire::test(WhistleblowerForm::class)
            ->set('category', '')
            ->set('content', 'Too short')
            ->call('submit')
            ->assertHasErrors(['category', 'content']);

        $this->assertDatabaseCount('whistleblower_reports', 0);
    }

    /**
     * TC-WB-004: Track report by anonymous token returns status only.
     */
    public function test_track_report_by_token_returns_status_not_content(): void
    {
        $token = WhistleblowerReport::generateAnonymousToken();

        WhistleblowerReport::create([
            'ticket_number'    => WhistleblowerReport::generateTicketNumber(),
            'encrypted_content' => encrypt('Secret details about corruption'),
            'category'         => 'corruption',
            'severity'         => 'critical',
            'anonymous_token'  => $token,
            'status'           => 'investigating',
        ]);

        $component = Livewire::test(WhistleblowerTrack::class)
            ->set('token', $token)
            ->call('track');

        $report = $component->get('report');

        $this->assertNotNull($report);
        $this->assertEquals('corruption', $report['category']);
        $this->assertEquals('investigating', $report['status']);

        // Content must NEVER be exposed
        $this->assertArrayNotHasKey('content', $report);
        $this->assertArrayNotHasKey('encrypted_content', $report);
        $this->assertArrayNotHasKey('investigator_notes', $report);
    }
}
