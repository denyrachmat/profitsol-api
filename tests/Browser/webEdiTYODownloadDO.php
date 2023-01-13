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
            $url = $browser->driver->getCommandExecutor()->getAddressOfRemoteServer();
            $uri = '/session/' . $browser->driver->getSessionID() . '/chromium/send_command';
            $body = [
                'cmd' => 'Page.setDownloadBehavior',
                'params' => ['behavior' => 'allow', 'downloadPath' => '/public/']
            ];
            (new \GuzzleHttp\Client())->post($url . $uri, ['body' => json_encode($body)]);

            $browser->visit('https://epro5.b2b-exchange.jp/scm5/toyodenso/index.htm')
                ->assertSee('User ID') // Making sure the login form is showing.
                ->assertSee('Password')
                ->typeSlowly('UID', 'ID37005R')
                ->typeSlowly('PWD', '6y51j7rd')
                ->click('#B_LOGIN'); // Submit the form;
            try {
                $browser
                    ->waitForDialog()
                    ->acceptDialog()
                    ->pause(3000)
                    ->visit('https://epro5.b2b-exchange.jp/scm5/home?pkg=TodayInfo&fnc=List&act=todayInfo')
                    ->assertSee('PT. SUMITRONICS INDONESIA')
                    ->pause(3000)
                    ->click('button[onclick="javascript:csvMonthlyOrderData(document.FRM.SEQ_LISTA,false);"]')
                    ->pause(3000)
                    ->click('button[onclick="javascript:csvMonthlyOrderData(document.FRM.SEQ_LISTA,false);"]')
                    ->pause(3000)
                    ->visit('https://epro5.b2b-exchange.jp/scm5/home?pkg=Menu&fnc=List&act=display')
                    ->pause(3000)
                    ->click('#menu_logout')
                    ->pause(3000);
            } catch (\Throwable $th) {
                try {
                    $browser->visit('https://epro5.b2b-exchange.jp/scm5/home?pkg=TodayInfo&fnc=List&act=todayInfo')
                    ->assertSee('PT. SUMITRONICS INDONESIA')
                    ->pause(3000)
                    ->click('button[onclick="javascript:csvMonthlyOrderData(document.FRM.SEQ_LISTA,false);"]')
                    ->pause(3000)
                    ->click('button[onclick="javascript:csvMonthlyOrderData(document.FRM.SEQ_LISTA,false);"]')
                    ->pause(3000)
                    ->visit('https://epro5.b2b-exchange.jp/scm5/home?pkg=Menu&fnc=List&act=display')
                    ->pause(3000)
                    ->click('#menu_logout')
                    ->pause(3000);
                    //     ->click('img#menu_sintyaku');
                } catch (\Facebook\WebDriver\Exception\NoSuchElementException $th) {
                    throw $th;
                }
            }
        });
    }
}
