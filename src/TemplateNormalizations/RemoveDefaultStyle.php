<?php

/**
* @package   s9e\AddonBuilder\MediaSites
* @copyright Copyright (c) The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\AddonBuilder\MediaSites\TemplateNormalizations;

use s9e\SweetDOM\Attr;
use s9e\SweetDOM\Element;
use s9e\SweetDOM\Text;
use s9e\TextFormatter\Configurator\TemplateNormalizations\AbstractNormalization;

class RemoveDefaultStyle extends AbstractNormalization
{
	/**
	* @var array Default CSS Style for each supported selector
	*/
	protected $defaultValues = [
		'iframe' => [
			'border'    => '0',
			'max-width' => '100%',
			'width'     => '640px'
		],
		'span' => [
			'display'   => 'inline-block',
			'max-width' => '100%',
			'width'     => '640px'
		],
		'span span' => [
			'display'        => 'block',
			'overflow'       => 'hidden',
			'padding-bottom' => '56.25%',
			'position'       => 'relative'
		],
		'span span iframe' => [
			'border'   => '0',
			'height'   => '100%',
			'left'     => '0',
			'position' => 'absolute',
			'width'    => '100%'
		]
	];

	/**
	* {@inheritdoc}
	*/
	protected array $queries = [
		'//*[@data-s9e-mediaembed]//@style',
		'//*[@data-s9e-mediaembed]//xsl:attribute[@name = "style"]//text()'
	];

	/**
	* {@inheritdoc}
	*/
	protected function normalizeAttribute(Attr $attribute): void
	{
		$value = $this->removeDefaultStyle($attribute->value, $attribute);
		if ($value === '')
		{
			$attribute->parentNode->removeAttribute('style');
		}
		else
		{
			$attribute->value = $value;
		}
	}

	/**
	* Normalize given text node
	*/
	protected function normalizeText(Text $node): void
	{
		$node->textContent = $this->removeDefaultStyle($node->textContent, $node);
	}

	/**
	* Return the default values that apply to given context node
	*
	* @param  Attr|Text $node Context node
	* @return array
	*/
	protected function getDefaultValues(Attr|Text $node): array
	{
		preg_match_all('(iframe|span)', $node->getNodePath(), $m);
		$key = implode(' ', $m[0]);

		return (isset($this->defaultValues[$key])) ? $this->defaultValues[$key] : [];
	}

	/**
	* Remove the default style from text found in given node
	*
	* @param  string    $text Original CSS
	* @param  Attr|Text $node Context node
	* @return string          Cleaned-up CSS
	*/
	protected function removeDefaultStyle(string $text, Attr|Text $node): string
	{
		return $this->removeDefaultValues($text, $this->getDefaultValues($node));
	}

	/**
	* Remove CSS defaults from given text
	*
	* @param  string $text     CSS string
	* @param  array  $defaults Associative array in the form [property => value]
	* @return string           Cleaned-up CSS
	*/
	protected function removeDefaultValues(string $text, array $defaults): string
	{
		foreach ($defaults as $k => $v)
		{
			$expr   = preg_quote($k . ':' . $v);
			$regexp = '(^' . $expr . '(?:;|$)|;' . $expr . '(?=;|$))';
			$text   = preg_replace($regexp, '', $text);
		}

		return $text;
	}
}