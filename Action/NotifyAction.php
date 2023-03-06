<?php declare(strict_types=1);

namespace Karser\PayumSaferpay\Action;

use Karser\PayumSaferpay\Api;
use Karser\PayumSaferpay\Request\Api\AssertPaymentPage;
use Karser\PayumSaferpay\Request\Api\CaptureTransaction;
use Payum\Core\Action\ActionInterface;
use Payum\Core\ApiAwareInterface;
use Payum\Core\ApiAwareTrait;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Reply\HttpResponse;
use Payum\Core\Request\GetHumanStatus;
use Payum\Core\Request\Notify;

class NotifyAction implements ActionInterface, ApiAwareInterface, GatewayAwareInterface
{
    use ApiAwareTrait;
    use GatewayAwareTrait;

    /**
     * @var Api
     */
    protected $api;

    public function __construct()
    {
        $this->apiClass = Api::class;
    }

    /**
     * @param Notify $request
     */
    public function execute($request): void
    {
        RequestNotSupportedException::assertSupports($this, $request);

        $model = ArrayObject::ensureArrayObject($request->getModel());

        $this->gateway->execute(new AssertPaymentPage($model));
        $this->gateway->execute($status = new GetHumanStatus($model));

        if ($this->api->doInstantCapturing() && ($status->isPending() || $status->isAuthorized())) {
            $this->gateway->execute(new CaptureTransaction($model));
        }

        throw new HttpResponse('', 204);
    }

    public function supports($request): bool
    {
        return
            $request instanceof Notify &&
            $request->getModel() instanceof \ArrayAccess
        ;
    }
}
