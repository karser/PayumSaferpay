<?php declare(strict_types=1);

namespace Karser\PayumSaferpay\Action\Api;

use ArrayAccess;
use Karser\PayumSaferpay\Constants;
use Karser\PayumSaferpay\Exception\SaferpayHttpException;
use Karser\PayumSaferpay\Request\Api\AuthorizeReferencedTransaction;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\LogicException;
use Payum\Core\Exception\RequestNotSupportedException;

class AuthorizeReferencedTransactionAction extends BaseApiAwareAction
{
    public function execute($request): void
    {
        RequestNotSupportedException::assertSupports($this, $request);
        $model = ArrayObject::ensureArrayObject($request->getModel());

        $status = $model['Transaction']['Status'];
        if ($status === Constants::STATUS_AUTHORIZED) {
            return; //already authorized
        }
        if ($status !== null) {
            throw new LogicException(sprintf('Cannot authorize transaction with status: "%s"', $status));
        }
        if (empty($model['Payment'])) {
            throw new LogicException('Payment is missing');
        }
        if (empty($model['TransactionReference']['TransactionId'])) {
            throw new LogicException('TransactionId is missing');
        }
        try {
            $response = $this->api->authorizeReferencedTransaction($model['Payment'], $model['TransactionReference']['TransactionId']);
            $model['Transaction'] = $response['Transaction'];
            $model['PaymentMeans'] = $response['PaymentMeans'];
        } catch (SaferpayHttpException $e) {
            $model['Error'] = $e->toArray();
            $model->replace(['Transaction' => array_merge($model['Transaction'], ['Status' => Constants::STATUS_FAILED])]);
        }
    }

    public function supports($request): bool
    {
        return
            $request instanceof AuthorizeReferencedTransaction &&
            $request->getModel() instanceof ArrayAccess;
    }
}
