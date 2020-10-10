<?php declare(strict_types=1);

use Nesk\Puphpeteer\Puppeteer;

require_once 'vendor/autoload.php';

$puppeteer = new Puppeteer();
$browser   = $puppeteer->launch(
	[
		'width'  => '1920',
		'height' => '1080',
		'args'   =>
			[
				// this is required for dockerized puppeteer
				'--no-sandbox',
				'--disable-setuid-sandbox',
				'--disable-dev-shm-usage',
			],
	]
);
$page      = $browser->newPage();
$page->goto(
	'https://labs.strava.com/gpx-to-route',
	[
		'waitUntil' => 'networkidle0',
	]
);

echo "Title is {$page->title()}.\n";

$gpxFileInput = $page->querySelector('#gpxFile');
$gpxFileInput->uploadFile(__DIR__ . '/examples/strava_designed_route.gpx');
$page->waitForNavigation(
	[
		'waitUntil' => 'networkidle0',
	]
);
$page->waitFor(2000);

$page->screenshot(
	[
		'path'     => 'output/route.png',
		'fullPage' => true,
	]
);

$browser->close();
