@php
if (! isset($scrollTo)) {
    $scrollTo = 'body';
}

$scrollIntoViewJsSnippet = ($scrollTo !== false)
    ? <<<JS
       (\$el.closest('{$scrollTo}') || document.querySelector('{$scrollTo}')).scrollIntoView()
    JS
    : '';
@endphp

<div>
    @if ($paginator->hasPages())
        <nav role="navigation" aria-label="Pagination Navigation" class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex flex-1 justify-between sm:hidden">
                <span>
                    @if ($paginator->onFirstPage())
                        <span class="relative inline-flex cursor-default items-center rounded-full border border-slate-200 bg-slate-50 px-4 py-2 text-sm font-medium leading-5 text-slate-400">
                            {!! __('pagination.previous') !!}
                        </span>
                    @else
                        <button type="button" wire:click="previousPage('{{ $paginator->getPageName() }}')" x-on:click="{{ $scrollIntoViewJsSnippet }}" wire:loading.attr="disabled" dusk="previousPage{{ $paginator->getPageName() == 'page' ? '' : '.' . $paginator->getPageName() }}.before" class="relative inline-flex items-center rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-medium leading-5 text-slate-700 shadow-sm transition hover:border-slate-300 hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-[#ff8300]/30 focus-visible:ring-offset-2 active:bg-slate-100">
                            {!! __('pagination.previous') !!}
                        </button>
                    @endif
                </span>

                <span>
                    @if ($paginator->hasMorePages())
                        <button type="button" wire:click="nextPage('{{ $paginator->getPageName() }}')" x-on:click="{{ $scrollIntoViewJsSnippet }}" wire:loading.attr="disabled" dusk="nextPage{{ $paginator->getPageName() == 'page' ? '' : '.' . $paginator->getPageName() }}.before" class="relative ml-3 inline-flex items-center rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-medium leading-5 text-slate-700 shadow-sm transition hover:border-slate-300 hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-[#ff8300]/30 focus-visible:ring-offset-2 active:bg-slate-100">
                            {!! __('pagination.next') !!}
                        </button>
                    @else
                        <span class="relative ml-3 inline-flex cursor-default items-center rounded-full border border-slate-200 bg-slate-50 px-4 py-2 text-sm font-medium leading-5 text-slate-400">
                            {!! __('pagination.next') !!}
                        </span>
                    @endif
                </span>
            </div>

            <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
                <div>
                    <p class="text-sm leading-5 text-slate-600">
                        <span>{!! __('Showing') !!}</span>
                        <span class="font-medium text-slate-800">{{ $paginator->firstItem() }}</span>
                        <span>{!! __('to') !!}</span>
                        <span class="font-medium text-slate-800">{{ $paginator->lastItem() }}</span>
                        <span>{!! __('of') !!}</span>
                        <span class="font-medium text-slate-800">{{ $paginator->total() }}</span>
                        <span>{!! __('results') !!}</span>
                    </p>
                </div>

                <div>
                    <span class="relative z-0 inline-flex overflow-hidden rounded-2xl border border-slate-200/90 bg-white p-1 shadow-sm rtl:flex-row-reverse">
                        <span>
                            @if ($paginator->onFirstPage())
                                <span aria-disabled="true" aria-label="{{ __('pagination.previous') }}">
                                    <span class="relative inline-flex cursor-default items-center rounded-xl px-2.5 py-2 text-slate-300" aria-hidden="true">
                                        <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                                        </svg>
                                    </span>
                                </span>
                            @else
                                <button type="button" wire:click="previousPage('{{ $paginator->getPageName() }}')" x-on:click="{{ $scrollIntoViewJsSnippet }}" dusk="previousPage{{ $paginator->getPageName() == 'page' ? '' : '.' . $paginator->getPageName() }}.after" class="relative inline-flex items-center rounded-xl px-2.5 py-2 text-slate-600 transition hover:bg-slate-50 hover:text-slate-900 focus:z-10 focus:outline-none focus-visible:ring-2 focus-visible:ring-[#ff8300]/35 focus-visible:ring-offset-1" aria-label="{{ __('pagination.previous') }}">
                                    <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                            @endif
                        </span>

                        @foreach ($elements as $element)
                            @if (is_string($element))
                                <span aria-disabled="true">
                                    <span class="relative -ml-px inline-flex cursor-default items-center px-3 py-2 text-sm font-medium text-slate-400">{{ $element }}</span>
                                </span>
                            @endif

                            @if (is_array($element))
                                @foreach ($element as $page => $url)
                                    <span wire:key="paginator-{{ $paginator->getPageName() }}-page{{ $page }}">
                                        @if ($page == $paginator->currentPage())
                                            <span aria-current="page">
                                                <span class="relative -ml-px inline-flex min-w-[2.5rem] items-center justify-center rounded-xl bg-[#ff8300] px-3 py-2 text-sm font-semibold text-white shadow-sm">{{ $page }}</span>
                                            </span>
                                        @else
                                            <button type="button" wire:click="gotoPage({{ $page }}, '{{ $paginator->getPageName() }}')" x-on:click="{{ $scrollIntoViewJsSnippet }}" class="relative -ml-px inline-flex min-w-[2.5rem] items-center justify-center rounded-xl px-3 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-100 hover:text-slate-900 focus:z-10 focus:outline-none focus-visible:ring-2 focus-visible:ring-[#ff8300]/35 focus-visible:ring-offset-1" aria-label="{{ __('Go to page :page', ['page' => $page]) }}">
                                                {{ $page }}
                                            </button>
                                        @endif
                                    </span>
                                @endforeach
                            @endif
                        @endforeach

                        <span>
                            @if ($paginator->hasMorePages())
                                <button type="button" wire:click="nextPage('{{ $paginator->getPageName() }}')" x-on:click="{{ $scrollIntoViewJsSnippet }}" dusk="nextPage{{ $paginator->getPageName() == 'page' ? '' : '.' . $paginator->getPageName() }}.after" class="relative -ml-px inline-flex items-center rounded-xl px-2.5 py-2 text-slate-600 transition hover:bg-slate-50 hover:text-slate-900 focus:z-10 focus:outline-none focus-visible:ring-2 focus-visible:ring-[#ff8300]/35 focus-visible:ring-offset-1" aria-label="{{ __('pagination.next') }}">
                                    <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                            @else
                                <span aria-disabled="true" aria-label="{{ __('pagination.next') }}">
                                    <span class="relative -ml-px inline-flex cursor-default items-center rounded-xl px-2.5 py-2 text-slate-300" aria-hidden="true">
                                        <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                                        </svg>
                                    </span>
                                </span>
                            @endif
                        </span>
                    </span>
                </div>
            </div>
        </nav>
    @endif
</div>
