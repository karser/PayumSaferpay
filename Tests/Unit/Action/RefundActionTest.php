<?php declare(strict_types=1);

namespace Karser\PayumSaferpay\Tests\Unit\Action;

use Karser\PayumSaferpay\Action\RefundAction;
use Payum\Core\Action\ActionInterface;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\Request\Refund;
use Payum\Core\Tests\GenericActionTest;

class RefundActionTest extends GenericActionTest
{
    protected $requestClass = Refund::class;
    protected $actionClass = RefundAction::class;

    /**
     * @test
     */
    public function shouldImplementActionInterface(): void
    {
        $rc = new \ReflectionClass(RefundAction::class);
        $this->assertTrue($rc->implementsInterface(ActionInterface::class));
    }

    /**
     * @test
     */
    public function shouldImplementGatewayAwareInterface(): void
    {
        $rc = new \ReflectionClass(RefundAction::class);
        $this->assertTrue($rc->implementsInterface(GatewayAwareInterface::class));
    }

    /**
     * @test
     */
    public function couldBeConstructedWithoutAnyArguments(): void
    {
        self::assertNotNull(new $this->actionClass());
    }
}
