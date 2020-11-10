<?php declare(strict_types=1);

namespace Karser\PayumSaferpay\Action\Api;

use ArrayAccess;
use Karser\PayumSaferpay\Constants;
use Karser\PayumSaferpay\Exception\SaferpayHttpException;
use Karser\PayumSaferpay\Request\Api\CaptureTransaction;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\LogicException;
use Payum\Core\Exception\RequestNotSupportedException;

class CaptureTransactionAction extends BaseApiAwareAction
{
    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);
        $model = ArrayObject::ensureArrayObject($request->getModel());

        $status = $model['Transaction']['Status'];
        if ($status === Constants::STATUS_CAPTURED) {
            return; //already captured
        }
        if ($status !== Constants::STATUS_AUTHORIZED) {
            throw new LogicException(sprintf('Cannot capture transaction with status: "%s"', $status));
        }
        if (empty($model['Transaction']['Id'])) {
            throw new LogicException('Transaction is missing');
        }
        try {
            $response = $this->api->captureTransaction($model['Transaction']['Id']);
            $model->replace(['Transaction' => array_merge($model['Transaction'], [
                'Status' => $response['Status'],
                'Date' => $response['Date'],
            ])]);
        } catch (SaferpayHttpException $e) {
            $error = $e->toArray();
            $errorName = $error['Data']['ErrorName'] ?? null;
            // do not raise any errors if transaction already has been captured
            if ($errorName !== Constants::ERROR_NAME_TRANSACTION_ALREADY_CAPTURED) {
                $model['Error'] = $error;
                $model->replace(['Transaction' => array_merge($model['Transaction'], ['Status' => Constants::STATUS_FAILED])]);
            }
        }
    }

    public function supports($request): bool
    {
        return
            $request instanceof CaptureTransaction &&
            $request->getModel() instanceof ArrayAccess;
    }
}
