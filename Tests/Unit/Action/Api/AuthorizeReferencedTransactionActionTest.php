<?php declare(strict_types=1);

namespace Karser\PayumSaferpay\Tests\Unit\Action\Api;

use Karser\PayumSaferpay\Action\Api\AuthorizeReferencedTransactionAction;
use Karser\PayumSaferpay\Constants;
use Karser\PayumSaferpay\Exception\SaferpayHttpException;
use Karser\PayumSaferpay\Request\Api\AuthorizeReferencedTransaction;

class AuthorizeReferencedTransactionActionTest extends BaseApiActionTest
{
    protected $actionClass = AuthorizeReferencedTransactionAction::class;
    protected $requestClass = AuthorizeReferencedTransaction::class;


    /**
     * @test
     *
     * @expectedException \Payum\Core\Exception\LogicException
     * @expectedExceptionMessage Cannot authorize transaction with status: "FAILED"
     */
    public function throwIfStatusNotNull(): void
    {
        $action = new AuthorizeReferencedTransactionAction();
        $action->execute(new AuthorizeReferencedTransaction(['Transaction' => ['Status' => Constants::STATUS_FAILED]]));
    }

    /**
     * @test
     *
     * @expectedException \Payum\Core\Exception\LogicException
     * @expectedExceptionMessage Payment is missing
     */
    public function throwIfPaymentNotSetInModel(): void
    {
        $action = new AuthorizeReferencedTransactionAction();
        $action->execute(new AuthorizeReferencedTransaction(['Transaction' => ['Status' => null]]));
    }

    /**
     * @test
     *
     * @expectedException \Payum\Core\Exception\LogicException
     * @expectedExceptionMessage TransactionId is missing
     */
    public function throwIfTransactionIdNotSetInModel(): void
    {
        $action = new AuthorizeReferencedTransactionAction();
        $action->execute(new AuthorizeReferencedTransaction([
            'Payment' => ['set'],
            'Transaction' => ['Status' => null
        ]]));
    }

    /**
     * @test
     */
    public function shouldNotCallApi_ifStatusIsAuthorized(): void
    {
        $apiMock = $this->createApiMock();
        $apiMock
            ->expects($this->never())
            ->method('authorizeReferencedTransaction')
        ;
        $action = new AuthorizeReferencedTransactionAction();
        $action->setApi($apiMock);
        $action->setGateway($this->createGatewayMock());
        $model = array_merge($this->model, [
            'Transaction' => ['Status' => Constants::STATUS_AUTHORIZED],
        ]);
        $request = new AuthorizeReferencedTransaction($model);
        $action->execute($request);
    }


    /**
     * @test
     */
    public function shouldCallApi_ifStatusIsNull(): void
    {
        $apiMock = $this->createApiMock();
        $apiMock
            ->expects($this->once())
            ->method('authorizeReferencedTransaction')
            ->willReturnCallback(function (array $payment, ?string $transactionId) {
                $this->assertSame('id', $transactionId);
                return [
                    'Transaction' => 'transaction',
                    'PaymentMeans' => 'paymentMeans',
                ];
            })
        ;
        $action = new AuthorizeReferencedTransactionAction();
        $action->setApi($apiMock);
        $action->setGateway($this->createGatewayMock());
        $model = array_merge($this->model, [
            'Transaction' => ['Status' => null],
            'TransactionReference' => ['TransactionId' => 'id'],
        ]);
        $request = new AuthorizeReferencedTransaction($model);
        $action->execute($request);
        $result = iterator_to_array($request->getModel());
        self::assertSame('transaction', $result['Transaction']);
        self::assertSame('paymentMeans', $result['PaymentMeans']);
    }

    /**
     * @test
     */
    public function shouldSetError_ifExceptionThrown(): void
    {
        $apiMock = $this->createApiMock();
        $apiMock
            ->expects($this->once())
            ->method('authorizeReferencedTransaction')
            ->willThrowException(new SaferpayHttpException('ERROR'))
        ;
        $action = new AuthorizeReferencedTransactionAction();
        $action->setApi($apiMock);
        $action->setGateway($this->createGatewayMock());
        $model = array_merge($this->model, [
            'Transaction' => ['Status' => null],
            'TransactionReference' => ['TransactionId' => 'id'],
        ]);
        $request = new AuthorizeReferencedTransaction($model);
        $action->execute($request);
        $result = iterator_to_array($request->getModel());
        self::assertSame('ERROR', $result['Error']['Message']);
    }
}
