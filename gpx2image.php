<?php declare(strict_types=1);

use Nesk\Puphpeteer\Puppeteer;

require_once 'vendor/autoload.php';

if (isset($argv[1]))
{
	$filePath = __DIR__ . '/' . $argv[1];

	if (!is_readable($filePath))
	{
		throw new Exception(".gpx file $filePath is not readable.");
	}

	$gpxContent = file_get_contents($filePath);
	$hash       = md5_file($filePath);
} else
{
	// Web request.

	$gpxContent = file_get_contents('php://input');
	$hash       = md5($gpxContent);
	$filePath   = "/tmp/$hash.gpx";
	file_put_contents($filePath, $gpxContent);
}

function isValidGpx(string $gpx): bool
{

	if (stripos($gpx, '<gpx') === false)
	{
		return false;
	}

	if (stripos($gpx, '</gpx>') === false)
	{
		return false;
	}

	return true;
}

if (!isValidGpx($gpxContent))
{
	throw new InvalidArgumentException(".gpx file $filePath is not valid xml.");
}

$puppeteer = new Puppeteer();
$browser   = $puppeteer->launch(
	[
		'defaultViewport' => [
			'width'  => 1920,
			'height' => 1080,
		],
		'args'            =>
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
$gpxFileInput->uploadFile($filePath);
$page->waitForNavigation(
	[
		'waitUntil' => 'networkidle0',
	]
);
$page->waitFor(1000);

$page->screenshot(
	[
		'path'     => "storage/$hash.png",
		'fullPage' => false,
		'clip' => [
			'x' => 374,
			'y' => 59,
			'width' => 1546,
			'height' => 1021,
		],
	]
);

$browser->close();

echo "Done, image is $hash.png\n";

// TODO reply with image directly in case of web request.
