<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    protected $fillable = [
        'user_id',
        'phone',
        'step',
        'data',
    ];

    protected $casts = [
        'data' => 'array',
    ];

    // So these are available as $conv->requirement, $conv->name, etc.
    protected $appends = [
        'requirement',
        'name',
        'business_name',
        'contact_number',
        'email',
    ];

    protected function dataArray(): array
    {
        return $this->data ?? [];
    }

    /** Requirement summary (from “service” to primary goal / details) */
    public function getRequirementAttribute(): string
    {
        $data    = $this->dataArray();
        $service = $data['service'] ?? null;

        if (! $service) {
            return '-';
        }

        $parts = [];

        if ($service === 'website') {
            $parts[] = 'Website Development';
            if (!empty($data['website']['type'])) {
                $parts[] = $data['website']['type'];
            }
            if (!empty($data['website']['project_type'])) {
                $parts[] = $data['website']['project_type'];
            }
        } elseif ($service === 'mobile_app') {
            $parts[] = 'Mobile App Development';
            if (!empty($data['mobile_app']['platform'])) {
                $parts[] = $data['mobile_app']['platform'];
            }
            if (!empty($data['mobile_app']['purpose'])) {
                $parts[] = 'Purpose: '.$data['mobile_app']['purpose'];
            }
        } elseif ($service === 'digital_marketing') {
            $parts[] = 'Digital Marketing';
            if (!empty($data['digital_marketing']['service'])) {
                $parts[] = $data['digital_marketing']['service'];
            }
            if (!empty($data['digital_marketing']['goal'])) {
                // this is your “Primary goal” answer
                $parts[] = 'Goal: '.$data['digital_marketing']['goal'];
            }
        } elseif ($service === 'branding') {
            $parts[] = 'Branding & Creative Design';
            if (!empty($data['branding']['service'])) {
                $parts[] = $data['branding']['service'];
            }
            if (!empty($data['branding']['reference'])) {
                $parts[] = $data['branding']['reference'];
            }
        }

        return implode(' | ', $parts);
    }

    /** Contact name */
    public function getNameAttribute(): ?string
    {
        $data = $this->dataArray();
        return $data['contact']['name'] ?? null;
    }

    public function getBusinessNameAttribute(): ?string
    {
        $data = $this->dataArray();
        return $data['contact']['business_name'] ?? null;
    }

    public function getContactNumberAttribute(): ?string
    {
        $data = $this->dataArray();
        return $data['contact']['phone'] ?? null;
    }

    public function getEmailAttribute(): ?string
    {
        $data = $this->dataArray();
        return $data['contact']['email'] ?? null;
    }
}
