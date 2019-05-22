<?php declare(strict_types=1);

namespace Karser\PayumSaferpay\Action;

use Karser\PayumSaferpay\Constants;
use Payum\Core\Action\ActionInterface;
use Payum\Core\Request\GetStatusInterface;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;

class StatusAction implements ActionInterface
{
    /**
     * @param GetStatusInterface $request
     */
    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);

        $model = ArrayObject::ensureArrayObject($request->getModel());

        $status = $model['Transaction']['Status'] ?? null;
        if (null === $status) {
            $request->markNew();
            return;
        }
        if (Constants::STATUS_PENDING === $status) {
            $request->markPending();
            return;
        }
        if (Constants::STATUS_AUTHORIZED === $status) {
            $request->markAuthorized();
            return;
        }
        if (Constants::STATUS_CAPTURED === $status) {
            $request->markCaptured();
            return;
        }
        if (Constants::STATUS_FAILED === $status) {
            $request->markFailed();
            return;
        }
        if (Constants::STATUS_CANCELED === $status || Constants::STATUS_ABORTED === $status) {
            $request->markCanceled();
            return;
        }
        $request->markUnknown();
    }

    public function supports($request): bool
    {
        return
            $request instanceof GetStatusInterface &&
            $request->getModel() instanceof \ArrayAccess
        ;
    }
}
