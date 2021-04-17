<?php


namespace Svea\OrderComment\Model\Data\Terms;

/**
 * Class GenerateTermsUrl
 *
 * @package Svea\OrderComment\Model\Data\Terms
 */
class GenerateTermsUrl
{
    /**
     * @param $termsUrl
     * @param $urlPart
     * @param $text
     * @return array
     */
    public function generateTermsUrlAndText($termsUrl, $urlPart, $text): array
    {
        $scriptTag = "<a href=\"" . filter_var($termsUrl, FILTER_VALIDATE_URL) . "\">" . $urlPart . "</a>";
        if (strpos($text, $urlPart) !== false) {
            $replacedText = str_replace($urlPart, "", $text);
            $finalTerms = $replacedText . $scriptTag;
        } else {
            $replacedText = $text . ": ";
        }
        return [
            $replacedText,
            $scriptTag
        ];
    }
}