<?php

namespace Tests\Feature;

use App\Models\Proyect;
use App\Models\Rol;
use App\Models\Survey;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SurveyPrePostLinkTest extends TestCase
{
    use RefreshDatabase;

    public function test_editing_a_pre_survey_without_post_reference_keeps_its_linked_post(): void
    {
        $this->authenticateAdministrator();
        [$project, $baseline, $monitoring] = $this->linkedSurveys();

        $this->putJson('/api/survey/'.$baseline->id, [
            'proyect_id' => $project->id,
            'survey_name' => $baseline->survey_name,
            'description' => 'Descripción editada desde el panel',
            'status' => Survey::STATUS_ACTIVE,
            'survey_type' => 'PRE',
            'post_survey_id' => null,
            'post_survey_name' => null,
        ])->assertOk();

        $this->assertSame($monitoring->id, (int) $baseline->fresh()->post_survey_id);
        $this->assertSame($baseline->id, $monitoring->fresh()->preSurvey?->id);
    }

    public function test_editing_a_pre_survey_can_still_link_it_to_a_free_post(): void
    {
        $this->authenticateAdministrator();
        $project = Proyect::create(['name' => 'Proyecto PRE libre']);
        $pre = $this->survey($project, 'PRE libre', 'PRE');
        $post = $this->survey($project, 'POST libre', 'POST');

        $this->putJson('/api/survey/'.$pre->id, [
            'proyect_id' => $project->id,
            'survey_name' => $pre->survey_name,
            'description' => 'Descripción',
            'status' => Survey::STATUS_ACTIVE,
            'survey_type' => 'PRE',
            'post_survey_id' => $post->id,
        ])->assertOk();

        $this->assertSame($post->id, (int) $pre->fresh()->post_survey_id);
    }

    public function test_editing_a_post_survey_unlinks_every_other_pre(): void
    {
        $this->authenticateAdministrator();
        [$project, $archivedPre, $baseline, $monitoring] = $this->duplicatedPreLinks();

        $this->putJson('/api/survey/'.$monitoring->id, [
            'proyect_id' => $project->id,
            'survey_name' => $monitoring->survey_name,
            'description' => 'Descripción',
            'status' => Survey::STATUS_ACTIVE,
            'survey_type' => 'POST',
            'pre_survey_id' => $baseline->id,
        ])->assertOk();

        $this->assertNull($archivedPre->fresh()->post_survey_id);
        $this->assertSame($monitoring->id, (int) $baseline->fresh()->post_survey_id);
    }

    public function test_pre_survey_relation_prefers_the_most_recent_pre_when_duplicated(): void
    {
        [, , $baseline, $monitoring] = $this->duplicatedPreLinks();

        $this->assertSame($baseline->id, $monitoring->preSurvey()->first()?->id);
        $this->assertSame($baseline->id, Survey::with('preSurvey')->find($monitoring->id)->preSurvey?->id);
    }

    public function test_migration_unlinks_archived_pre_from_kpt_monitoring(): void
    {
        [, $archivedPre, $baseline, $monitoring] = $this->duplicatedPreLinks();
        [, $untouchedPre, $untouchedPost] = $this->linkedSurveys();

        $this->runDuplicateLinkMigration();

        $this->assertNull($archivedPre->fresh()->post_survey_id);
        $this->assertSame($monitoring->id, (int) $baseline->fresh()->post_survey_id);
        $this->assertSame($untouchedPost->id, (int) $untouchedPre->fresh()->post_survey_id);
    }

    public function test_migration_keeps_the_pre_with_finalized_participations(): void
    {
        [$project, $archivedPre, $baseline, $monitoring] = $this->duplicatedPreLinks();
        // Una PRE más reciente sin finalizadas no debe desplazar a la que tiene datos.
        DB::table('surveys')->where('id', $archivedPre->id)->update(['post_survey_id' => null]);
        $newerPre = $this->survey($project, 'PRE nueva sin datos', 'PRE', $monitoring->id);
        DB::table('surveyeds')->insert([
            ['survey_id' => $baseline->id, 'status' => 'FINALIZADA', 'created_at' => now(), 'updated_at' => now()],
            ['survey_id' => $newerPre->id, 'status' => 'BORRADOR', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->runDuplicateLinkMigration();

        $this->assertSame($monitoring->id, (int) $baseline->fresh()->post_survey_id);
        $this->assertNull($newerPre->fresh()->post_survey_id);
        $this->assertSame($baseline->id, $monitoring->fresh()->preSurvey?->id);
    }

    /**
     * Reproduce producción: la PRE archivada (9) y KPT línea base (10) apuntan a KPT monitoreo (11).
     */
    private function duplicatedPreLinks(): array
    {
        $project = Proyect::create(['name' => 'Proyecto con PRE duplicadas']);
        $monitoring = $this->survey($project, 'KPT monitoreo', 'POST');
        $archivedPre = $this->survey($project, 'Identificación / Uso actual de las cocinas', 'PRE');
        $baseline = $this->survey($project, 'KPT línea base', 'PRE');
        DB::table('surveys')
            ->whereIn('id', [$archivedPre->id, $baseline->id])
            ->update(['post_survey_id' => $monitoring->id]);

        return [$project, $archivedPre->fresh(), $baseline->fresh(), $monitoring];
    }

    private function runDuplicateLinkMigration(): void
    {
        $migration = require database_path('migrations/2026_10_05_000001_resolve_duplicate_pre_links_to_post_surveys.php');
        $migration->up();
    }

    private function linkedSurveys(): array
    {
        $project = Proyect::create(['name' => 'Proyecto KPT vinculado']);
        $monitoring = $this->survey($project, 'KPT monitoreo', 'POST');
        $baseline = $this->survey($project, 'KPT línea base', 'PRE', $monitoring->id);

        return [$project, $baseline, $monitoring];
    }

    private function survey(Proyect $project, string $name, string $type, ?int $postSurveyId = null): Survey
    {
        return Survey::create([
            'proyect_id' => $project->id,
            'survey_name' => $name,
            'description' => 'Descripción',
            'survey_type' => $type,
            'status' => Survey::STATUS_ACTIVE,
            'post_survey_id' => $postSurveyId,
        ]);
    }

    private function authenticateAdministrator(): void
    {
        Sanctum::actingAs(User::create([
            'number_document' => 'USR-PRE-POST-LINK',
            'names' => 'Administrador de vínculos',
            'username' => 'pre-post-link-admin',
            'password' => 'Password!2026',
            'status' => User::STATUS_ACTIVE,
            'rol_id' => Rol::where('name', 'Administrador')->firstOrFail()->id,
        ]));
    }
}
