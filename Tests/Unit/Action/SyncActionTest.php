<?php declare(strict_types=1);

namespace Karser\PayumSaferpay\Tests\Unit\Action;

use Karser\PayumSaferpay\Action\SyncAction;
use Payum\Core\Action\ActionInterface;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\Request\Sync;
use Payum\Core\Tests\GenericActionTest;

class SyncActionTest extends GenericActionTest
{
    protected $requestClass = Sync::class;
    protected $actionClass = SyncAction::class;

    /**
     * @test
     */
    public function shouldImplementActionInterface(): void
    {
        $rc = new \ReflectionClass(SyncAction::class);
        $this->assertTrue($rc->implementsInterface(ActionInterface::class));
    }

    /**
     * @test
     */
    public function shouldImplementGatewayAwareInterface(): void
    {
        $rc = new \ReflectionClass(SyncAction::class);
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
