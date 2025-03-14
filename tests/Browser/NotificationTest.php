<?php

namespace Laravel\Nova\Tests\Browser;

use App\Models\User;
use Laravel\Dusk\Browser;
use Laravel\Nova\Notifications\NovaNotification;
use Laravel\Nova\Testing\Browser\Components\HeaderComponent;
use Laravel\Nova\Testing\Browser\Pages\Dashboard;
use Laravel\Nova\Tests\DuskTestCase;
use Laravel\Nova\URL;

class NotificationTest extends DuskTestCase
{
    /** @test */
    public function it_can_view_own_notitications()
    {
        $user = User::find(1);
        $user->notify(
            NovaNotification::make()
                ->message('Just a test notification')
                ->url(URL::make("/resources/users/{$user->id}"))
        );

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                    ->visit(new Dashboard())
                    ->within(new HeaderComponent(), function ($browser) {
                        $browser->showNotificationPanel(function ($browser) {
                            $browser->assertSee('Just a test notification');
                        });
                    });

            $browser->blank();
        });
    }
}
