<?php

/**
* @package   s9e\AddonBuilder\MediaSites
* @copyright Copyright (c) 2017-2020 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\AddonBuilder\MediaSites\TemplateNormalizations;

use DOMAttr;
use DOMElement;
use DOMNode;
use DOMText;
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
			'width'     => '100%'
		],
		'span' => [
			'display'   => 'inline-block',
			'max-width' => '640px',
			'width'     => '100%'
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
	protected $queries = [
		'//*[@data-s9e-mediaembed]//@style',
		'//*[@data-s9e-mediaembed]//xsl:attribute[@name = "style"]//text()'
	];

	/**
	* {@inheritdoc}
	*/
	protected function normalizeAttribute(DOMAttr $attribute)
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
	* {@inheritdoc}
	*/
	protected function normalizeNode(DOMNode $node)
	{
		if ($node instanceof DOMText)
		{
			$this->normalizeTextNode($node);
		}
		else
		{
			parent::normalizeNode($node);
		}
	}

	/**
	* Normalize given text node
	*
	* @param  DOMText $node
	* @return void
	*/
	protected function normalizeTextNode(DOMText $node)
	{
		$node->textContent = $this->removeDefaultStyle($node->textContent, $node);
	}

	/**
	* Return the default values that apply to given context node
	*
	* @param  DOMNode $node Context node
	* @return array
	*/
	protected function getDefaultValues(DOMNode $node)
	{
		preg_match_all('(iframe|span)', $node->getNodePath(), $m);
		$key = implode(' ', $m[0]);

		return (isset($this->defaultValues[$key])) ? $this->defaultValues[$key] : [];
	}

	/**
	* Remove the default style from text found in given node
	*
	* @param  string  $text Original CSS
	* @param  DOMNode $node Context node
	* @return string        Cleaned-up CSS
	*/
	protected function removeDefaultStyle($text, DOMNode $node)
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
	protected function removeDefaultValues($text, array $defaults)
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