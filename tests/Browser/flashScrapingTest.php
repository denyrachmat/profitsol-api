<?php

namespace Tests\Browser;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class flashScrapingTest extends DuskTestCase
{
    /**
     * A Dusk test example.
     *
     * @return void
     */
    public function testExample()
    {
        $this->browse(function (Browser $browser) {
            // $browser->visit('chrome://settings/content/siteDetails?site=http://abeall.com')
            //     ->click('#plugins #details #permissionItem #permission')
            //     // ->clickAtXPath('//*div[@id="plugins"]/[@id="details"]/[@id="permissionItem"]/[@id="permission"]')
                // ->scrollIntoView('Flash');
            //     // ->clickAtXPath('//*div[2]/settings-main//settings-basic-page//div[1]/settings-section[4]/settings-privacy-page//settings-animated-pages/settings-subpage/site-details//div[3]/site-details-permission[7]//div/div/select/option[2]')
            //     ->pause(10000);

            $browser->visit('http://abeall.com')
                    ->pause(10000)
                    ->assertSee('contact@abeall.com')
                    ->moveMouse(712, 385)
                    ->click()
                    ->pause(10000);
        });
    }
}
