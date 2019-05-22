<?php declare(strict_types=1);

namespace Karser\PayumSaferpay\Action\Api;

use ArrayAccess;
use Karser\PayumSaferpay\Constants;
use Karser\PayumSaferpay\Exception\SaferpayHttpException;
use Karser\PayumSaferpay\Request\Api\AssertPaymentPage;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\LogicException;
use Payum\Core\Exception\RequestNotSupportedException;

class AssertPaymentPageAction extends BaseApiAwareAction
{
    /**
     * @param AssertPaymentPage $request
     */
    public function execute($request): void
    {
        RequestNotSupportedException::assertSupports($this, $request);

        $details = ArrayObject::ensureArrayObject($request->getModel());

        if (empty($details['Token'])) {
            throw new LogicException('Token is missing');
        }
        try {
            $response = $this->api->assertPaymentPage($details['Token']);
            $details['Transaction'] = $response['Transaction'];
            $details['PaymentMeans'] = $response['PaymentMeans'];
            $details['Liability'] = $response['Liability'];
        } catch (SaferpayHttpException $e) {
            $details['Error'] = $e->toArray();
            $details->replace(['Transaction' => array_merge($details['Transaction'], ['Status' => Constants::STATUS_FAILED])]);
        }
    }

    public function supports($request): bool
    {
        return
            $request instanceof AssertPaymentPage &&
            $request->getModel() instanceof ArrayAccess
        ;
    }
}
