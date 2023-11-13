<?php declare(strict_types=1);

namespace Karser\PayumSaferpay\Action;

use Karser\PayumSaferpay\Api;
use Karser\PayumSaferpay\Request\Api\AuthorizeReferencedTransaction;
use Karser\PayumSaferpay\Request\Api\CaptureTransaction;
use Karser\PayumSaferpay\Request\CaptureReferenced;
use Payum\Core\Action\ActionInterface;
use Payum\Core\ApiAwareInterface;
use Payum\Core\ApiAwareTrait;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Model\PaymentInterface;
use Payum\Core\Request\Convert;
use Payum\Core\Request\GetHumanStatus;

class CaptureReferencedAction implements ActionInterface, ApiAwareInterface, GatewayAwareInterface
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
     * @param mixed $request
     *
     * @throws \Payum\Core\Exception\RequestNotSupportedException if the action does not support the request.
     */
    public function execute($request): void
    {
        RequestNotSupportedException::assertSupports($this, $request);

        /** @var PaymentInterface $payment */
        $payment = $request->getModel();

        $this->gateway->execute($status = new GetHumanStatus($payment));
        if ($status->isNew()) {
            $this->gateway->execute($convert = new Convert($payment, 'array'));

            $payment->setDetails($convert->getResult());
        }

        $details = ArrayObject::ensureArrayObject($payment->getDetails());

        try {
            $this->gateway->execute(new AuthorizeReferencedTransaction($details));
            if ($this->api->doInstantCapturing()) {
                $this->gateway->execute(new CaptureTransaction($details));
            }

        } finally {
            $payment->setDetails($details);
        }
    }

    /**
     * @param mixed $request
     */
    public function supports($request): bool
    {
        return
            $request instanceof CaptureReferenced &&
            $request->getModel() instanceof PaymentInterface
            ;
    }
}
