<?php declare(strict_types=1);

namespace Karser\PayumSaferpay\Tests\Functional;

use Karser\PayumSaferpay\Constants;
use Karser\PayumSaferpay\Request\CaptureReferenced;
use Payum\Core\Request\GetHumanStatus;
use Payum\Core\Request\Refund;

class TransactionAdvancedTest extends AbstractSaferpayTest
{
    /**
     * @test
     */
    public function refund(): void
    {
        $payment = $this->createCapturedPayment([]);
        $this->assertStatus(GetHumanStatus::STATUS_CAPTURED, $payment);

        $this->gateway->execute(new Refund($payment));
        self::assertSame(Constants::TYPE_REFUND, $payment->getDetails()['Transaction']['Type']);
        $this->assertStatus(GetHumanStatus::STATUS_CAPTURED, $payment);

    }

    public static function paymentRecurringProvider(): array
    {
        return [
            [
                ['Payment' => ['Recurring' => ['Initial' => true]]]
            ],
            [
                ['Payment' => ['Installment' => ['Initial' => true]]]
            ],
        ];
    }

    /**
     * @test
     * @dataProvider paymentRecurringProvider
     */
    public function recurringPaymentUsingReferencedTransaction(array $options): void
    {
        $payment = $this->createCapturedPayment($options);
        $this->assertStatus(GetHumanStatus::STATUS_CAPTURED, $payment);

        $refTransactionId = $payment->getDetails()['Transaction']['Id'];

        # do referenced transaction
        $payment = $this->createPayment([
            'TransactionReference' => [
                'TransactionId' =>  $refTransactionId,
            ]
        ]);

        $this->gateway->execute(new CaptureReferenced($payment));
        $this->assertStatus(GetHumanStatus::STATUS_CAPTURED, $payment);
    }

    /**
     * @test
     */
    public function recurringPaymentUsingCardAlias(): void
    {
        $cardAlias = $this->createInsertedCardAlias([]);
        self::assertNotNull($aliasId = $cardAlias->getDetails()['Alias']['Id']);

        $payment = $this->createCapturedPayment([
            'PaymentMeans' => [
                'Alias' => [
                    'Id' => $aliasId,
                ],
            ],
        ]);
        $this->assertStatus(GetHumanStatus::STATUS_CAPTURED, $payment);
    }

    /**
     * @test
     */
    public function paymentShouldFailWithoutLS(): void
    {
        $payment = $this->createCapturedPayment([
            'Condition' => Constants::LS_WITH_LIABILITY_SHIFT,
        ]);
        $this->assertStatus(GetHumanStatus::STATUS_FAILED, $payment);

        $details = $payment->getDetails();
        self::assertStringStartsWith('Liability shift condition not satisfied', $details['Error']['Message']);
    }

    /**
     * @test
     */
    public function paymentShouldRegisterAlias(): void
    {
        $payment = $this->createCapturedPayment([
            'Alias' => [
                'IdGenerator' => Constants::ALIAS_ID_GENERATOR_RANDOM,
                'Lifetime' => self::ALIAS_LIFETIME,
            ],
        ]);
        $this->assertStatus(GetHumanStatus::STATUS_CAPTURED, $payment);

        $details = $payment->getDetails();
        self::assertSame(self::ALIAS_LIFETIME, $details['Alias']['Lifetime']);
        self::assertNotNull($details['Alias']['Id']);
    }
}
