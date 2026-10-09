@props(['vehicle', 'variant' => 'card'])

@php
    $tel = $vehicle->telHref;
    $whatsapp = $vehicle->whatsappUrl;
    $shareUrl = route('vehicles.show', $vehicle);
    $callLabel = $vehicle->phone_display ?: __('Call');

    $wrapperClass = $variant === 'hero'
        ? 'mt-4'
        : 'border-t border-slate-100 p-3 dark:border-slate-800';
@endphp

@if ($tel !== '' || $whatsapp !== '')
    <div {{ $attributes->merge(['class' => $wrapperClass]) }} data-contact-actions="{{ $variant }}">
        <div class="flex flex-wrap gap-2">
            @if ($tel !== '')
                <a href="{{ $tel }}"
                   class="inline-flex min-h-11 flex-1 items-center justify-center gap-2 whitespace-nowrap rounded-xl bg-brand-700 px-4 text-sm font-semibold text-white transition hover:bg-brand-800 focus:outline-none focus:ring-2 focus:ring-brand-600 focus:ring-offset-2 dark:focus:ring-offset-slate-900">
                    <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z" />
                    </svg>
                    <span>{{ $callLabel }}</span>
                </a>
            @endif

            @if ($whatsapp !== '')
                <a href="{{ $whatsapp }}"
                   target="_blank"
                   rel="noopener noreferrer nofollow"
                   class="inline-flex min-h-11 flex-1 items-center justify-center gap-2 whitespace-nowrap rounded-xl border-2 border-slate-900 px-4 text-sm font-semibold text-slate-900 transition hover:bg-slate-900 hover:text-white focus:outline-none focus:ring-2 focus:ring-brand-600 focus:ring-offset-2 dark:border-slate-100 dark:text-slate-100 dark:hover:bg-slate-100 dark:hover:text-slate-900 dark:focus:ring-offset-slate-950">
                    <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a5.969 5.969 0 0 1-.474-.065 4.48 4.48 0 0 0 .978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25Z" />
                    </svg>
                    <span>{{ __('WhatsApp') }}</span>
                </a>
            @endif

            <div x-data="{ open: false, copied: false, async copyLink() { const anchor = this.$refs.shareUrl; const url = anchor ? anchor.getAttribute('href') : ''; try { if (navigator.clipboard && window.isSecureContext) { await navigator.clipboard.writeText(url); } else { const field = document.createElement('textarea'); field.value = url; field.setAttribute('readonly', 'readonly'); field.style.position = 'fixed'; field.style.opacity = '0'; document.body.appendChild(field); field.select(); document.execCommand('copy'); document.body.removeChild(field); } } catch (error) {} this.open = false; this.copied = true; window.clearTimeout(this.timer); this.timer = window.setTimeout(() => { this.copied = false; }, 2500); } }"
                 class="relative">
                <a x-ref="shareUrl" href="{{ $shareUrl }}" class="hidden" aria-hidden="true" tabindex="-1"></a>

                <button
                    type="button"
                    x-on:click="open = ! open"
                    x-on:click.outside="open = false"
                    x-on:keydown.escape.window="open = false"
                    :aria-expanded="open.toString()"
                    aria-haspopup="true"
                    class="inline-flex min-h-11 flex-1 items-center justify-center gap-2 whitespace-nowrap rounded-xl border border-slate-300 px-4 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-brand-600 focus:ring-offset-2 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-white/5 dark:focus:ring-offset-slate-950">
                    <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M7.217 10.907a2.25 2.25 0 1 0 0 2.186m0-2.186c.18.324.283.696.283 1.093s-.103.77-.283 1.093m0-2.186 9.566-5.314m-9.566 7.5 9.566 5.314m0 0a2.25 2.25 0 1 0 3.935 2.186 2.25 2.25 0 0 0-3.935-2.186Zm0-12.814a2.25 2.25 0 1 0 3.933-2.185 2.25 2.25 0 0 0-3.933 2.185Z" />
                    </svg>
                    <span>{{ __('Share') }}</span>
                </button>

                <div x-show="open"
                     x-cloak
                     x-transition.origin.top.right
                     class="absolute right-0 z-30 mt-2 w-48 rounded-xl border border-slate-200 bg-white p-2 shadow-lg dark:border-slate-700 dark:bg-slate-900">
                    <a href="https://www.facebook.com/sharer/sharer.php?u={{ rawurlencode($shareUrl) }}"
                       target="_blank"
                       rel="noopener noreferrer nofollow"
                       class="flex min-h-11 items-center gap-2 rounded-lg px-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-white/5">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878v-6.987H7.897V12h2.541V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.891h-2.33v6.987C18.343 21.128 22 16.991 22 12Z" clip-rule="evenodd" /></svg>
                        <span>{{ __('Facebook') }}</span>
                    </a>

                    <button type="button"
                            x-on:click="copyLink()"
                            class="flex min-h-11 w-full items-center gap-2 rounded-lg px-3 text-left text-sm font-semibold text-slate-700 transition hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-white/5">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.666 3.888A2.25 2.25 0 0 0 13.5 2.25h-3c-1.03 0-1.9.693-2.166 1.638m7.332 0c.055.194.084.4.084.612v0a.75.75 0 0 1-.75.75H9a.75.75 0 0 1-.75-.75v0c0-.212.03-.418.084-.612m7.332 0c.646.049 1.288.11 1.927.184 1.1.128 1.907 1.077 1.907 2.185V19.5a2.25 2.25 0 0 1-2.25 2.25H6.75A2.25 2.25 0 0 1 4.5 19.5V6.257c0-1.108.806-2.057 1.907-2.185a48.208 48.208 0 0 1 1.927-.184" />
                        </svg>
                        <span>{{ __('Copy link') }}</span>
                    </button>
                </div>

                <div x-show="copied"
                     x-cloak
                     x-transition.opacity.duration.200ms
                     role="status"
                     class="fixed bottom-6 left-1/2 z-50 -translate-x-1/2 rounded-xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white shadow-lg dark:bg-white dark:text-slate-900">
                    {{ __('Link copied') }}
                </div>
            </div>
        </div>
    </div>
@endif
