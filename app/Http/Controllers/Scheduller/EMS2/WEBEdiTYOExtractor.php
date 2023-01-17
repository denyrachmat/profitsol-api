<?php

namespace App\Http\Controllers\Scheduller\EMS2;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Laravel\Dusk\Browser;
use Laravel\Dusk\Chrome\ChromeProcess;
use Laravel\Dusk\ElementResolver;

class WEBEdiTYOExtractor extends Controller
{
    public function downloadData()
    {
        $process = (new ChromeProcess)->toProcess();
        //$process->start();
        $process->start(null, [
            'SystemRoot' => 'C:\\WINDOWS',
            'TEMP' => 'C:\Users\deny-rachmat\AppData\Local\Temp',
        ]);
        $options = new ChromeOptions;
        $options->setBinary("C:\Program Files\Google\Chrome\Application\chrome.exe");
        $capabilities = DesiredCapabilities::chrome()->setCapability(ChromeOptions::CAPABILITY, $options);
        $driver = retry(5, function () use($capabilities) {
            return RemoteWebDriver::create('http://localhost:9515', $capabilities);
        }, 50);
        $browser = new Browser($driver);
        $browser->visit('https://www.google.com');
        $browser->quit();
        $process->stop();
    }
}