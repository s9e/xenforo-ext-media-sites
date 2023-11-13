<?php

/**
* @package   s9e\AddonBuilder\MediaSites
* @copyright Copyright (c) The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\AddonBuilder\MediaSites\TemplateNormalizations;

use s9e\TextFormatter\Configurator\TemplateNormalizations\AbstractChooseOptimization;

class SplitConditionalAttributes extends AbstractChooseOptimization
{
	/**
	* {@inheritdoc}
	*/
	protected function optimizeChoose(): void
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
		$attribute = $this->choose->beforeXslAttribute(name: $attrNames[0]);

		// Create the new xsl:choose
		$choose = $attribute->appendXslChoose();
		foreach ($this->getBranches() as $branch)
		{
			$newBranch = $choose->appendElement($branch->nodeName);
			foreach ($branch->attributes as $attribute)
			{
				$newBranch->setAttribute($attribute->nodeName, $attribute->nodeValue);
			}
			while ($branch->firstChild->firstChild)
			{
				$newBranch->appendChild($branch->firstChild->firstChild);
			}
			$branch->firstChild->remove();
		}
	}
}