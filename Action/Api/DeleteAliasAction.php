<?php declare(strict_types=1);

namespace Karser\PayumSaferpay\Action\Api;

use ArrayAccess;
use Karser\PayumSaferpay\Exception\SaferpayHttpException;
use Karser\PayumSaferpay\Request\Api\DeleteAlias;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Exception\RuntimeException;

class DeleteAliasAction extends BaseApiAwareAction
{
    public function execute($request): void
    {
        RequestNotSupportedException::assertSupports($this, $request);
        $model = ArrayObject::ensureArrayObject($request->getModel());

        if (empty($model['Alias']['Id'])) {
            throw new RuntimeException('AliasId is missing');
        }
        try {
            $this->api->deleteAlias($model['Alias']['Id']);
            $model['Alias'] = array_merge($model['Alias'], ['Id' => null]);
        } catch (SaferpayHttpException $e) {
            $model['Error'] = $e->toArray();
            throw $e;
        }
    }

    public function supports($request): bool
    {
        return
            $request instanceof DeleteAlias &&
            $request->getModel() instanceof ArrayAccess;
    }
}
