<?php declare(strict_types=1);

namespace Karser\PayumSaferpay\Tests\Functional;

use Payum\Core\Reply\HttpRedirect;
use Payum\Core\Request\GetHumanStatus;

class TransactionTest extends AbstractSaferpayTest
{
    public function paymentStatusProvider(): array
    {
        return [
            'success' => [
                $this->composeFormData(self::CARD_SUCCESS),
                $behavior = 'submit',
                GetHumanStatus::STATUS_CAPTURED,
            ],
            'success 3d' => [
                $this->composeFormData(self::CARD_SUCCESS_LS_AUTH_3D),
                $behavior = 'submit',
                GetHumanStatus::STATUS_CAPTURED,
            ],
            'failed because of card' => [
                $this->composeFormData(self::CARD_FAILED),
                $behavior = 'submit',
                GetHumanStatus::STATUS_FAILED,
            ],
            'failed by clicking cancel on the last step' => [
                $this->composeFormData(self::CARD_SUCCESS_LS_AUTH_3D),
                $behavior = 'fail',
                GetHumanStatus::STATUS_FAILED,
            ],
            'canceled by clicking cancel on the first step' => [
                [],
                $behavior = 'abort',
                GetHumanStatus::STATUS_CANCELED,
            ],
        ];
    }

    /**
     * @test
     * @dataProvider paymentStatusProvider
     */
    public function paymentStatus($formData, $formBehavior, string $expectedStatus): void
    {
        $payment = $this->createPayment();

        $token = $this->payum->getTokenFactory()->createCaptureToken(self::GATEWAY_NAME, $payment, 'done.php');
        $this->payum->getHttpRequestVerifier()->invalidate($token); //no need to store token

        # INIT transaction
        /** @var HttpRedirect $reply */
        $reply = $this->capture($token, $payment);
        $this->assertStatus(GetHumanStatus::STATUS_PENDING, $payment);

        #assert redirected
        self::assertInstanceOf(HttpRedirect::class, $reply);
        $iframeUrl = $reply->getUrl();
        self::assertNotNull($iframeUrl);
        self::assertStringStartsWith('https://test.saferpay.com/', $iframeUrl);

        # submit form
        $iframeRedirect = $this->getThroughCheckout($iframeUrl, $formData, $formBehavior);
        self::assertNotNull($iframeRedirect);
        self::assertStringStartsWith(self::HOST, $iframeRedirect);
        self::assertStringContainsString('payum_token='.$token->getHash(), $iframeRedirect);
        parse_str(parse_url($iframeRedirect, PHP_URL_QUERY) ?: '', $_GET);

        # AUTHORIZE AND CAPTURE
        $this->capture($token, $payment);
        $this->assertStatus($expectedStatus, $payment);
    }
}
