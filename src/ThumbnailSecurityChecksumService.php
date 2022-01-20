<?php declare(strict_types=1);
namespace Nubium\ThumbSign;

use DateInterval;
use DateTimeImmutable;
use DateTimeZone;
use Nette\Http\Url;

class ThumbnailSecurityChecksumService
{
	private string $secret;


	public function __construct(string $secret)
	{
		$this->secret = $secret;
	}


	public function signUrl(Url $url, ?DateInterval $customValidityInterval = null): Url
	{
		$validUntil = $this->computeValidity($customValidityInterval);

		$sg = md5(
			$url->getPath()
			. $this->secret
			. (string) $validUntil->format('U')
		, true);

		return $url
			->setQueryParameter('vt', (string) $validUntil->format('U'))
			->setQueryParameter('sg', trim(strtr(base64_encode($sg), '+/', '-_'), '='))
			->setQueryParameter('bl', ''); # blank parameter because of prevent wrong appended parameters
	}


	protected function getCurrentTime(): DateTimeImmutable
	{
		return new DateTimeImmutable('now', new DateTimeZone('Europe/Prague'));
	}


	private function computeValidity(?DateInterval $customValidityInterval = null): DateTimeImmutable
	{
		$currentTime = $this->getCurrentTime();

		if (!$customValidityInterval) {
			$validity = $currentTime
				->modify('tomorrow 23:59:59');

			// if less than 5 minutes till midnight, add one more day to validity
			if ($currentTime->add(new DateInterval('PT5M')) > $currentTime->modify('tomorrow')) {
				$validity = $validity->modify('tomorrow 23:59:59');
			}
		} else {
			$validity = $currentTime->add($customValidityInterval);
		}

		return $validity;
	}

}
