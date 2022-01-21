<?php declare(strict_types=1);
namespace Nubium\ThumbSign\Test;

use DateTimeImmutable;
use Nette\Http\Url;
use Nubium\ThumbSign\ThumbnailSecurityChecksumService;
use PHPUnit\Framework\TestCase;

class ThumbnailSecurityChecksumServiceTest extends TestCase
{


	/** @dataProvider dpParams */
	public function testParamsAreCorrect(Url $url, DateTimeImmutable $currentTime, string $expectedUrl): void
	{
		$service = $this->createThumbnailSecurityChecksumService($currentTime);

		$signedUrl = $service->signUrl($url);

		$this->assertEquals($expectedUrl, $signedUrl->getAbsoluteUrl());
	}

	/** @return array<string, array<string, mixed>> */
	public function dpParams(): array
	{
		return [
			'generic' => [
				'url' => new Url('https://thumb.uloz.to/d/7/6/d767ad66567c4968c92e006b6282e14f.260x170.jpg'),
				'currentTime' => new DateTimeImmutable('2019-09-26 15:32', new \DateTimeZone('Europe/Prague')),
				'expectedUrl' => 'https://thumb.uloz.to/d/7/6/d767ad66567c4968c92e006b6282e14f.260x170.jpg?vt=' . (new DateTimeImmutable('2019-09-27 23:59:59', new \DateTimeZone('Europe/Prague')))->format('U') .'&sg=Z_AdRmge8ecZJalf7W9SQQ&bl=',
			],
			'5 minutest till midnight' => [
				'url' => new Url('https://thumb.uloz.to/d/7/6/d767ad66567c4968c92e006b6282e14f.260x170.jpg'),
				'currentTime' => new DateTimeImmutable('2019-09-27 23:57:12', new \DateTimeZone('Europe/Prague')),
				'expectedUrl' => 'https://thumb.uloz.to/d/7/6/d767ad66567c4968c92e006b6282e14f.260x170.jpg?vt=' . (new DateTimeImmutable('2019-09-29 23:59:59', new \DateTimeZone('Europe/Prague')))->format('U') .'&sg=UrKNcXxIG1bpaxt3efcINQ&bl=',
			],
		];
	}


	public function testCustomValidityInterval(): void
	{
		$service = $this->createThumbnailSecurityChecksumService(new DateTimeImmutable('2020-01-23 00:11:22', new \DateTimeZone('Europe/Prague')));

		$signedUrl = $service->signUrl(new Url('http://example.com/path'), new \DateInterval('P2DT3H'));

		$this->assertSame(
			'http://example.com/path?vt=' . (new DateTimeImmutable('2020-01-25 03:11:22', new \DateTimeZone('Europe/Prague')))->format('U') . '&sg=nAWC_KNIXHJ5EAHMplehrw&bl=',
			$signedUrl->getAbsoluteUrl()
		);
	}

	private function createThumbnailSecurityChecksumService(DateTimeImmutable $currentTime): ThumbnailSecurityChecksumService
	{
		$mock = \Mockery::mock(ThumbnailSecurityChecksumService::class . '[getCurrentTime]', ['imgTopSecret'])
			->shouldAllowMockingProtectedMethods();

		$mock->allows([
			'getCurrentTime' => $currentTime
		]);

		$this->assertInstanceOf(ThumbnailSecurityChecksumService::class, $mock);
		return $mock;
	}
}
