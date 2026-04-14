<?php

use App\Models\Document;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
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

    public string $name = '';

    public string $path = '';

    public string $description = '';

    public string $content = '';

    public string $keywords = '';

    public function updatedQ(): void
    {
        $this->resetPage();
    }

    public function openCreateModal(): void
    {
        $this->resetForm();
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

        Document::create($validated);

        $this->resetForm();
        $this->showCreateModal = false;
        $this->resetPage();
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
            return Document::query()
                ->latest()
                ->paginate(perPage: 8);
        }

        return Document::search($query)->paginate(perPage: 8);
    }

    private function resetForm(): void
    {
        $this->reset(['name', 'path', 'description', 'content', 'keywords']);
        $this->resetValidation();
    }
};
?>

<div class="min-h-screen bg-slate-100 text-slate-900">
    <div class="mx-auto flex w-full max-w-6xl flex-col px-4 pb-12 pt-4 sm:px-6 lg:px-8">
        <header class="mb-6 flex items-center justify-between py-2">
            <div>
                <p class="text-sm text-slate-500">Local</p>
                <h1 class="text-xl font-semibold sm:text-2xl">Base de conocimiento</h1>
            </div>

            <button
                type="button"
                wire:click="openCreateModal"
                class="rounded-full bg-slate-900 px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-slate-700 focus:outline-none"
            >
                Nuevo documento
            </button>
        </header>

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
                        wire:model.live.debounce.300ms="q"
                        placeholder="Busca por nombre, descripción, contenido o keywords..."
                        class="w-full border-0 bg-transparent text-sm text-slate-700 placeholder:text-slate-400 focus:outline-none sm:text-base"
                    >
                </div>
            </div>
        </section>

        <section>
            <div wire:loading class="mb-4 text-sm text-slate-500">Buscando...</div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                @forelse ($this->documents as $document)
                    <article wire:key="document-{{ $document->id }}" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <div class="mb-3 flex items-start justify-between gap-2">
                            <h2 class="line-clamp-2 text-lg font-semibold">{{ $document->name }}</h2>
                            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs text-slate-600">#{{ $document->id }}</span>
                        </div>

                        <p class="mb-3 line-clamp-3 text-sm text-slate-600">{{ $document->description }}</p>
                        <p class="mb-4 line-clamp-3 text-sm text-slate-500">{{ $document->content }}</p>

                        <div class="mb-4 flex flex-wrap gap-2">
                            @foreach (collect(explode(',', $document->keywords))->filter() as $keyword)
                                <span wire:key="keyword-{{ $document->id }}-{{ md5($keyword) }}" class="rounded-full bg-sky-100 px-3 py-1 text-xs font-medium text-sky-700">
                                    {{ trim($keyword) }}
                                </span>
                            @endforeach
                        </div>

                        <div class="flex items-center justify-between gap-3">
                            <button
                                type="button"
                                wire:click="showDetail({{ $document->id }})"
                                class="rounded-full border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-100"
                            >
                                Ver detalle
                            </button>

                            <a
                                href="{{ $document->path }}"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="rounded-full bg-slate-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-slate-700"
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
        <div class="fixed inset-0 z-40 bg-slate-900/40 backdrop-blur-sm"></div>
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="w-full max-w-2xl rounded-3xl border border-slate-200 bg-white p-5 shadow-xl sm:p-6">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-lg font-semibold">Cargar documento</h3>
                    <button type="button" wire:click="closeCreateModal" class="rounded-full p-2 text-slate-500 hover:bg-slate-100">✕</button>
                </div>

                <form wire:submit="save" class="grid grid-cols-1 gap-4">
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
                        <textarea id="content" wire:model="content" rows="5" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none"></textarea>
                        @error('content') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="keywords" class="mb-1 block text-sm font-medium">Keywords (separadas por comas)</label>
                        <input id="keywords" type="text" wire:model="keywords" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none">
                        @error('keywords') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="mt-2 flex items-center justify-end gap-3">
                        <button type="button" wire:click="closeCreateModal" class="rounded-full border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">
                            Cancelar
                        </button>
                        <button type="submit" class="rounded-full bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700">
                            Guardar documento
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    @if ($showDetailModal && $selectedDocument)
        <div class="fixed inset-0 z-40 bg-slate-900/40 backdrop-blur-sm"></div>
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="w-full max-w-3xl rounded-3xl border border-slate-200 bg-white p-5 shadow-xl sm:p-6">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-lg font-semibold">{{ $selectedDocument->name }}</h3>
                    <button type="button" wire:click="closeDetail" class="rounded-full p-2 text-slate-500 hover:bg-slate-100">✕</button>
                </div>

                <div class="max-h-[60vh] space-y-4 overflow-y-auto pr-1">
                    <div>
                        <p class="mb-1 text-sm font-medium text-slate-700">Descripción</p>
                        <p class="text-sm text-slate-600">{{ $selectedDocument->description }}</p>
                    </div>

                    <div>
                        <p class="mb-1 text-sm font-medium text-slate-700">Contenido</p>
                        <p class="whitespace-pre-wrap text-sm text-slate-600">{{ $selectedDocument->content }}</p>
                    </div>

                    <div>
                        <p class="mb-1 text-sm font-medium text-slate-700">Ruta</p>
                        <p class="break-all text-xs text-slate-500">{{ $selectedDocument->path }}</p>
                    </div>
                </div>

                <div class="mt-5 flex justify-end">
                    <a href="{{ $selectedDocument->path }}" target="_blank" rel="noopener noreferrer" class="rounded-full bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700">
                        Abrir recurso
                    </a>
                </div>
            </div>
        </div>
    @endif
</div>