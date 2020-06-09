<?php declare(strict_types=1);

namespace Karser\PayumSaferpay\Action\Api;

use ArrayAccess;
use Karser\PayumSaferpay\Constants;
use Karser\PayumSaferpay\Exception\SaferpayHttpException;
use Karser\PayumSaferpay\Request\Api\InitPaymentPage;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\LogicException;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Reply\HttpRedirect;

class InitPaymentPageAction extends BaseApiAwareAction
{
    /**
     * @param InitPaymentPage $request
     */
    public function execute($request): void
    {
        RequestNotSupportedException::assertSupports($this, $request);
        $model = ArrayObject::ensureArrayObject($request->getModel());

        if (empty($model['Payment'])) {
            throw new LogicException('Payment is missing');
        }
        if (empty($model['ReturnUrls'])) {
            throw new LogicException('ReturnUrls is missing');
        }

        if (empty($model['Token'])) {
            try {
                $response = $this->api->initPaymentPage((array) $model);
                $model->replace(['Transaction' => array_merge($model['Transaction'], ['Status' => Constants::STATUS_PENDING])]);
                $model['Token'] = $response['Token'];
                $model['Expiration'] = $response['Expiration'];
                $model['RedirectUrl'] = $response['RedirectUrl'];
            } catch (SaferpayHttpException $e) {
                $model['Error'] = $e->toArray();
                $model->replace(['Transaction' => array_merge($model['Transaction'], ['Status' => Constants::STATUS_FAILED])]);
                return;
            }
        }
        throw new HttpRedirect($model['RedirectUrl']);
    }

    public function supports($request): bool
    {
        return
            $request instanceof InitPaymentPage &&
            $request->getModel() instanceof ArrayAccess
            ;
    }
}
