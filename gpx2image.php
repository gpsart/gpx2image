<?php declare(strict_types=1);

use Nesk\Puphpeteer\Puppeteer;

require_once 'vendor/autoload.php';

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

try
{
	if (isset($argv[1]))
	{
		$filePath = __DIR__ . '/' . $argv[1];

		if (!is_readable($filePath))
		{
			throw new Exception(".gpx file $filePath is not readable.");
		}

		$gpxContent = file_get_contents($filePath);
		$hash       = md5_file($filePath);
		$isCli      = true;
	} else
	{
		// Web request.

		$gpxContent = file_get_contents('php://input');
		$hash       = md5($gpxContent);
		$filePath   = "/tmp/$hash.gpx";
		file_put_contents($filePath, $gpxContent);
		$isCli = false;
	}

	$imagePath = __DIR__ . "/storage/$hash.png";

	if (!isValidGpx($gpxContent))
	{
		throw new InvalidArgumentException(".gpx file $filePath is not valid xml.");
	}

	if (!file_exists($imagePath))
	{

		if (!$isCli) {
			header('X-Cache-Hit: false');
		}

		// Not found in cache => download.

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

		if ($isCli)
		{
			echo "Title is {$page->title()}.\n";
		}

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
				'clip'     => [
					'x'      => 374,
					'y'      => 59,
					'width'  => 1546,
					'height' => 1021,
				],
			]
		);

		$browser->close();
	} else {

		// Image found in cache.
		if (!$isCli) {
			header('X-Cache-Hit: true');
		}
	}
}
catch (Error $exception)
{

	if ($isCli)
	{
		throw $exception;
	} else
	{
		header('Content-Type: application/json');
		echo json_encode(
			[
				'success' => false,
				'error'   => $exception->getMessage(),
			]
		);
	}
}

if ($isCli)
{
	echo "$hash.png\n";
} else
{
	header('Content-Type: application/json');
	echo json_encode(
		[
			'success' => true,
			'image'   => "https://cdn.gpsart.app/storage/$hash.png",
		]
	);
}
