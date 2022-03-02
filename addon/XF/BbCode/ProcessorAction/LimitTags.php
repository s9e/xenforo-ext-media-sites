<?php

namespace s9e\MediaSites\XF\BbCode\ProcessorAction;



/**
 * Extends \XF\BbCode\ProcessorAction\LimitTags
 */
class LimitTags extends XFCP_LimitTags
{
    public function filterTag(array $tag)
    {
        if ($tag['tag'] === 'url' && isset($this->disabledTags['media']) &&
            ((\XF::options()->s9e_MediaSites_Markup ?? '') === 'url') &&
            (($tag['option']['media'] ?? '') !== '') &&
            (\strtolower($tag['option']['unfurl'] ?? '') === 'true'))
        {
            $this->disabledSeen['media'] = true;
            if ($this->stripDisabled)
            {
                return false; // remove the tag wrapping, keep the children
            }

            return null; // do nothing
        }

        return parent::filterTag($tag);
    }
}