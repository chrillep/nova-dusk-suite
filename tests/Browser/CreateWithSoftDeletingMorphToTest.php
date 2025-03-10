<?php

namespace Laravel\Nova\Tests\Browser;

use Database\Factories\VideoFactory;
use Laravel\Dusk\Browser;
use Laravel\Nova\Testing\Browser\Pages\Create;
use Laravel\Nova\Testing\Browser\Pages\Detail;
use Laravel\Nova\Tests\DuskTestCase;

class CreateWithSoftDeletingMorphToTest extends DuskTestCase
{
    /**
     * @test
     */
    public function test_parent_select_is_locked_when_creating_child_of_soft_deleted_non_searchable_resource()
    {
        $this->parent_select_is_locked_when_creating_child_of_soft_deleted_resource();
    }

    /**
     * @test
     */
    public function test_parent_select_is_locked_when_creating_child_of_soft_deleted_searchable_resource()
    {
        $this->whileSearchable(function () {
            $this->parent_select_is_locked_when_creating_child_of_soft_deleted_resource();
        });
    }

    protected function parent_select_is_locked_when_creating_child_of_soft_deleted_resource()
    {
        $video = VideoFactory::new()->create(['deleted_at' => now()]);

        $this->browse(function (Browser $browser) use ($video) {
            $browser->loginAs(1)
                    ->visit(new Detail('videos', $video->id))
                    ->runCreateRelation('comments')
                    ->assertDisabled('select[dusk="commentable-type"]')
                    ->assertDisabled('select[dusk="commentable-select"]')
                    ->type('@body', 'Test Comment')
                    ->create();

            $this->assertCount(1, $video->fresh()->comments);

            $browser->blank();
        });
    }

    /**
     * @test
     */
    public function non_searchable_morph_to_respects_with_trashed_checkbox_state()
    {
        $this->whileSearchable(function () {
            $video = VideoFactory::new()->create(['deleted_at' => now()]);
            $video2 = VideoFactory::new()->create();

            $this->browse(function (Browser $browser) use ($video, $video2) {
                $browser->loginAs(1)
                        ->visit(new Create('comments'))
                        ->selectRelation('commentable-type', 'videos')
                        ->searchRelation('commentable', $video->id)
                        ->pause(1500)
                        ->assertMissing('@commentable-search-input-result-0')
                        ->searchRelation('commentable', $video2->id)
                        ->pause(1500)
                        ->assertSeeIn('@commentable-search-input-result-0', $video2->title)
                        ->closeSearchableResult('commentable')
                        ->click('@cancel-create-button');

                $browser->visit(new Create('comments'))
                        ->selectRelation('commentable-type', 'videos')
                        ->pause(750)
                        ->withTrashedRelation('commentable')
                        ->searchFirstRelation('commentable', $video->id)
                        ->assertSelectedSearchResult('commentable', $video->title)
                        ->type('@body', 'Test Comment')
                        ->create();

                $this->assertCount(1, $video->fresh()->comments);

                $browser->blank();
            });
        });
    }

    /**
     * @test
     */
    public function unable_to_uncheck_with_trashed_if_currently_selected_non_searchable_parent_is_trashed()
    {
        $this->whileSearchable(function () {
            $video = VideoFactory::new()->create(['deleted_at' => now()]);
            VideoFactory::new()->create();

            $this->browse(function (Browser $browser) use ($video) {
                $browser->loginAs(1)
                        ->visit(new Create('comments'))
                        ->selectRelation('commentable-type', 'videos')
                        ->pause(175)
                        ->withTrashedRelation('commentable')
                        ->searchFirstRelation('commentable', $video->id)
                        ->pause(1500)
                        ->withoutTrashedRelation('commentable')
                        ->type('@body', 'Test Comment')
                        ->create()
                        ->pause(175)
                        ->assertSee('This Commentable may not be associated with this resource.')
                        ->click('@cancel-create-button');

                $this->assertCount(0, $video->fresh()->comments);

                $browser->blank();
            });
        });
    }

    /**
     * @test
     */
    public function searchable_belongs_to_respects_with_trashed_checkbox_state()
    {
        $this->whileSearchable(function () {
            $video = VideoFactory::new()->create(['deleted_at' => now()]);

            $this->browse(function (Browser $browser) use ($video) {
                $browser->loginAs(1)
                        ->visit(new Create('comments'))
                        ->selectRelation('commentable-type', 'videos')
                        ->searchRelation('commentable', '1')
                        ->pause(1500)
                        ->assertNoRelationSearchResults('commentable')
                        ->closeSearchableResult('commentable')
                        ->click('@cancel-create-button');

                $browser->visit(new Create('comments'))
                        ->selectRelation('commentable-type', 'videos')
                        ->pause(175)
                        ->withTrashedRelation('commentable')
                        ->searchFirstRelation('commentable', '1')
                        ->type('@body', 'Test Comments')
                        ->create();

                $this->assertCount(1, $video->fresh()->comments);

                $browser->blank();
            });
        });
    }
}
