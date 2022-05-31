<?php

namespace App\Twig;

use App\Service\MarkdownHelper;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class MarkdownExtension extends AbstractExtension {
	public function __construct(private MarkdownHelper $markdownHelper)
	{
	}

	public function getFilters(): array {
		return [
			// If your filter generates SAFE HTML, you should add a third
			// parameter: ['is_safe' => ['html']]
			// Reference: https://twig.symfony.com/doc/2.x/advanced.html#automatic-escaping
			new TwigFilter('parse_markdown', [$this, 'parseMarkdown'], ['is_safe' => ['html']]),
		];
	}

	public function parseMarkdown($value) {
		return $this->markdownHelper->parse($value);
	}
}
