<?php

namespace App\Imports;

use App\Models\Contact;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ContactsImport implements ToModel, WithHeadingRow
{
    public $inserted = 0;
    public $duplicates = 0;

    protected $userId;

    public function __construct($userId)
    {
        $this->userId = $userId;
    }

    public function model(array $row)
    {
        // Skip empty rows
        if (empty($row['phone'])) {
            return null;
        }

        // Check duplicate (same user + phone)
        $exists = Contact::where('user_id', $this->userId)
            ->where('phone', $row['phone'])
            ->exists();

        if ($exists) {
            $this->duplicates++;
            return null; // skip this row
        }

        $this->inserted++;

        return new Contact([
            'user_id'      => $this->userId,
            'name'         => $row['name'] ?? null,
            'phone'        => $row['phone'],
            'email'        => $row['email'] ?? null,
            'tags'         => isset($row['tags'])
                                ? json_encode(array_map('trim', explode(',', $row['tags'])))
                                : json_encode([]),
            'optin_status' => $row['optin_status'] ?? 'pending',
            'last_message' => null,
            'last_seen_at' => null,
        ]);
    }
}
