<?php declare(strict_types=1);

namespace Karser\PayumSaferpay\Action\Api;

use ArrayAccess;
use Karser\PayumSaferpay\Constants;
use Karser\PayumSaferpay\Exception\SaferpayHttpException;
use Karser\PayumSaferpay\Request\Api\RefundTransaction;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\LogicException;
use Payum\Core\Exception\RequestNotSupportedException;

class RefundTransactionAction extends BaseApiAwareAction
{
    /**
     * @param RefundTransaction $request
     */
    public function execute($request): void
    {
        RequestNotSupportedException::assertSupports($this, $request);
        $model = ArrayObject::ensureArrayObject($request->getModel());

        $status = $model['Transaction']['Status'];
        $type = $model['Transaction']['Type'];
        if ($status === Constants::STATUS_AUTHORIZED && $type === Constants::TYPE_REFUND) {
            return; //already refund
        }
        $canRefund = in_array($type, [Constants::TYPE_PAYMENT, Constants::TYPE_PURCHASE], true)
            && $status === Constants::STATUS_CAPTURED;
        if (!$canRefund) {
            throw new RequestNotSupportedException(sprintf(
                'Cannot refund transaction of type: "%s" with status: "%s"',
                $type,
                $status
            ));
        }
        if (empty($model['Payment'])) {
            throw new LogicException('Payment is missing');
        }
        if (empty($model['Transaction']['Id'])) {
            throw new LogicException('Transaction is missing');
        }
        try {
            $response = $this->api->refundTransaction($model['Payment'], $model['Transaction']['Id']);
            $model['Transaction'] = $response['Transaction'];
        } catch (SaferpayHttpException $e) {
            $model['Error'] = $e->toArray();
            $model->replace(['Transaction' => array_merge($model['Transaction'], ['Status' => Constants::STATUS_FAILED])]);
        }
    }

    public function supports($request): bool
    {
        return
            $request instanceof RefundTransaction &&
            $request->getModel() instanceof ArrayAccess;
    }
}
