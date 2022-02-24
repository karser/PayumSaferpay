<?php declare(strict_types=1);

namespace Karser\PayumSaferpay\Tests\Functional;

use Karser\PayumSaferpay\Constants;
use Karser\PayumSaferpay\Request\Api\DeleteAlias;
use Payum\Core\Reply\HttpRedirect;
use DMS\PHPUnitExtensions\ArraySubset\ArraySubsetAsserts;

class CardAliasTest extends AbstractSaferpayTest
{
    use ArraySubsetAsserts;

    /**
     * @test
     */
    public function insertAlias(): void
    {
        $cardAlias = $this->createCardAlias([
            'Alias' => [
                'IdGenerator' => Constants::ALIAS_ID_GENERATOR_MANUAL,
                'Id' => $generatedId = uniqid('id', true),
                'Lifetime' => self::ALIAS_LIFETIME,
            ]
        ]);
        $token = $this->payum->getTokenFactory()->createCaptureToken(self::GATEWAY_NAME, $cardAlias, 'done.php');
        $this->payum->getHttpRequestVerifier()->invalidate($token); //no need to store token

        $reply = $this->insertCardAlias($token, $cardAlias);

        #assert redirected
        self::assertInstanceOf(HttpRedirect::class, $reply);
        self::assertStringStartsWith('https://test.saferpay.com/', $iframeUrl = $reply->getUrl());

        # submit form
        $iframeRedirect = $this->getThroughCheckout($reply->getUrl(), $formData = $this->composeFormData(self::CARD_SUCCESS, $cvc = false));

        self::assertStringStartsWith(self::HOST, $iframeRedirect);
        self::assertStringContainsString('payum_token='.$token->getHash(), $iframeRedirect);
        self::assertStringContainsString('success=1', $iframeRedirect);
        parse_str(parse_url($iframeRedirect, PHP_URL_QUERY), $_GET);

        $this->insertCardAlias($token, $cardAlias);

        $expiry = explode('/', $formData['Expiry']);

        self::assertArraySubset([
            'Alias' => [
                'Id' => $generatedId,
                'Lifetime' => self::ALIAS_LIFETIME,
            ],
            'PaymentMeans' => [
                'Card' => [
                    'MaskedNumber' => 'xxxxxxxxxxxx'.substr($formData['CardNumber'], -4),
                    'ExpYear' => (int) $expiry[1],
                    'ExpMonth' => (int) $expiry[0],
                    'HolderName' => $formData['HolderName'],
                ]
            ],
        ], $cardAlias->getDetails());
    }

    /**
     * @test
     */
    public function deleteAlias(): void
    {
        $cardAlias = $this->createInsertedCardAlias([]);
        self::assertNotNull($cardAlias->getDetails()['Alias']['Id']);
        $this->gateway->execute(new DeleteAlias($cardAlias));
        self::assertNull($cardAlias->getDetails()['Alias']['Id']);
    }
}
