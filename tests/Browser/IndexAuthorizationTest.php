<?php

namespace Laravel\Nova\Tests\Browser;

use App\Models\User;
use Database\Factories\PostFactory;
use Laravel\Dusk\Browser;
use Laravel\Nova\Testing\Browser\Components\IndexComponent;
use Laravel\Nova\Testing\Browser\Pages\Dashboard;
use Laravel\Nova\Testing\Browser\Pages\Detail;
use Laravel\Nova\Testing\Browser\Pages\Index;
use Laravel\Nova\Testing\Browser\Pages\Page;
use Laravel\Nova\Tests\DuskTestCase;

class IndexAuthorizationTest extends DuskTestCase
{
    /**
     * @test
     */
    public function resource_index_can_be_totally_blocked_via_view_any()
    {
        PostFactory::new()->create();
        User::find(1)->shouldBlockFrom(...[
            'user.viewAny',
            'post.viewAny',
        ]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs(1)
                    ->visit(new Dashboard())
                    ->assertDontSeeIn('div.sidebar-menu[role="navigation"]', 'User Posts')
                    ->visit(new Page('/resources/posts'))
                    ->assertForbidden();

            $browser->visit(new Dashboard())
                    ->assertDontSeeIn('div.sidebar-menu[role="navigation"]', 'Users')
                    ->visit(new Page('/resources/users'))
                    ->assertForbidden()
                    ->visit(new Detail('users', 1));

            $browser->blank();
        });
    }

    /**
     * @test
     */
    public function shouldnt_see_id_link_if_blocked_from_viewing()
    {
        $posts = PostFactory::new()->times(3)->create();
        User::find(1)->shouldBlockFrom(...[
            'post.view.'.$posts[0]->id,
            'user.view.'.$posts[1]->user_id,
        ]);

        $posts->loadMissing('user');

        $this->browse(function (Browser $browser) use ($posts) {
            $browser->loginAs(1)
                    ->visit(new Index('posts'))
                    ->within(new IndexComponent('posts'), function ($browser) use ($posts) {
                        $browser->waitForTable()
                                ->assertDontSeeLink($posts[0]->id)
                                ->assertSeeLink($posts[0]->user->name)
                                ->assertSeeLink($posts[1]->id)
                                ->assertDontSeeLink($posts[1]->user->name)
                                ->assertSeeLink($posts[2]->id)
                                ->assertSeeLink($posts[2]->user->name);
                    });

            $browser->blank();
        });
    }

    /**
     * @test
     */
    public function shouldnt_see_edit_button_if_blocked_from_updating()
    {
        $post = PostFactory::new()->create();
        $post2 = PostFactory::new()->create();
        User::find(1)->shouldBlockFrom('post.update.'.$post->id);

        $this->browse(function (Browser $browser) use ($post, $post2) {
            $browser->loginAs(1)
                    ->visit(new Index('posts'))
                    ->within(new IndexComponent('posts'), function ($browser) use ($post, $post2) {
                        $browser->waitForTable()
                                ->assertMissing('@'.$post->id.'-edit-button')
                                ->assertVisible('@'.$post2->id.'-edit-button');
                    });

            $browser->blank();
        });
    }

    /**
     * @test
     */
    public function shouldnt_see_delete_button_if_blocked_from_deleting()
    {
        $post = PostFactory::new()->create();
        $post2 = PostFactory::new()->create();
        User::find(1)->shouldBlockFrom('post.delete.'.$post->id);

        $this->browse(function (Browser $browser) use ($post, $post2) {
            $browser->loginAs(1)
                    ->visit(new Index('posts'))
                    ->within(new IndexComponent('posts'), function ($browser) use ($post, $post2) {
                        $browser->waitForTable()
                                ->assertMissing('@'.$post->id.'-delete-button')
                                ->assertVisible('@'.$post2->id.'-delete-button');
                    });

            $browser->blank();
        });
    }

    /**
     * @test
     */
    public function can_delete_resources_using_checkboxes_only_if_authorized_to_delete_them()
    {
        PostFactory::new()->times(3)->create();
        User::find(1)->shouldBlockFrom('post.delete.1');

        $this->browse(function (Browser $browser) {
            $browser->loginAs(1)
                    ->visit(new Index('posts'))
                    ->within(new IndexComponent('posts'), function ($browser) {
                        $browser->waitForTable()
                            ->clickCheckboxForId(3)
                            ->clickCheckboxForId(2)
                            ->clickCheckboxForId(1)
                            ->deleteSelected()
                            ->waitForTable()
                            ->assertSeeResource(1)
                            ->assertDontSeeResource(2)
                            ->assertDontSeeResource(3);
                    });

            $browser->blank();
        });
    }

    /**
     * @test
     */
    public function can_delete_all_matching_resources_only_if_authorized_to_delete_them()
    {
        PostFactory::new()->times(3)->create();
        User::find(1)->shouldBlockFrom('post.delete.1');

        $this->browse(function (Browser $browser) {
            $browser->loginAs(1)
                    ->visit(new Index('posts'))
                    ->within(new IndexComponent('posts'), function ($browser) {
                        $browser->waitForTable()
                            ->selectAllMatching()
                            ->deleteSelected()
                            ->waitForTable()
                            ->assertSeeResource(1)
                            ->assertDontSeeResource(2)
                            ->assertDontSeeResource(3);
                    });

            $browser->blank();
        });
    }
}
