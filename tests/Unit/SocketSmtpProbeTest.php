<?php

use App\Data\ProbeResult;
use App\Services\SocketSmtpProbe;

/** Trouve un port libre en ouvrant puis refermant une socket d'écoute. */
function freeLocalPort(): int
{
    $server = stream_socket_server('tcp://127.0.0.1:0', $errno, $errstr);
    $name = stream_socket_get_name($server, false);
    fclose($server);

    return (int) explode(':', $name)[1];
}

test('a closed port reports a failure instead of throwing', function () {
    $result = (new SocketSmtpProbe)->probe('127.0.0.1', freeLocalPort(), timeoutSeconds: 1);

    expect($result)->toBeInstanceOf(ProbeResult::class)
        ->and($result->ok)->toBeFalse()
        ->and($result->message)->not->toBe('');
});

test('a socket that never sends a banner reports a failure', function () {
    // Serveur qui accepte la connexion mais n'envoie jamais de bannière 220 :
    // la sonde doit abandonner proprement au bout du délai imparti.
    $server = stream_socket_server('tcp://127.0.0.1:0', $errno, $errstr);
    $port = (int) explode(':', stream_socket_get_name($server, false))[1];

    $result = (new SocketSmtpProbe)->probe('127.0.0.1', $port, timeoutSeconds: 1);

    fclose($server);

    expect($result->ok)->toBeFalse();
});

test('ProbeResult carries its outcome and message', function () {
    expect(ProbeResult::success('220 ok')->ok)->toBeTrue()
        ->and(ProbeResult::success('220 ok')->message)->toBe('220 ok')
        ->and(ProbeResult::failure('refused')->ok)->toBeFalse()
        ->and(ProbeResult::failure('refused')->message)->toBe('refused');
});
