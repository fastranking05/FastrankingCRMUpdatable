<?php

namespace Tests\Feature\Api\Pagination;

use App\Http\Middleware\JwtMiddleware;
use App\Http\Middleware\PermissionMiddleware;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\Concerns\SetsUpUserWithModuleReadPermissions;
use Tests\TestCase;

/**
 * Hits every HTTP route backed by Illuminate cursorPaginate(), asserting 200 + success envelope + cursor keys.
 *
 * JwtMiddleware and PermissionMiddleware are disabled; the user still has RBAC rows for permission names.
 */
class CursorPaginationEndpointsTest extends TestCase
{
    use RefreshDatabase;
    use SetsUpUserWithModuleReadPermissions;

    protected function setUp(): void
    {
        /*
         * Project migrations embed MySQL-only syntax (e.g. ON UPDATE CURRENT_TIMESTAMP).
         * phpunit.xml sets DB_CONNECTION=sqlite by default — skip before parent::setUp()
         * so RefreshDatabase never runs migrations on an incompatible driver.
         */
        if ($this->envDbDriver() === 'sqlite') {
            $this->markTestSkipped(
                'Requires MySQL. Example: DB_CONNECTION=mysql DB_HOST=127.0.0.1 DB_PORT=3307 '.
                'DB_DATABASE=fastrankingcrm DB_USERNAME=root DB_PASSWORD= php artisan test '.
                'tests/Feature/Api/Pagination/CursorPaginationEndpointsTest.php'
            );
        }

        parent::setUp();

        $this->withoutMiddleware([
            JwtMiddleware::class,
            PermissionMiddleware::class,
        ]);
    }

    private function envDbDriver(): string
    {
        return $_ENV['DB_CONNECTION'] ?? $_SERVER['DB_CONNECTION'] ?? 'sqlite';
    }

    private function assertApiSuccessWithCursor(TestResponse $response, ?string $nestedKeyUnderData = null): void
    {
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $payload = $response->json('data');
        $this->assertIsArray($payload);

        $cursorBlock = $nestedKeyUnderData === null ? $payload : ($payload[$nestedKeyUnderData] ?? null);
        $this->assertIsArray($cursorBlock, 'Expected cursor paginator payload'.($nestedKeyUnderData ? ' at data.'.$nestedKeyUnderData : ''));
        $this->assertCursorPaginatorShape($cursorBlock);
    }

    public function test_users_index_cursor_shape(): void
    {
        $this->actingAsUserWithModuleRead(['Administration']);
        $this->assertApiSuccessWithCursor($this->getJson('/api/users'));
    }

    public function test_teams_index_cursor_shape(): void
    {
        $this->actingAsUserWithModuleRead(['Administration']);
        $this->assertApiSuccessWithCursor($this->getJson('/api/teams'));
    }

    public function test_departments_index_cursor_shape(): void
    {
        $this->actingAsUserWithModuleRead(['Administration']);
        $this->assertApiSuccessWithCursor($this->getJson('/api/departments'));
    }

    public function test_roles_index_cursor_shape(): void
    {
        $this->actingAsUserWithModuleRead(['Administration']);
        $this->assertApiSuccessWithCursor($this->getJson('/api/roles'));
    }

    public function test_modules_index_cursor_shape(): void
    {
        $this->actingAsUserWithModuleRead(['Administration']);
        $this->assertApiSuccessWithCursor($this->getJson('/api/modules'));
    }

    public function test_followup_businesses_index_cursor_shape(): void
    {
        $this->actingAsUserWithModuleRead(['Follow-Up']);
        $this->assertApiSuccessWithCursor($this->getJson('/api/followup-businesses'));
    }

    public function test_followup_auth_persons_index_cursor_shape(): void
    {
        $this->actingAsUserWithModuleRead(['Follow-Up']);
        $this->assertApiSuccessWithCursor($this->getJson('/api/followup-auth-persons'));
    }

    public function test_followup_details_index_cursor_shape(): void
    {
        $this->actingAsUserWithModuleRead(['Follow-Up']);
        $this->assertApiSuccessWithCursor($this->getJson('/api/followup-details'));
    }

    public function test_followup_index_cursor_shape(): void
    {
        $this->actingAsUserWithModuleRead(['Follow-Up']);
        $this->assertApiSuccessWithCursor($this->getJson('/api/followup'));
    }

    public function test_emails_index_cursor_shape(): void
    {
        $this->actingAsUserWithModuleRead(['Email']);
        $this->assertApiSuccessWithCursor($this->getJson('/api/emails'));
    }

    public function test_emails_all_emails_cursor_shape(): void
    {
        $this->actingAsUserWithModuleRead(['Email']);
        $this->assertApiSuccessWithCursor($this->getJson('/api/emails/all-emails'), 'emails');
    }

    public function test_emails_my_emails_cursor_shape(): void
    {
        $this->actingAsUserWithModuleRead(['Email']);
        $this->assertApiSuccessWithCursor($this->getJson('/api/emails/my-emails'), 'emails');
    }

    public function test_appointments_direct_index_cursor_shape(): void
    {
        $this->actingAsUserWithModuleRead(['Appointment']);
        $this->assertApiSuccessWithCursor($this->getJson('/api/appointments'));
    }

    public function test_appointments_today_listing_cursor_shape(): void
    {
        $this->actingAsUserWithModuleRead(['Appointment']);
        $this->assertApiSuccessWithCursor($this->getJson('/api/appointments/today-appointments'), 'appointments');
    }

    public function test_consultation_index_cursor_shape(): void
    {
        $this->actingAsUserWithModuleRead(['Consultation']);
        $this->assertApiSuccessWithCursor($this->getJson('/api/consultation'));
    }

    public function test_consultation_filter_cursor_shape(): void
    {
        $this->actingAsUserWithModuleRead(['Consultation']);
        $this->assertApiSuccessWithCursor($this->getJson('/api/consultation/filter'));
    }

    public function test_quality_index_cursor_shape(): void
    {
        $this->actingAsUserWithModuleRead(['Quality Control']);
        $this->assertApiSuccessWithCursor($this->getJson('/api/quality'));
    }

    public function test_quality_quality_filter_post_cursor_shape(): void
    {
        $this->actingAsUserWithModuleRead(['Quality Control']);
        $this->assertApiSuccessWithCursor($this->postJson('/api/quality/quality-filter', []));
    }

    public function test_quality_my_assignments_cursor_shape(): void
    {
        $this->actingAsUserWithModuleRead(['Quality Control']);
        $this->assertApiSuccessWithCursor($this->getJson('/api/quality/my-assignments'));
    }

    public function test_quality_questions_index_cursor_shape(): void
    {
        $this->actingAsUserWithModuleRead(['Administration']);
        $this->assertApiSuccessWithCursor($this->getJson('/api/quality-questions'));
    }

    public function test_seo_questions_index_cursor_shape(): void
    {
        $this->actingAsUserWithModuleRead(['SEO']);
        $this->assertApiSuccessWithCursor($this->getJson('/api/seo-questions'));
    }

    public function test_seo_filter_post_cursor_shape(): void
    {
        $this->actingAsUserWithModuleRead(['SEO']);
        $this->assertApiSuccessWithCursor($this->postJson('/api/seo/seo-filter', []));
    }
}
