<?php

namespace Laravel\Nova\Tests\Browser;

use App\Models\User;
use Database\Factories\RoleFactory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Laravel\Dusk\Browser;
use Laravel\Nova\Testing\Browser\Components\IndexComponent;
use Laravel\Nova\Testing\Browser\Pages\Detail;
use Laravel\Nova\Testing\Browser\Pages\UpdateAttached;
use Laravel\Nova\Tests\DuskTestCase;

class UpdateAttachedTest extends DuskTestCase
{
    /**
     * @test
     */
    public function attached_resource_can_be_updated()
    {
        $role = RoleFactory::new()->create();
        User::find(1)->roles()->attach($role, ['notes' => 'Test Notes']);

        $this->browse(function (Browser $browser) {
            $browser->loginAs(1)
                    ->visit(new Detail('users', 1))
                    ->within(new IndexComponent('roles'), function ($browser) {
                        $browser->waitForTable()
                                ->click('@1-edit-attached-button');
                    })
                    ->on(new UpdateAttached('users', 1, 'roles', 1))
                    ->whenAvailable('@via-resource-field', function ($browser) {
                        $browser->assertSee('User')->assertSee('1');
                    })
                    ->assertDisabled('select[dusk="attachable-select"]')
                    ->assertInputValue('@notes', 'Test Notes')
                    ->type('@notes', 'Test Notes Updated')
                    ->update()
                    ->waitForText('The resource was updated!');

            $this->assertEquals('Test Notes Updated', User::with('roles')->find(1)->roles->first()->pivot->notes);

            $browser->blank();
        });
    }

    /**
     * @test
     */
    public function attached_resource_can_be_updated_and_can_continue_editing()
    {
        $role = RoleFactory::new()->create();
        User::find(1)->roles()->attach($role, ['notes' => 'Test Notes']);

        $this->browse(function (Browser $browser) {
            $browser->loginAs(1)
                    ->visit(new Detail('users', 1))
                    ->within(new IndexComponent('roles'), function ($browser) {
                        $browser->waitForTable()
                                ->click('@1-edit-attached-button');
                    })
                    ->on(new UpdateAttached('users', 1, 'roles', 1))
                    ->whenAvailable('@via-resource-field', function ($browser) {
                        $browser->assertSee('User')->assertSee('1');
                    })
                    ->whenAvailable('select[dusk="attachable-select"]', function ($browser) {
                        $browser->assertDisabled('')
                                ->assertSelected('', '1');
                    })
                    ->type('@notes', 'Test Notes Updated')
                    ->updateAndContinueEditing()
                    ->waitForText('The resource was updated!')
                    ->on(new UpdateAttached('users', 1, 'roles', 1))
                    ->whenAvailable('select[dusk="attachable-select"]', function ($browser) {
                        $browser->assertDisabled('')
                                ->assertSelected('', '1');
                    });

            $this->assertEquals('Test Notes Updated', User::with('roles')->find(1)->roles->first()->pivot->notes);

            $browser->blank();
        });
    }

    /**
     * @test
     */
    public function validation_errors_are_displayed()
    {
        $role = RoleFactory::new()->create();
        User::find(1)->roles()->attach($role, ['notes' => 'Test Notes']);

        $this->browse(function (Browser $browser) {
            $browser->loginAs(1)
                    ->visit(new Detail('users', 1))
                    ->within(new IndexComponent('roles'), function ($browser) {
                        $browser->waitForTable()
                                ->click('@1-edit-attached-button');
                    })
                    ->on(new UpdateAttached('users', 1, 'roles', 1))
                    ->whenAvailable('@via-resource-field', function ($browser) {
                        $browser->assertSee('User')->assertSee('1');
                    })
                    ->type('@notes', str_repeat('A', 30))
                    ->update()
                    ->waitForText('There was a problem submitting the form.')
                    ->assertSee('The notes must not be greater than 20 characters.')
                    ->click('@cancel-update-attached-button');

            $this->assertEquals('Test Notes', User::with('roles')->find(1)->roles->first()->pivot->notes);

            $browser->blank();
        });
    }

    /**
     * @test
     */
    public function it_can_update_attached_duplicate_relations_pivot()
    {
        Carbon::setTestNow($now = Carbon::now());

        DB::table('book_purchases')->insert([
            ['user_id' => 1, 'book_id' => 4, 'type' => 'gift', 'price' => 34, 'purchased_at' => $now->toDatetimeString()],
            ['user_id' => 1, 'book_id' => 4, 'type' => 'gift', 'price' => 32, 'purchased_at' => $now->toDatetimeString()],
        ]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs(1)
                    ->visit(new Detail('users', 1))
                    ->within(new IndexComponent('books', 'giftBooks'), function ($browser) {
                        $browser->waitForTable()
                            ->within('tr[data-pivot-id="2"]', function ($browser) {
                                $browser->click('@4-edit-attached-button');
                            });
                    })
                    ->on(new UpdateAttached('users', 1, 'books', 4))
                    ->whenAvailable('@via-resource-field', function ($browser) {
                        $browser->assertSee('User')->assertSee('1');
                    })
                    ->whenAvailable('@price', function ($browser) {
                        $browser->type('', '43');
                    })
                    ->update()
                    ->waitForText('The resource was updated!')
                    ->on(new Detail('users', 1))
                    ->within(new IndexComponent('books', 'giftBooks'), function ($browser) {
                        $browser->waitForTable()
                            ->within('tr[data-pivot-id="1"]', function ($browser) {
                                $browser->assertSee('$34.00');
                            })
                            ->within('tr[data-pivot-id="2"]', function ($browser) {
                                $browser->assertSee('$43.00');
                            });
                    });
        });
    }
}
