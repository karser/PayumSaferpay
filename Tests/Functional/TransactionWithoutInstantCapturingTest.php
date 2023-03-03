<?php declare(strict_types=1);

namespace Karser\PayumSaferpay\Tests\Functional;

use Payum\Core\Request\GetHumanStatus;

class TransactionWithoutInstantCapturingTest extends AbstractSaferpayTest
{
    protected function getGatewayConfig(): array
    {
        return array_merge(parent::getGatewayConfig(), [
            'instantCapturing' => false
        ]);
    }

    public function paymentStatusWithoutInstantCapturingProvider(): array
    {
        return [
            'success' => [
                $this->composeFormData(self::CARD_SUCCESS),
                $behavior = 'submit',
                GetHumanStatus::STATUS_AUTHORIZED,
            ],
            'success 3d' => [
                $this->composeFormData(self::CARD_SUCCESS_LS_AUTH_3D),
                $behavior = 'submit',
                GetHumanStatus::STATUS_AUTHORIZED,
            ],
        ];
    }

    /**
     * @test
     * @dataProvider paymentStatusWithoutInstantCapturingProvider
     */
    public function paymentWithoutInstantCapturingStatus($formData, $formBehavior, string $expectedStatus): void
    {
        $this->createPaymentWithStatus($formData, $formBehavior, $expectedStatus);
    }
}
