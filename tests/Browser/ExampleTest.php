<?php

namespace Tests\Browser;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;
use Facebook\WebDriver\WebDriverKeys;
use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Cookie;
use Facebook\WebDriver\WebDriverOptions;
use Laravel\Dusk\Chrome\ChromeProcess;

class ExampleTest extends DuskTestCase
{
    /**
     * A basic browser test example.
     *
     * @return void
     */
    public function testBasicExample()
    {
        //the cookie file name
        $cookie_file = 'cookies.txt';
        $ls_file = 'localstoragedeny.txt';

        $process = (new ChromeProcess)->toProcess();
        $process->start();
        $options = (new ChromeOptions)->addArguments(['--disable-gpu', '--enable-file-cookies', '--no-sandbox']);
        $capabilities = DesiredCapabilities::chrome()->setCapability(ChromeOptions::CAPABILITY, $options);
        $driver = retry(5, function () use ($capabilities) {
            return RemoteWebDriver::create('http://localhost:9515', $capabilities);
        }, 50);

        //start the browser
        $browser = new Browser($driver);
        $browser->visit('http://web.whatsapp.com');
        //Cookie management - if there's a stored cookie file, load the contents         
        if (file_exists($cookie_file)) {
            //Get cookies from storage
            $cookies = unserialize(file_get_contents($cookie_file));
            //Add each cookie to this session
            foreach ($cookies as $key => $cookie) {
                $driver->manage()->addCookie($cookie);
            }
        }

        if (file_exists($ls_file)) {
            $ls = json_decode(file_get_contents($ls_file));

            foreach ($ls as $key2 => $val){
                $browser
                ->script("
                    window.localStorage.setItem('".$val->id."','".$val->value."');
                ");
            }
        }

        $browser
            ->visit('http://web.whatsapp.com')
            ->pause('10000')
            ->waitForText('Keep your phone connected');

        if (!file_exists($ls_file)) {
            $getlocalWhatsapp = $browser
                ->script('
                    const storenya = Object.keys(window.localStorage)
                    const hasil = []
                    storenya.map(val => {
                        hasil.push({id: val, value: window.localStorage.getItem(val)})                      
                    })
    
                    return JSON.stringify(hasil)
                ');
            file_put_contents($ls_file, $getlocalWhatsapp);
        }

        //Action on whatsapp
        $browser
            ->click('.PVMjB:nth-child(2)')
            ->type('._3FRCZ', 'Wedus Baru') // Class searching contact
            ->keys('._3FRCZ', '{enter}')
            ->keys('._3uMse', // Class Message
                'Halo selamat pagi.', 
                [WebDriverKeys::SHIFT, WebDriverKeys::ENTER], 
                'Ini adalah testing Bot Whatsapp', 
                [WebDriverKeys::SHIFT, WebDriverKeys::ENTER],
                'Jangan khawatir ya.'
                // '{enter}'
            )
            ->pause('10000');

        $browser->clickAtPoint(0,0);

        //now the cookies have been set, get and store them for future runs
        if (!file_exists($cookie_file)) {
            $cookies = $driver->manage()->getCookies();
            file_put_contents($cookie_file, serialize($cookies));
        }
    }
}
