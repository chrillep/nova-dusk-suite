<?php

namespace Laravel\Nova\Tests\Browser;

use App\Models\Post;
use Database\Factories\CommentFactory;
use Database\Factories\LinkFactory;
use Database\Factories\PostFactory;
use Laravel\Dusk\Browser;
use Laravel\Nova\Testing\Browser\Pages\Update;
use Laravel\Nova\Tests\DuskTestCase;

class UpdateWithMorphToTest extends DuskTestCase
{
    /**
     * @test
     */
    public function resource_can_be_updated_to_new_parent()
    {
        $this->whileSearchable(function () {
            $comment = CommentFactory::new()->create();
            PostFactory::new()->create();

            $this->browse(function (Browser $browser) use ($comment) {
                $browser->loginAs(1)
                        ->visit(new Update('comments', $comment->id))
                        ->searchFirstRelation('commentable', 2)
                        ->update()
                        ->waitForText('The comment was updated');

                $this->assertCount(0, Post::find(1)->comments);
                $this->assertCount(1, Post::find(2)->comments);

                $browser->blank();
            });
        });
    }

    /**
     * @test
     */
    public function morph_to_field_should_honor_custom_polymorphic_type()
    {
        $this->whileSearchable(function () {
            $link = LinkFactory::new()->create();
            $link->comments()->save($comment = CommentFactory::new()->create());

            $this->browse(function (Browser $browser) use ($comment, $link) {
                $browser->loginAs(1)
                        ->visit(new Update('comments', $comment->id))
                        ->assertEnabled('select[dusk="commentable-type"]')
                        ->within('select[dusk="commentable-type"]', function ($browser) {
                            $browser->assertSee('Link');
                        })
                        ->assertSelectedSearchResult('commentable', $link->title);

                $browser->blank();
            });
        });
    }

    /**
     * @test
     */
    public function morph_to_field_should_ignore_query_parameters_when_editing()
    {
        $this->whileSearchable(function () {
            $post = PostFactory::new()->create();
            $post->comments()->save($comment = CommentFactory::new()->create());

            $this->browse(function (Browser $browser) use ($comment, $post) {
                $browser->loginAs(1)
                        ->visit(new Update('comments', $comment->id, [
                            'viaResource' => 'links',
                            'viaResourceId' => 1,
                            'viaRelationship' => 'comments',
                        ]))
                        ->whenAvailable('select[dusk="commentable-type"]', function ($browser) {
                            $browser->assertEnabled('')
                                    ->assertSelected('', 'posts');
                        })
                        ->assertSelectedSearchResult('commentable', $post->title);

                $browser->blank();
            });
        });
    }
}
