<?php

namespace Tests\Feature;

use App\Models\CheckRun;
use App\Models\Decision;
use App\Services\Checking\Rules\NoDirectEloquentInControllerRule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CoreJourneyUiTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function overview_shows_seeded_decision_without_setup_forms(): void
    {
        $this->seed();

        $response = $this->get(route('overview'));

        $response->assertOk();
        $response->assertSee('DecisionLedger');
        $response->assertSee('Keep architectural decisions connected to implementation');
        $response->assertSee('Check PHP');
        $response->assertSee('New Decision');
        $response->assertSee('No direct Eloquent or DB queries in controllers');
    }

    #[Test]
    public function user_can_create_a_decision(): void
    {
        $response = $this->post(route('decisions.store'), [
            'title' => 'Keep controllers thin',
            'statement' => 'Controllers must not own domain workflows.',
            'rationale' => 'Reuse and testability.',
            'category' => 'Architecture',
            'evaluation_limitations' => 'Pasted text only.',
            'rule_key' => NoDirectEloquentInControllerRule::RULE_KEY,
            'criterion_summary' => 'MUST NOT use Model::where in controllers.',
        ]);

        $decision = Decision::query()->first();
        $this->assertNotNull($decision);
        $response->assertRedirect(route('decisions.show', $decision));
        $this->get(route('decisions.show', $decision))
            ->assertOk()
            ->assertSee('Verification criteria')
            ->assertSee('Evaluation limitations');
    }

    #[Test]
    public function check_php_with_violating_fixture_produces_confirmed_finding(): void
    {
        $this->seed();
        $decision = Decision::query()->first();
        $code = file_get_contents(database_path('fixtures/php/violating_controller.php'));

        $response = $this->post(route('checks.store'), [
            'php_code' => $code,
            'decision_scope' => CheckRun::SCOPE_SINGLE,
            'decision_id' => $decision->id,
            'context_notes' => 'Should not affect evidence',
        ]);

        $response->assertRedirect();
        $run = CheckRun::query()->latest('id')->first();
        $this->assertNotNull($run);
        $this->assertTrue($run->findings()->where('checker_status', 'confirmed')->exists());

        $this->withSession(['last_check_run_id' => $run->id])
            ->get(route('checks.show', ['decision' => $decision->id]))
            ->assertOk()
            ->assertSee('confirmed')
            ->assertSee('Order::where')
            ->assertSee('Analyzed pasted text only');
    }

    #[Test]
    public function empty_php_paste_is_rejected(): void
    {
        $this->seed();
        $decision = Decision::query()->first();

        $this->from(route('checks.show'))
            ->post(route('checks.store'), [
                'php_code' => '',
                'decision_scope' => CheckRun::SCOPE_SINGLE,
                'decision_id' => $decision->id,
            ])
            ->assertRedirect(route('checks.show'))
            ->assertSessionHasErrors('php_code');
    }
}
