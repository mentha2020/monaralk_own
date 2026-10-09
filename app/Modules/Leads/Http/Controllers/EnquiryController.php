<?php

namespace App\Modules\Leads\Http\Controllers;

use App\Http\Requests\StoreEnquiryRequest;
use App\Modules\Catalog\Models\Vehicle;
use App\Modules\Leads\Enums\EnquiryStatus;
use App\Modules\Leads\Models\Enquiry;
use App\Modules\Leads\Notifications\EnquiryReceived;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Notification;
use Illuminate\View\View;

class EnquiryController
{
    public function create(): View
    {
        return view('contact.create');
    }

    public function store(StoreEnquiryRequest $request): RedirectResponse
    {
        return $this->persist($request, null);
    }

    public function storeForVehicle(StoreEnquiryRequest $request, Vehicle $vehicle): RedirectResponse
    {
        return $this->persist($request, $vehicle);
    }

    private function persist(StoreEnquiryRequest $request, ?Vehicle $vehicle): RedirectResponse
    {
        $redirect = $vehicle === null
            ? redirect()->route('contact')
            : redirect()->route('vehicles.show', $vehicle);

        if ($request->filled('website')) {
            return $redirect->with('status', $this->thanks());
        }

        $data = $request->validated();

        $enquiry = Enquiry::create([
            'vehicle_id' => $vehicle?->getKey(),
            'user_id' => $request->user()?->getKey(),
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'message' => $data['message'],
            'status' => EnquiryStatus::New,
            'ip' => $request->ip(),
        ]);

        Notification::send(EnquiryReceived::recipients(), new EnquiryReceived($enquiry));

        return $redirect->with('status', $this->thanks());
    }

    private function thanks(): string
    {
        return __('Thanks — your message has been sent. We usually reply within one working day.');
    }
}
