<?php

use Kapi\Http\Response;

describe('Response', function () {
    describe('withStatus', function () {
        it('set only code', function () {
            $response = new Response();
            $response = $response->withStatus(200);

            expect($response->getStatusCode())->toBe(200);
            expect($response->getReasonPhrase())->toBe('OK');
        });

        it('set custom reason', function () {
            $response = new Response();
            $response = $response->withStatus(404, 'This page doesn\'t exist');

            expect($response->getStatusCode())->toBe(404);
            expect($response->getReasonPhrase())->toBe('This page doesn\'t exist');
        });
    });
});
