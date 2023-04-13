<?php

namespace XF\Repository;
class BbCodeMediaSite
{
	public function __construct(protected array $willReturn)
	{
	}

	public function fetch(): array
	{
		return [];
	}

	public function findActiveMediaSites()
	{
		return $this;
	}

	public function urlMatchesMediaSiteList(string $url, array $sites): ?array
	{
		return $this->willReturn[$url] ?? null;
	}
}