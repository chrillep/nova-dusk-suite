<?php

namespace Laravel\Nova\Tests\Browser;

use App\Models\User;
use Database\Factories\RoleFactory;
use Laravel\Dusk\Browser;
use Laravel\Nova\Testing\Browser\Components\IndexComponent;
use Laravel\Nova\Testing\Browser\Pages\Detail;
use Laravel\Nova\Tests\DuskTestCase;

class PivotActionTest extends DuskTestCase
{
    /**
     * @test
     */
    public function pivot_tables_can_be_referred_to_using_a_custom_name()
    {
        User::find(1)->roles()->attach(RoleFactory::new()->create());

        $this->browse(function (Browser $browser) {
            $browser->loginAs(1)
                    ->visit(new Detail('users', 1))
                    ->pause(1500)
                    ->within(new IndexComponent('roles'), function ($browser) {
                        $browser->waitForTable(25)
                                ->clickCheckboxForId(1)
                                ->openActionSelector()
                                ->within('@action-select', function ($browser) {
                                    $label = $browser->attribute('optgroup', 'label');
                                    $this->assertEquals('Role Assignment', $label);
                                });
                    });

            $browser->blank();
        });
    }

    /**
     * @test
     */
    public function actions_can_be_executed_against_pivot_rows()
    {
        User::find(1)->roles()->attach(RoleFactory::new()->create());

        $this->browse(function (Browser $browser) {
            $browser->loginAs(1)
                    ->visit(new Detail('users', 1))
                    ->pause(1500)
                    ->within(new IndexComponent('roles'), function ($browser) {
                        $browser->waitForTable(25)
                                ->clickCheckboxForId(1)
                                ->runAction('update-pivot-notes');
                    });

            $browser->waitForText('The action ran successfully!', 25);

            $this->assertEquals('Pivot Action Notes', User::find(1)->roles()->first()->pivot->notes);

            $browser->blank();
        });
    }
}
