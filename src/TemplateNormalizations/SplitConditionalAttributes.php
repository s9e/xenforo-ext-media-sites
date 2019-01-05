<?php

/**
* @package   s9e\AddonBuilder\MediaSites
* @copyright Copyright (c) 2017-2019 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\AddonBuilder\MediaSites\TemplateNormalizations;

use DOMElement;
use s9e\TextFormatter\Configurator\TemplateNormalizations\AbstractChooseOptimization;

class SplitConditionalAttributes extends AbstractChooseOptimization
{
	/**
	* {@inheritdoc}
	*/
	protected function optimizeChoose()
	{
		if (!$this->hasOtherwise())
		{
			return;
		}

		// Ensure that every branch starts with a matching xsl:attribute element
		$attrNames = [];
		foreach ($this->getBranches() as $branch)
		{
			if (!isset($branch->firstChild) || !$this->isXsl($branch->firstChild, 'attribute'))
			{
				return;
			}
			$attrNames[] = $branch->firstChild->getAttribute('name');
		}
		if (count(array_unique($attrNames)) > 1)
		{
			return;
		}

		// Create the new xsl:attribute
		$attribute = $this->choose->parentNode->insertBefore(
			$this->createElement('xsl:attribute'),
			$this->choose
		);
		$attribute->setAttribute('name', $attrNames[0]);

		// Create the new xsl:choose
		$choose = $attribute->appendChild($this->createElement('xsl:choose'));
		foreach ($this->getBranches() as $branch)
		{
			$newBranch = $choose->appendChild($this->createElement($branch->nodeName));
			foreach ($branch->attributes as $attribute)
			{
				$newBranch->setAttribute($attribute->nodeName, $attribute->nodeValue);
			}
			while ($branch->firstChild->firstChild)
			{
				$newBranch->appendChild($branch->firstChild->firstChild);
			}
			$branch->removeChild($branch->firstChild);
		}
	}
}