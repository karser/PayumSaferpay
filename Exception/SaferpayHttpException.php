<?php declare(strict_types=1);

namespace Karser\PayumSaferpay\Exception;

use Payum\Core\Exception\Http\HttpException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

final class SaferpayHttpException extends HttpException
{
    // https://saferpay.github.io/jsonapi/index.html#errorhandling
    public const BEHAVIOR_ABORT = 'ABORT';
    public const BEHAVIOR_RETRY = 'RETRY';
    public const BEHAVIOR_RETRY_LATER = 'RETRY_LATER';
    public const BEHAVIOR_OTHER_MEANS = 'OTHER_MEANS';

    protected ?array $data = null;
    protected ?string $info = null;

    public static function factory(RequestInterface $request, ResponseInterface $response): SaferpayHttpException
    {
        /** @var SaferpayHttpException $e */
        $e = parent::factory($request, $response);
        $contents = $response->getBody()->getContents();
        $data = @json_decode($contents, true);
        if ($data !== null && json_last_error() === JSON_ERROR_NONE) {
            $e->data = $data;
            $e->info = $e->message;
            $e->message = $data['ErrorMessage'];
        }
        return $e;
    }

    public function getData(): ?array
    {
        return $this->data;
    }

    public function getInfo(): ?string
    {
        return $this->info;
    }

    public function toArray(): array
    {
        return [
            'Message' => $this->message,
            'Data' => $this->data,
            'Info' => $this->info
        ];
    }
}
