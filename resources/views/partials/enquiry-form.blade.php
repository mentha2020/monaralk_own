@php($action = isset($vehicle) && $vehicle ? route('vehicles.enquiry', $vehicle) : route('contact.store'))
@php($account = auth()->user())
@php($fieldClass = 'block w-full rounded-xl border-slate-500 bg-white text-sm text-slate-900 placeholder:text-slate-400 focus:border-brand-600 focus:ring-brand-600 dark:border-slate-400 dark:bg-slate-900 dark:text-slate-200 dark:placeholder:text-slate-500')

@if (session('status'))
    <div role="status" class="mt-4 rounded-xl border border-brand-200 bg-brand-50 px-4 py-3 text-sm font-medium text-brand-800 dark:border-brand-900 dark:bg-brand-950 dark:text-brand-300">
        {{ session('status') }}
    </div>
@endif

<form method="POST" action="{{ $action }}" class="mt-4 space-y-3">
    @csrf

    <div class="hidden" aria-hidden="true">
        <label for="website">Leave this field empty</label>
        <input type="text" id="website" name="website" tabindex="-1" autocomplete="off" value="{{ old('website') }}">
    </div>

    <div>
        <label for="enquiry-name" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Name') }}</label>
        <input type="text" id="enquiry-name" name="name" value="{{ old('name', $account?->name) }}" required maxlength="255" autocomplete="name" class="{{ $fieldClass }}">
        @error('name')<p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="enquiry-email" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Email') }}</label>
        <input type="email" id="enquiry-email" name="email" value="{{ old('email', $account?->email) }}" required maxlength="255" autocomplete="email" class="{{ $fieldClass }}">
        @error('email')<p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="enquiry-phone" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Phone') }} <span class="font-normal normal-case tracking-normal">({{ __('optional') }})</span></label>
        <input type="tel" id="enquiry-phone" name="phone" value="{{ old('phone') }}" maxlength="40" autocomplete="tel" class="{{ $fieldClass }}">
        @error('phone')<p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="enquiry-message" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Message') }}</label>
        <textarea id="enquiry-message" name="message" rows="4" required maxlength="2000" class="{{ $fieldClass }}">{{ old('message') }}</textarea>
        @error('message')<p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
    </div>

    <button type="submit" class="inline-flex min-h-11 w-full items-center justify-center rounded-xl bg-brand-700 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-800 focus:outline-none focus:ring-2 focus:ring-brand-600 focus:ring-offset-2 dark:focus:ring-offset-slate-950">
        {{ isset($vehicle) && $vehicle ? __('Send enquiry') : __('Send message') }}
    </button>
</form>
