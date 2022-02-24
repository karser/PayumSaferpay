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
     *
     * @expectedException \Payum\Core\Exception\LogicException
     * @expectedExceptionMessage Payment is missing
     */
    public function throwIfPaymentNotSetInModel(): void
    {
        $action = new InitTransactionAction();
        $action->execute(new InitTransaction([]));
    }

    /**
     * @test
     *
     * @expectedException \Payum\Core\Exception\LogicException
     * @expectedExceptionMessage ReturnUrls is missing
     */
    public function throwIfReturnsUrlNotSetInModel(): void
    {
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
     * @expectedException \Payum\Core\Reply\HttpRedirect
     */
    public function shouldThrowRedirect_ifSet(): void
    {
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
