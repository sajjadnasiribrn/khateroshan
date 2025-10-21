<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use UnitEnum;

abstract class BaseJsonResource extends JsonResource
{
    protected int $responseStatus;

    public function __construct($resource, int $status = HttpResponse::HTTP_OK)
    {
        parent::__construct($resource);

        $this->responseStatus = $status;
    }
    public function toResponse($request)
    {
        $response = parent::toResponse($request);
        $response->setStatusCode($this->responseStatus);

        return $response;
    }

    protected function enumValue(mixed $value): mixed
    {
        if ($value instanceof UnitEnum) {
            return $value->value;
        }

        return $value;
    }
}
