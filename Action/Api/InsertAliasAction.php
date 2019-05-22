<?php declare(strict_types=1);

namespace Karser\PayumSaferpay\Action\Api;

use ArrayAccess;
use Karser\PayumSaferpay\Constants;
use Karser\PayumSaferpay\Exception\SaferpayHttpException;
use Karser\PayumSaferpay\Request\Api\InsertAlias;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\LogicException;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Reply\HttpRedirect;

class InsertAliasAction extends BaseApiAwareAction
{
    public function execute($request): void
    {
        RequestNotSupportedException::assertSupports($this, $request);
        $model = ArrayObject::ensureArrayObject($request->getModel());

        if (empty($model['ReturnUrls'])) {
            throw new LogicException('ReturnUrls is missing');
        }

        if (empty($model['Token'])) {
            try {
                $response = $this->api->insertAlias(
                    $model['ReturnUrls'],
                    array_merge(
                        ['IdGenerator' => Constants::ALIAS_ID_GENERATOR_RANDOM], $model['Alias'] ?? []
                    ),
                    $model['Type'] ?? Constants::ALIAS_TYPE_CARD
                );
                $model['Token'] = $response['Token'];
                $model['RedirectUrl'] = $response['RedirectUrl'];
            } catch (SaferpayHttpException $e) {
                $model['Error'] = $e->toArray();
                throw $e;
            }
        }
        throw new HttpRedirect($model['RedirectUrl']);
    }

    public function supports($request): bool
    {
        return
            $request instanceof InsertAlias &&
            $request->getModel() instanceof ArrayAccess;
    }
}
