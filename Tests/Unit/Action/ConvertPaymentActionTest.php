<?php declare(strict_types=1);

namespace Karser\PayumSaferpay\Tests\Unit\Action;

use Karser\PayumSaferpay\Action\ConvertPaymentAction;
use Payum\Core\Action\ActionInterface;
use Payum\Core\Model\Payment;
use Payum\Core\Model\PaymentInterface;
use Payum\Core\Request\Convert;
use Payum\Core\Request\Generic;
use Payum\Core\Tests\GenericActionTest;
use DMS\PHPUnitExtensions\ArraySubset\ArraySubsetAsserts;

class ConvertPaymentActionTest extends GenericActionTest
{
    use ArraySubsetAsserts;
    protected $requestClass = Convert::class;
    protected $actionClass = ConvertPaymentAction::class;


    /**
     * @test
     */
    public function shouldImplementActionInterface(): void
    {
        $rc = new \ReflectionClass(ConvertPaymentAction::class);
        $this->assertTrue($rc->implementsInterface(ActionInterface::class));
    }

    /**
     * @test
     */
    public function couldBeConstructedWithoutAnyArguments(): void
    {
        self::assertNotNull(new $this->actionClass());
    }

    public function provideSupportedRequests(): \Iterator
    {
        yield [new $this->requestClass(new Payment(), 'array')];
        yield [new $this->requestClass($this->createMock(PaymentInterface::class), 'array')];
        yield [new $this->requestClass(new Payment(), 'array', $this->createMock('Payum\Core\Security\TokenInterface'))];

    }

    public function provideNotSupportedRequests(): \Iterator
    {
        yield ['foo'];
        yield [['foo']];
        yield [new \stdClass()];
        yield [$this->getMockForAbstractClass(Generic::class, [[]])];
        yield [new $this->requestClass(new \stdClass(), 'array')];
        yield [new $this->requestClass(new Payment(), 'foobar')];
        yield [new $this->requestClass($this->createMock(PaymentInterface::class), 'foobar')];

    }

    /**
     * @test
     */
    public function shouldCorrectlyConvertOrderToDetailsAndSetItBack(): void
    {
        $payment = new Payment();
        $payment->setNumber('theNumber');
        $payment->setCurrencyCode('USD');
        $payment->setTotalAmount(123);
        $payment->setDescription('the description');
        $payment->setClientId('theClientId');
        $payment->setClientEmail('theClientEmail');
        $action = new ConvertPaymentAction();
        $action->execute($convert = new Convert($payment, 'array'));
        $details = $convert->getResult();
        $this->assertNotEmpty($details);
        self::assertSame([
            'Payment' => [
                'Amount' => [
                    'Value' => 123,
                    'CurrencyCode' => 'USD',
                ],
                'OrderId' => 'theNumber',
                'Description' => 'the description',
            ],
            'Transaction' => [
                'Type' => 'PAYMENT',
                'Status' => NULL,
                'Amount' => [
                    'Value' => 123,
                    'CurrencyCode' => 'USD',
                ],
                'OrderId' => 'theNumber',
            ],
        ], $details);
    }

    /**
     * @test
     */
    public function shouldNotOverwriteAlreadySetExtraDetails(): void
    {
        $payment = new Payment();
        $payment->setNumber('theNumber');
        $payment->setCurrencyCode('USD');
        $payment->setTotalAmount(123);
        $payment->setDescription('the description');
        $payment->setClientId('theClientId');
        $payment->setClientEmail('theClientEmail');
        $payment->setDetails(array(
            'foo' => 'fooVal',
        ));
        $action = new ConvertPaymentAction();
        $action->execute($convert = new Convert($payment, 'array'));
        $details = $convert->getResult();
        $this->assertNotEmpty($details);
        self::assertArraySubset([
            'foo' => 'fooVal'
        ], $details);
    }
}
