<?php

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;


class GenerateTermsUrlTest extends TestCase
{

    /**
     * @var \Svea\OrderComment\Model\Data\Terms\GenerateTermsUrl
     */
    protected $testObject;

    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);

        $this->testObject = $objectManager->getObject('Svea\OrderComment\Model\Data\Terms\GenerateTermsUrl');
    }

    public function urlProvider()
    {
        return [
           'normal' => [
               "https://www.sveapayments.fi/hubfs/Payments/Sopimusehdot_selosteet/Kayttoehdot_(ostaja)_ENG.pdf",
               "terms and conditions",
               "Check terms and conditions",
               "Check ",
               "<a href=\"https://www.sveapayments.fi/hubfs/Payments/Sopimusehdot_selosteet/Kayttoehdot_(ostaja)_ENG.pdf\">terms and conditions</a>",
           ],
            'Pathless url' => [
                "https://www.sveapayments.fi",
                "terms and conditions",
                "Check terms and conditions",
                "Check ",
                "<a href=\"https://www.sveapayments.fi\">terms and conditions</a>",
            ],
            'no substring' => [
                "https://google.com",
                "terms and conditions",
                "Check terms",
                "Check terms: ",
                "<a href=\"https://google.com\">terms and conditions</a>",
            ],
        ];
    }

    /**
     * @param $termsUrl
     * @param $urlPart
     * @param $text
     * @param $expectedText
     * @param $expectedHtmlTag
     * @dataProvider urlProvider
     */
    public function testGenerateTermsUrlAndText($termsUrl, $urlPart, $text, $expectedText, $expectedHtmlTag)
    {
        $expected = [
            $expectedText,
            $expectedHtmlTag
        ];

        $result = $this->testObject->generateTermsUrlAndText($termsUrl, $urlPart, $text);

        $this->assertEquals($expected, $result);
    }

}