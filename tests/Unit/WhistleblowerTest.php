<?php

namespace Tests\Unit;

use App\Models\WhistleblowerReport;
use Tests\TestCase;

class WhistleblowerTest extends TestCase
{
    /**
     * TC-WB-001: Content Encryption Round-trip
     */
    public function test_content_encryption_roundtrip(): void
    {
        $report = new WhistleblowerReport();
        $plainText = 'Fraud detected in branch 3 — manager approving fake overtime';

        $report->setContent($plainText);

        // Encrypted content must differ from plain text
        $this->assertNotEquals($plainText, $report->encrypted_content);

        // Decrypted content must match original
        $this->assertEquals($plainText, $report->getContent());
    }

    /**
     * TC-WB-002: Unique Ticket Numbers
     */
    public function test_unique_ticket_numbers(): void
    {
        $t1 = WhistleblowerReport::generateTicketNumber();
        $t2 = WhistleblowerReport::generateTicketNumber();

        $this->assertNotEquals($t1, $t2);
        $this->assertStringStartsWith('WB-', $t1);
        $this->assertStringStartsWith('WB-', $t2);
    }

    /**
     * TC-WB-003: Anonymous Token is SHA-256 Length (64 hex chars)
     */
    public function test_anonymous_token_is_sha256(): void
    {
        $token = WhistleblowerReport::generateAnonymousToken();

        $this->assertEquals(64, strlen($token));
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $token);
    }

    /**
     * TC-WB-004: Unicode/Arabic Content Encryption
     */
    public function test_arabic_content_encryption(): void
    {
        $report = new WhistleblowerReport();
        $plainText = 'تم رصد مخالفة مالية في الفرع الثالث — تزوير ساعات إضافية';

        $report->setContent($plainText);

        $this->assertNotEquals($plainText, $report->encrypted_content);
        $this->assertEquals($plainText, $report->getContent());
    }
}
