<?php declare(strict_types=1);

namespace Karser\PayumSaferpay\Tests\Functional;

use Karser\PayumSaferpay\Constants;
use Payum\Core\Reply\HttpRedirect;
use Payum\Core\Request\GetHumanStatus;
use Payum\Core\Request\Notify;

class PaymentPageTest extends AbstractSaferpayTest
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
        $payment = $this->createPayment(['Interface' => Constants::INTERFACE_PAYMENT_PAGE]);

        $token = $this->payum->getTokenFactory()->createCaptureToken(self::GATEWAY_NAME, $payment, 'done.php');
        $this->payum->getHttpRequestVerifier()->invalidate($token); //no need to store token

        # INIT transaction
        $reply = $this->capture($token, $payment);
        $this->assertStatus(GetHumanStatus::STATUS_PENDING, $payment);

        #assert redirected
        self::assertInstanceOf(HttpRedirect::class, $reply);
        self::assertStringStartsWith('https://test.saferpay.com/', $iframeUrl = $reply->getUrl());

        # submit form
        $iframeRedirect = $this->getThroughCheckout($iframeUrl, $formData, $formBehavior);

        self::assertStringStartsWith(self::HOST, $iframeRedirect);
        self::assertStringContainsString('payum_token='.$token->getHash(), $iframeRedirect);
        parse_str(parse_url($iframeRedirect, PHP_URL_QUERY), $_GET);

        # AUTHORIZE AND CAPTURE
        $this->capture($token, $payment);
        $this->assertStatus($expectedStatus, $payment);
    }

    /**
     * @test
     */
    public function paymentNotification(): void
    {
        $payment = $this->createPayment(['Interface' => Constants::INTERFACE_PAYMENT_PAGE]);

        $token = $this->payum->getTokenFactory()->createCaptureToken(self::GATEWAY_NAME, $payment, 'done.php');
        $this->payum->getHttpRequestVerifier()->invalidate($token); //no need to store token

        # INIT transaction
        $reply = $this->capture($token, $payment);
        $this->assertStatus(GetHumanStatus::STATUS_PENDING, $payment);

        #assert redirected
        self::assertInstanceOf(HttpRedirect::class, $reply);
        self::assertStringStartsWith('https://test.saferpay.com/', $iframeUrl = $reply->getUrl());

        # submit form
        $iframeRedirect = $this->getThroughCheckout($iframeUrl, $this->composeFormData(self::CARD_SUCCESS));
        self::assertStringStartsWith(self::HOST, $iframeRedirect);
        self::assertStringContainsString('payum_token='.$token->getHash(), $iframeRedirect);
        self::assertStringContainsString('success=1', $iframeRedirect);

        $notifyUrl = $payment->getDetails()['Notification']['NotifyUrl'];
        $_SERVER['REQUEST_URI'] = $notifyUrl;
        parse_str(parse_url($notifyUrl, PHP_URL_QUERY), $_GET);
        $notifyToken = $this->payum->getHttpRequestVerifier()->verify($_GET);
        $this->payum->getHttpRequestVerifier()->invalidate($notifyToken); //no need to store token

        # NOTIFY AND CAPTURE
        $this->gateway->execute(new Notify($notifyToken), true);

        $this->capture($token, $payment);
        $this->assertStatus(GetHumanStatus::STATUS_CAPTURED, $payment);
    }
}
