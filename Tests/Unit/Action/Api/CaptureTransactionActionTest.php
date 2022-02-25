<?php declare(strict_types=1);

namespace Karser\PayumSaferpay\Tests\Unit\Action\Api;

use Karser\PayumSaferpay\Action\Api\CaptureTransactionAction;
use Karser\PayumSaferpay\Constants;
use Karser\PayumSaferpay\Exception\SaferpayHttpException;
use Karser\PayumSaferpay\Request\Api\CaptureTransaction;

class CaptureTransactionActionTest extends BaseApiActionTest
{
    protected $actionClass = CaptureTransactionAction::class;
    protected $requestClass = CaptureTransaction::class;

    /**
     * @test
     */
    public function throwIfStatusIncorrect(): void
    {
        $this->expectExceptionMessage("Cannot capture transaction with status: \"FAILED\"");
        $this->expectException(\Payum\Core\Exception\LogicException::class);
        $action = new CaptureTransactionAction();
        $action->execute(new CaptureTransaction(['Transaction' => ['Status' => Constants::STATUS_FAILED]]));
    }

    /**
     * @test
     */
    public function throwIfTransactionIdNotSetInModel(): void
    {
        $this->expectExceptionMessage("Transaction is missing");
        $this->expectException(\Payum\Core\Exception\LogicException::class);
        $action = new CaptureTransactionAction();
        $action->execute(new CaptureTransaction(['Transaction' => ['Status' => Constants::STATUS_AUTHORIZED]]));
    }

    /**
     * @test
     */
    public function shouldNotCallApi_ifStatusIsCaptured(): void
    {
        $apiMock = $this->createApiMock();
        $apiMock
            ->expects($this->never())
            ->method('captureTransaction')
        ;
        $action = new CaptureTransactionAction();
        $action->setApi($apiMock);
        $action->setGateway($this->createGatewayMock());
        $model = array_merge($this->model, [
            'Transaction' => ['Status' => Constants::STATUS_CAPTURED],
        ]);
        $request = new CaptureTransaction($model);
        $action->execute($request);
    }

    /**
     * @test
     */
    public function shouldCallApi_ifStatusAuthorized(): void
    {
        $apiMock = $this->createApiMock();
        $apiMock
            ->expects($this->once())
            ->method('captureTransaction')
            ->willReturnCallback(function (string $id) {
                $this->assertSame('transaction-id', $id);
                return [
                    'Status' => Constants::STATUS_CAPTURED,
                    'Date' => 'date',
                ];
            })
        ;
        $action = new CaptureTransactionAction();
        $action->setApi($apiMock);
        $action->setGateway($this->createGatewayMock());
        $model = array_merge($this->model, [
            'Transaction' => [
                'Id' => 'transaction-id',
                'Status' => Constants::STATUS_AUTHORIZED,
            ],
        ]);
        $request = new CaptureTransaction($model);
        $action->execute($request);
        $result = iterator_to_array($request->getModel());
        self::assertSame(Constants::STATUS_CAPTURED, $result['Transaction']['Status']);
    }

    /**
     * @test
     */
    public function shouldSetError_ifExceptionThrown(): void
    {
        $apiMock = $this->createApiMock();
        $apiMock
            ->expects($this->once())
            ->method('captureTransaction')
            ->willThrowException(new SaferpayHttpException('ERROR'))
        ;
        $action = new CaptureTransactionAction();
        $action->setApi($apiMock);
        $action->setGateway($this->createGatewayMock());
        $model = array_merge($this->model, [
            'Transaction' => [
                'Id' => 'transaction-id',
                'Status' => Constants::STATUS_AUTHORIZED,
            ],
        ]);
        $request = new CaptureTransaction($model);
        $action->execute($request);
        $result = iterator_to_array($request->getModel());
        self::assertSame('ERROR', $result['Error']['Message']);
    }
}
