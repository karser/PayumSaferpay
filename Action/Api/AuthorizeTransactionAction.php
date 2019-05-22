<?php declare(strict_types=1);

namespace Karser\PayumSaferpay\Action\Api;

use ArrayAccess;
use Karser\PayumSaferpay\Constants;
use Karser\PayumSaferpay\Exception\SaferpayHttpException;
use Karser\PayumSaferpay\Request\Api\AuthorizeTransaction;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\LogicException;
use Payum\Core\Exception\RequestNotSupportedException;

class AuthorizeTransactionAction extends BaseApiAwareAction
{
    public function execute($request): void
    {
        RequestNotSupportedException::assertSupports($this, $request);
        $model = ArrayObject::ensureArrayObject($request->getModel());

        $status = $model['Transaction']['Status'];
        if ($status === Constants::STATUS_AUTHORIZED) {
            return; //already authorized
        }
        if ($status !== Constants::STATUS_PENDING) {
            throw new LogicException(sprintf('Cannot authorize transaction with status: "%s"', $status));
        }
        if (empty($model['Token'])) {
            throw new LogicException('Token is missing');
        }

        try {
            $response = $this->api->authorizeTransaction(
                $model['Token'],
                $model['Condition'] ?? null,
                $model['Alias'] ?? null
            );
            $model['Transaction'] = $response['Transaction'];
            $model['PaymentMeans'] = $response['PaymentMeans'];
            $model['Liability'] = $response['Liability'];
            if (isset($response['RegistrationResult']['Alias']) && $response['RegistrationResult']['Success']) {
                $model['Alias'] = $response['RegistrationResult']['Alias'];
            }
        } catch (SaferpayHttpException $e) {
            $model['Error'] = $e->toArray();
            $model->replace(['Transaction' => array_merge($model['Transaction'], ['Status' => Constants::STATUS_FAILED])]);
        }
    }

    public function supports($request): bool
    {
        return
            $request instanceof AuthorizeTransaction &&
            $request->getModel() instanceof ArrayAccess;
    }
}
