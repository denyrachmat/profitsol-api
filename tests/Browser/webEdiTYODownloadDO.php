<?php

namespace Tests\Browser;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class webEdiTYODownloadDO extends DuskTestCase
{
    /**
     * A Dusk test example.
     *
     * @return void
     */
    public function testExample()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('https://epro5.b2b-exchange.jp/scm5/toyodenso/index.htm')
                ->assertSee('User ID') // Making sure the login form is showing.
                ->assertSee('Password')
                ->type('UID', 'ID37005R')
                ->type('PWD', '6y51j7rd')
                ->press('#B_LOGIN'); // Submit the form;

            if ($browser->assertDialogOpened('ID37005R は使用中です')) {
                $browser->waitForDialog()->dismissDialog();
            } else {
                $browser->assertSee('Show new information')->press('Show new information');
            }
        });
    }
}
