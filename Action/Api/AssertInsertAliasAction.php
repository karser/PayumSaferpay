<?php declare(strict_types=1);

namespace Karser\PayumSaferpay\Action\Api;

use ArrayAccess;
use Karser\PayumSaferpay\Exception\SaferpayHttpException;
use Karser\PayumSaferpay\Request\Api\AssertInsertAlias;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Exception\RuntimeException;

class AssertInsertAliasAction extends BaseApiAwareAction
{
    public function execute($request): void
    {
        RequestNotSupportedException::assertSupports($this, $request);
        $model = ArrayObject::ensureArrayObject($request->getModel());

        if (empty($model['Token'])) {
            throw new RuntimeException('Token is missing');
        }
        try {
            $response = $this->api->assertInsertAlias($model['Token']);
            $model['Alias'] = $response['Alias'];
            $model['PaymentMeans'] = $response['PaymentMeans'];
        } catch (SaferpayHttpException $e) {
            $model['Error'] = $e->toArray();
            throw $e;
        }
    }

    public function supports($request): bool
    {
        return
            $request instanceof AssertInsertAlias &&
            $request->getModel() instanceof ArrayAccess
        ;
    }
}
