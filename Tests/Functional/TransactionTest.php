<?php declare(strict_types=1);

namespace Karser\PayumSaferpay\Tests\Functional;

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
        $this->createPaymentWithStatus($formData, $formBehavior, $expectedStatus);
    }
}
