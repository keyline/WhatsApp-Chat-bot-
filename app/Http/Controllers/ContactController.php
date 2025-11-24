<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;  
class ContactController extends Controller
{
    public function index()
    {
        $userId = Auth::id();

        $contacts = Contact::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get();

        // segment stats
        $stats = [
            'total'        => $contacts->count(),
            'opted_in'     => $contacts->where('optin_status', 'opted_in')->count(),
            'pending'      => $contacts->where('optin_status', 'pending')->count(),
            'opted_out'    => $contacts->where('optin_status', 'opted_out')->count(),
            // optional tag-based segments
            'high_value'   => $contacts->filter(function ($c) {
                return is_array($c->tags) && in_array('high_value', $c->tags);
            })->count(),
            'real_estate'  => $contacts->filter(function ($c) {
                return is_array($c->tags) && in_array('real_estate', $c->tags);
            })->count(),
        ];

        return view('contacts.index', compact('contacts', 'stats'));
    }

        public function store(Request $request)
    {
        // Validate incoming data
        $validated = $request->validate([
            'name'         => 'nullable|string|max:255',
            'phone'        => 'required|string|max:20|unique:contacts,phone',
            'email'        => 'nullable|email|max:255',
            'tags'         => 'nullable|string',
            'optin_status' => 'required|string|in:opted_in,pending,opted_out',
        ]);

        // Convert tags from string → JSON array
        $tagsArray = $validated['tags']
            ? array_map('trim', explode(',', $validated['tags']))
            : [];

        // Save contact
        Contact::create([
            'user_id'      => Auth::id(),
            'name'         => $validated['name'] ?? null,
            'phone'        => $validated['phone'],
            'email'        => $validated['email'] ?? null,
            'tags'         => json_encode($tagsArray), // stored as JSON
            'optin_status' => $validated['optin_status'],
            'last_message' => null,
            'last_seen_at' => null,
        ]);

        return redirect()
            ->route('contacts')
            ->with('success', 'Contact added successfully.');
    }
}
