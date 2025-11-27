<?php

namespace App\Imports;

use App\Models\Contact;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ContactsImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        // Skip empty rows
        if (!isset($row['phone']) || empty(trim($row['phone']))) {
            return null;
        }

        return new Contact([
            'user_id' => Auth::id(),
            'name' => $row['name'] ?? null,
            'phone' => $row['phone'],
            'email' => $row['email'] ?? null,
            'tags' => json_encode(explode(',', $row['tags'] ?? '')),
            'optin_status' => $row['optin_status'] ?? 'pending',
        ]);
    }
}
