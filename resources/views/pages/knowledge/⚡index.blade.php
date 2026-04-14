<?php

use App\Models\Document;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public string $q = '';

    public bool $showCreateModal = false;

    public bool $showDetailModal = false;

    public ?Document $selectedDocument = null;

    public ?int $editingDocumentId = null;

    public bool $showNotification = false;

    public string $notificationType = 'success';

    public string $notificationMessage = '';

    public string $searchMode = 'default';

    public string $name = '';

    public string $path = '';

    public string $description = '';

    public string $content = '';

    public string $keywords = '';

    public function updatedQ(): void
    {
        $this->resetPage();
    }

    public function clearSearch(): void
    {
        $this->q = '';
        $this->resetPage();
    }

    public function openCreateModal(): void
    {
        $this->resetForm();
        $this->editingDocumentId = null;
        $this->showCreateModal = true;
    }

    public function closeCreateModal(): void
    {
        $this->showCreateModal = false;
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'path' => [
                'required',
                'string',
                'max:2048',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $isHttp = Str::startsWith($value, ['http://', 'https://']);
                    $isFile = Str::startsWith($value, 'file://');

                    if (! $isHttp && ! $isFile) {
                        $fail('The '.$attribute.' must start with http://, https:// or file://');
                    }
                },
            ],
            'description' => ['required', 'string'],
            'content' => ['required', 'string'],
            'keywords' => ['required', 'string', 'max:255'],
        ]);

        $validated['content'] = strip_tags(
            $validated['content'],
            '<b><strong><i><em><u><ul><ol><li><p><br>'
        );

        try {
            if ($this->editingDocumentId !== null) {
                $document = Document::query()->findOrFail($this->editingDocumentId);
                $document->update($validated);
                $this->notify('success', 'Documento actualizado correctamente.');
            } else {
                Document::create($validated);
                $this->notify('success', 'Documento creado correctamente.');
            }

            $this->resetForm();
            $this->showCreateModal = false;
            $this->editingDocumentId = null;
            $this->resetPage();
        } catch (\Throwable) {
            $this->notify('error', 'Ocurrió un error al guardar el documento.');
        }
    }

    public function openEditModal(int $documentId): void
    {
        $document = Document::query()->findOrFail($documentId);

        $this->editingDocumentId = $document->id;
        $this->name = $document->name;
        $this->path = $document->path;
        $this->description = $document->description;
        $this->content = $document->content;
        $this->keywords = $document->keywords;
        $this->resetValidation();
        $this->showCreateModal = true;
    }

    public function showDetail(int $documentId): void
    {
        $this->selectedDocument = Document::query()->findOrFail($documentId);
        $this->showDetailModal = true;
    }

    public function closeDetail(): void
    {
        $this->showDetailModal = false;
        $this->selectedDocument = null;
    }

    public function getDocumentsProperty(): LengthAwarePaginator
    {
        $query = trim($this->q);

        if ($query === '') {
            $this->searchMode = 'default';

            return Document::query()
                ->latest()
                ->paginate(perPage: 8);
        }

        $normalizedQuery = $this->normalizeSearchText($query);
        $scoutResults = Document::search($query)->paginate(perPage: 8);

        if ($scoutResults->total() > 0) {
            $this->searchMode = 'exact';

            return $scoutResults;
        }

        if ($normalizedQuery !== '' && $normalizedQuery !== mb_strtolower($query)) {
            $normalizedScoutResults = Document::search($normalizedQuery)->paginate(perPage: 8);

            if ($normalizedScoutResults->total() > 0) {
                $this->searchMode = 'normalized';

                return $normalizedScoutResults;
            }
        }

        $terms = $this->extractSearchTerms($normalizedQuery !== '' ? $normalizedQuery : $query);

        if ($terms === []) {
            $this->searchMode = 'none';

            return Document::query()->paginate(perPage: 8);
        }

        $fallbackResults = Document::query()
            ->where(function (Builder $queryBuilder) use ($terms): void {
                foreach ($terms as $term) {
                    $queryBuilder->where(function (Builder $tokenQuery) use ($term): void {
                        $this->applyTokenConstraint($tokenQuery, $term);
                    });
                }
            })
            ->latest()
            ->paginate(perPage: 8);

        $this->searchMode = 'partial';

        return $fallbackResults;
    }

    private function normalizeSearchText(string $text): string
    {
        $normalized = Str::lower(Str::ascii($text));
        $normalized = preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $normalized) ?? '';

        return trim(preg_replace('/\s+/u', ' ', $normalized) ?? '');
    }

    /**
     * @return array<int, string>
     */
    private function extractSearchTerms(string $query): array
    {
        return collect(explode(' ', $query))
            ->map(static fn (string $term): string => trim($term))
            ->filter(static fn (string $term): bool => mb_strlen($term) >= 2)
            ->values()
            ->all();
    }

    private function applyTokenConstraint(Builder $query, string $term): void
    {
        $columns = ['name', 'description', 'content', 'keywords'];
        $databaseDriver = Document::query()->getModel()->getConnection()->getDriverName();
        $searchTerm = '%'.$term.'%';

        foreach ($columns as $column) {
            if ($databaseDriver === 'mysql') {
                $query->orWhereRaw("LOWER({$column}) COLLATE utf8mb4_unicode_ci LIKE ?", [$searchTerm]);
            } else {
                $query->orWhere($column, 'like', $searchTerm);
            }
        }
    }

    private function resetForm(): void
    {
        $this->reset(['name', 'path', 'description', 'content', 'keywords']);
        $this->resetValidation();
    }

    public function notify(string $type, string $message): void
    {
        $this->notificationType = $type;
        $this->notificationMessage = $message;
        $this->showNotification = true;
    }

    public function clearNotification(): void
    {
        $this->showNotification = false;
    }

    public function render()
    {
        return $this->view()->layout('layouts::app', [
            'metaDescription' => 'Consulta y gestión de documentos institucionales de la Secretaría de Educación Jalisco (SEJ). Búsqueda por nombre, descripción, contenido y palabras clave alineada a procesos ISO.',
            'metaKeywords' => 'base de conocimiento, SEJ, Secretaría de Educación Jalisco, educación, ISO, documentos, procedimientos, Jalisco',
        ])->title('Base de Conocimiento - SEJ ISO');
    }
};
?>

