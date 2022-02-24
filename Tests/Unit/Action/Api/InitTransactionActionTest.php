<?php declare(strict_types=1);

namespace Karser\PayumSaferpay\Tests\Unit\Action\Api;

use Karser\PayumSaferpay\Action\Api\InitTransactionAction;
use Karser\PayumSaferpay\Request\Api\InitTransaction;

class InitTransactionActionTest extends BaseApiActionTest
{
    protected $actionClass = InitTransactionAction::class;
    protected $requestClass = InitTransaction::class;

    /**
     * @test
     */
    public function throwIfPaymentNotSetInModel(): void
    {
        $this->expectExceptionMessage("Payment is missing");
        $this->expectException(\Payum\Core\Exception\LogicException::class);
        $action = new InitTransactionAction();
        $action->execute(new InitTransaction([]));
    }

    /**
     * @test
     */
    public function throwIfReturnsUrlNotSetInModel(): void
    {
        $this->expectExceptionMessage("ReturnUrls is missing");
        $this->expectException(\Payum\Core\Exception\LogicException::class);
        $action = new InitTransactionAction();
        $action->execute(new InitTransaction(['Payment' => ['Amount' => 123]]));
    }

    /**
     * @test
     */
    public function shouldCallApiInitTransactionMethodWithExpectedRequiredArguments(): void
    {
        $apiMock = $this->createApiMock();
        $apiMock
            ->expects($this->once())
            ->method('initTransaction')
            ->willReturnCallback(function (array $model) {
                $this->assertSame($this->model['Payment'], $model['Payment']);
                $this->assertSame($this->model['ReturnUrls'], $model['ReturnUrls']);
                $this->assertSame(123, $model['Payment']['Amount']['Value']);
                return ['Token' => 'token', 'Expiration' => 'expiration', 'RedirectRequired' => false];
            })
        ;
        $action = new InitTransactionAction();
        $action->setApi($apiMock);
        $action->setGateway($this->createGatewayMock());
        $request = new InitTransaction($this->model);
        $action->execute($request);
    }

    /**
     * @test
     */
    public function shouldNotCallApiInitTransactionMethod_ifTokenSetInModel(): void
    {
        $apiMock = $this->createApiMock();
        $apiMock
            ->expects($this->never())
            ->method('initTransaction')
        ;
        $action = new InitTransactionAction();
        $action->setApi($apiMock);
        $action->setGateway($this->createGatewayMock());
        $model = array_merge($this->model, ['Token' => 'token']);
        $request = new InitTransaction($model);
        $action->execute($request);
    }

    /**
     * @test
     */
    public function shouldThrowRedirect_ifSet(): void
    {
        $this->expectException(\Payum\Core\Reply\HttpRedirect::class);
        $apiMock = $this->createApiMock();
        $action = new InitTransactionAction();
        $action->setApi($apiMock);
        $action->setGateway($this->createGatewayMock());
        $model = array_merge($this->model, [
            'Token' => 'token',
            'RedirectRequired' => true,
            'Redirect' => ['RedirectUrl' => 'redirectUrl']
        ]);
        $request = new InitTransaction($model);
        $action->execute($request);
    }

}
