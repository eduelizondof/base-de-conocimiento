<?php

use App\Models\Document;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('knowledge base page loads and shows stored documents', function () {
    Document::query()->create([
        'name' => 'Informe de auditoria',
        'path' => 'https://drive.google.com/file/d/abc123/view',
        'description' => 'Documento de referencia para auditoria interna.',
        'content' => 'Contenido de ejemplo con hallazgos y controles.',
        'keywords' => 'auditoria,controles,finanzas',
    ]);

    $response = $this->get('/');

    $response
        ->assertOk()
        ->assertSee('Base de conocimiento')
        ->assertSee('Informe de auditoria');
});
