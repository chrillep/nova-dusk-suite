<?php

namespace Laravel\Nova\Tests\Browser;

use Laravel\Dusk\Browser;
use Laravel\Nova\Testing\Browser\Pages\Create;
use Laravel\Nova\Testing\Browser\Pages\Detail;
use Laravel\Nova\Tests\DuskTestCase;

class CreateWithHasOneTest extends DuskTestCase
{
    public function test_has_one_should_be_filled()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs(1)
                ->visit(new Create('people'))
                ->type('@name', 'Adam Wathan')
                ->create()
                ->visit(new Detail('people', 1))
                ->runCreateRelation('employees')
                ->assertDisabled('select[dusk="people"]');

            $browser->blank();
        });
    }
}