<div class="min-h-screen bg-slate-100 text-slate-900">
    <header class="flex h-11 shrink-0 items-center justify-center bg-[#e9004c] px-4 shadow-sm">
        <a href="{{ url('/') }}" class="flex items-center py-1.5" wire:navigate>
            <img
                src="{{ asset('img/logo_blanco_educacion.svg') }}"
                alt="Secretaría de Educación Jalisco"
                width="200"
                height="51"
                class="h-7 w-auto max-w-[min(100vw-2rem,220px)] object-contain object-center"
                fetchpriority="high"
            >
        </a>
    </header>

    @if ($showNotification)
        <div
            x-data
            x-init="setTimeout(() => $wire.clearNotification(), 3000)"
            class="fixed right-4 top-14 z-60 rounded-2xl px-4 py-3 text-sm font-medium shadow-lg {{ $notificationType === 'success' ? 'bg-emerald-600 text-white' : 'bg-red-600 text-white' }}"
        >
            <div class="flex items-center gap-3">
                <span>{{ $notificationMessage }}</span>
                <button type="button" wire:click="clearNotification" class="rounded-full bg-white/15 px-2 py-0.5 text-xs hover:bg-white/25">Cerrar</button>
            </div>
        </div>
    @endif

    <div class="mx-auto flex w-full max-w-6xl flex-col px-4 pb-12 pt-6 sm:px-6 lg:px-8">
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h1 class="text-xl font-semibold tracking-tight text-slate-900 sm:text-2xl">Base de Conocimiento — SEJ ISO</h1>
                <p class="mt-1 max-w-2xl text-sm text-slate-600">
                    Consulta, busca y administra documentos y enlaces de referencia para procesos y normativa institucional.
                </p>
            </div>

            <button
                type="button"
                wire:click="openCreateModal"
                class="shrink-0 rounded-full bg-[#ff8300] px-4 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-[#e67600] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#ff8300]/45 focus-visible:ring-offset-2"
            >
                Nuevo documento
            </button>
        </div>

        <section class="mb-8 mt-4 flex flex-1 items-center justify-center">
            <div class="w-full max-w-2xl rounded-3xl border border-slate-200 bg-white p-3 shadow-lg">
                <label for="search" class="sr-only">Buscar documento</label>
                <div class="flex items-center gap-3">
                    <svg class="h-5 w-5 text-slate-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 1 0 3.47 9.768l2.63 2.63a.75.75 0 1 0 1.06-1.06l-2.63-2.63A5.5 5.5 0 0 0 9 3.5ZM5 9a4 4 0 1 1 8 0 4 4 0 0 1-8 0Z" clip-rule="evenodd" />
                    </svg>
                    <input
                        id="search"
                        type="text"
                        wire:model.live.debounce.500ms="q"
                        placeholder="Busca por nombre, descripción, contenido o keywords..."
                        class="w-full border-0 bg-transparent text-sm text-slate-700 placeholder:text-slate-400 focus:outline-none sm:text-base"
                    >

                    @if (trim($q) !== '')
                        <button
                            type="button"
                            wire:click="clearSearch"
                            class="rounded-full border border-slate-200 px-3 py-1 text-xs font-medium text-slate-600 transition hover:bg-slate-100"
                        >
                            Limpiar
                        </button>
                    @endif
                </div>
            </div>
        </section>

        <section>
            @if (trim($q) !== '')
                <div class="mb-3 flex items-center justify-between gap-3 rounded-xl bg-sky-50 px-3 py-2 text-sm text-sky-800">
                    <p>
                        Filtrando resultados por: <span class="font-semibold">"{{ $q }}"</span>
                        @if ($searchMode === 'partial')
                            <span class="ml-2 text-xs font-medium text-sky-700">(coincidencias parciales por palabras)</span>
                        @elseif ($searchMode === 'normalized')
                            <span class="ml-2 text-xs font-medium text-sky-700">(coincidencia normalizada sin acentos)</span>
                        @elseif ($searchMode === 'exact')
                            <span class="ml-2 text-xs font-medium text-sky-700">(coincidencia exacta Scout)</span>
                        @endif
                    </p>
                    <span class="text-xs text-sky-700">
                        {{ $this->documents->total() }} resultado(s)
                    </span>
                </div>
            @endif

            <div wire:loading wire:target="q" class="mb-4 text-sm text-slate-500">
                Aplicando filtro...
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                @forelse ($this->documents as $document)
                    <article wire:key="document-{{ $document->id }}" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <div class="mb-3 flex items-start justify-between gap-2">
                            <h2 class="line-clamp-2 text-lg font-semibold">{{ $document->name }}</h2>
                            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs text-slate-600">#{{ $document->id }}</span>
                        </div>

                        <p class="mb-3 line-clamp-3 text-sm text-slate-600">{{ $document->description }}</p>
                        <p class="mb-4 line-clamp-3 text-sm text-slate-500">
                            {{ Str::limit(strip_tags($document->content), 220) }}
                        </p>

                        <div class="mb-4 flex flex-wrap gap-2">
                            @foreach (collect(explode(',', $document->keywords))->filter() as $keyword)
                                <span wire:key="keyword-{{ $document->id }}-{{ md5($keyword) }}" class="rounded-full bg-sky-100 px-3 py-1 text-xs font-medium text-sky-700">
                                    {{ trim($keyword) }}
                                </span>
                            @endforeach
                        </div>

                        <div class="flex items-center justify-between gap-3">
                            <div class="flex items-center gap-2">
                                <button
                                    type="button"
                                    wire:click="showDetail({{ $document->id }})"
                                    class="rounded-full border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-100"
                                >
                                    Ver detalle
                                </button>
                                <button
                                    type="button"
                                    wire:click="openEditModal({{ $document->id }})"
                                    class="rounded-full border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-100"
                                >
                                    Editar
                                </button>
                            </div>

                            <a
                                href="{{ $document->path }}"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="rounded-full bg-[#ff8300] px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-[#e67600] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#ff8300]/45 focus-visible:ring-offset-2"
                            >
                                Abrir enlace
                            </a>
                        </div>
                    </article>
                @empty
                    <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-8 text-center text-slate-500 md:col-span-2">
                        No hay resultados con ese criterio.
                    </div>
                @endforelse
            </div>

            <div class="mt-6">
                {{ $this->documents->links() }}
            </div>
        </section>
    </div>

    @if ($showCreateModal)
        <div class="fixed inset-0 z-40 bg-slate-900/40 backdrop-blur-sm" wire:click="closeCreateModal"></div>
        <div class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4" wire:click="closeCreateModal">
            <div wire:click.stop class="flex h-[92vh] w-[96vw] max-w-7xl flex-col rounded-3xl border border-slate-200 bg-white p-5 shadow-xl sm:p-6">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-lg font-semibold">{{ $editingDocumentId ? 'Editar documento' : 'Cargar documento' }}</h3>
                    <button type="button" wire:click="closeCreateModal" class="rounded-full p-2 text-slate-500 hover:bg-slate-100">✕</button>
                </div>

                <form wire:submit="save" class="flex h-full flex-col overflow-hidden">
                    <div class="grid flex-1 grid-cols-1 gap-4 overflow-y-auto pr-1">
                        <div>
                        <label for="name" class="mb-1 block text-sm font-medium">Nombre</label>
                        <input id="name" type="text" wire:model="name" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none">
                        @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="path" class="mb-1 block text-sm font-medium">Ruta o enlace</label>
                        <input id="path" type="text" wire:model="path" placeholder="https://drive.google.com/... o file:///C:/Users/..." class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none">
                        @error('path') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="description" class="mb-1 block text-sm font-medium">Descripción</label>
                        <textarea id="description" wire:model="description" rows="3" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none"></textarea>
                        @error('description') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                        <div>
                        <label for="content" class="mb-1 block text-sm font-medium">Contenido clave</label>
                        <div
                            x-data="{
                                value: $wire.entangle('content'),
                                syncEditorFromState() {
                                    if (document.activeElement !== this.$refs.editor) {
                                        this.$refs.editor.innerHTML = this.value ?? '';
                                    }
                                }
                            }"
                            x-init="$nextTick(() => { $refs.editor.innerHTML = value ?? '' }); $watch('value', () => syncEditorFromState())"
                            class="overflow-hidden rounded-xl border border-slate-300"
                        >
                            <div class="flex flex-wrap items-center gap-2 border-b border-slate-200 bg-slate-50 p-2">
                                <button type="button" class="rounded-md border border-slate-300 bg-white px-2 py-1 text-xs hover:bg-slate-100" x-on:click="$refs.editor.focus(); document.execCommand('bold')">B</button>
                                <button type="button" class="rounded-md border border-slate-300 bg-white px-2 py-1 text-xs italic hover:bg-slate-100" x-on:click="$refs.editor.focus(); document.execCommand('italic')">I</button>
                                <button type="button" class="rounded-md border border-slate-300 bg-white px-2 py-1 text-xs underline hover:bg-slate-100" x-on:click="$refs.editor.focus(); document.execCommand('underline')">U</button>
                                <button type="button" class="rounded-md border border-slate-300 bg-white px-2 py-1 text-xs hover:bg-slate-100" x-on:click="$refs.editor.focus(); document.execCommand('insertUnorderedList')">Lista</button>
                                <button type="button" class="rounded-md border border-slate-300 bg-white px-2 py-1 text-xs hover:bg-slate-100" x-on:click="$refs.editor.focus(); document.execCommand('insertOrderedList')">1. 2. 3.</button>
                            </div>
                            <div
                                x-ref="editor"
                                contenteditable="true"
                                x-on:input="value = $refs.editor.innerHTML"
                                dir="ltr"
                                style="direction: ltr; unicode-bidi: isolate;"
                                class="min-h-[280px] w-full overflow-y-auto px-3 py-2 text-left text-sm focus:outline-none"
                            ></div>
                        </div>
                        @error('content') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="keywords" class="mb-1 block text-sm font-medium">Keywords (separadas por comas)</label>
                        <input id="keywords" type="text" wire:model="keywords" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none">
                        @error('keywords') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    </div>

                    <div class="mt-4 flex items-center justify-end gap-3 border-t border-slate-200 pt-4">
                        <button type="button" wire:click="closeCreateModal" class="rounded-full border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">
                            Cancelar
                        </button>
                        <button type="submit" class="rounded-full bg-[#ff8300] px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-[#e67600] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#ff8300]/45 focus-visible:ring-offset-2">
                            {{ $editingDocumentId ? 'Actualizar documento' : 'Guardar documento' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    @if ($showDetailModal && $selectedDocument)
        <div class="fixed inset-0 z-40 bg-slate-900/40 backdrop-blur-sm" wire:click="closeDetail"></div>
        <div class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4" wire:click="closeDetail">
            <div wire:click.stop class="flex h-[92vh] w-[96vw] max-w-7xl flex-col rounded-3xl border border-slate-200 bg-white p-5 shadow-xl sm:p-6">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-lg font-semibold">{{ $selectedDocument->name }}</h3>
                    <button type="button" wire:click="closeDetail" class="rounded-full p-2 text-slate-500 hover:bg-slate-100">✕</button>
                </div>

                <div class="flex-1 space-y-4 overflow-y-auto pr-1">
                    <div>
                        <p class="mb-1 text-sm font-medium text-slate-700">Descripción</p>
                        <p class="text-sm text-slate-600">{{ $selectedDocument->description }}</p>
                    </div>

                    <div>
                        <p class="mb-1 text-sm font-medium text-slate-700">Contenido</p>
                        <div class="prose prose-sm max-w-none text-slate-600">
                            {!! $selectedDocument->content !!}
                        </div>
                    </div>

                    <div>
                        <p class="mb-1 text-sm font-medium text-slate-700">Ruta</p>
                        <p class="break-all text-xs text-slate-500">{{ $selectedDocument->path }}</p>
                    </div>

                    <div>
                        <p class="mb-2 text-sm font-medium text-slate-700">Palabras clave</p>
                        <div class="flex flex-wrap gap-2">
                            @foreach (collect(explode(',', $selectedDocument->keywords))->filter() as $keyword)
                                <span wire:key="detail-keyword-{{ $selectedDocument->id }}-{{ md5($keyword) }}" class="rounded-full bg-sky-100 px-3 py-1 text-xs font-medium text-sky-700">
                                    {{ trim($keyword) }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="mt-5 flex justify-end">
                    <a href="{{ $selectedDocument->path }}" target="_blank" rel="noopener noreferrer" class="rounded-full bg-[#ff8300] px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-[#e67600] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#ff8300]/45 focus-visible:ring-offset-2">
                        Abrir recurso
                    </a>
                </div>
            </div>
        </div>
    @endif
</div>