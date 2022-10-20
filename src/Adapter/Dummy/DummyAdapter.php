<?php

namespace Nahid\RequestProxy\Adapter\Dummy;

use Nahid\RequestProxy\Adapter\AdapterInterface;
use Psr\Http\Message\RequestInterface;
use Laminas\Diactoros\Response;

class DummyAdapter implements AdapterInterface
{
    /**
     * @inheritdoc
     */
    public function send(RequestInterface $request)
    {
        return new Response($request->getBody(), 200);
    }
}
