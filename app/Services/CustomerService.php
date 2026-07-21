<?php

namespace App\Services;

use App\Models\Customer;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CustomerService
{
    public function create(array $data): Customer
    {
        $data = $this->normalize($data);
        $this->ensureUniqueDocument($data['document_number'] ?? null);

        return DB::transaction(fn (): Customer => Customer::create($data));
    }

    public function update(Customer $customer, array $data): Customer
    {
        $data = $this->normalize($data);
        $this->ensureUniqueDocument($data['document_number'] ?? null, $customer);

        return DB::transaction(function () use ($customer, $data): Customer {
            $customer->update($data);

            return $customer->fresh();
        });
    }

    public function toggle(Customer $customer): Customer
    {
        return DB::transaction(function () use ($customer): Customer {
            $customer->update(['is_active' => ! $customer->is_active]);

            return $customer->fresh();
        });
    }

    public function delete(Customer $customer): void
    {
        DB::transaction(fn (): bool => $customer->delete());
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalize(array $data): array
    {
        foreach (['name', 'document_type', 'document_number', 'phone', 'email', 'address'] as $field) {
            if (array_key_exists($field, $data) && is_string($data[$field])) {
                $data[$field] = trim($data[$field]);
            }
        }

        if (is_string($data['email'] ?? null)) {
            $data['email'] = strtolower($data['email']);
        }

        if (($data['document_number'] ?? null) === '') {
            $data['document_number'] = null;
        }

        if (($data['document_type'] ?? null) === '') {
            $data['document_type'] = null;
        }

        if (($data['credit_limit'] ?? null) === '' || ($data['credit_limit'] ?? null) === null) {
            $data['credit_limit'] = 0;
        }

        return $data;
    }

    private function ensureUniqueDocument(?string $documentNumber, ?Customer $ignore = null): void
    {
        if ($documentNumber === null) {
            return;
        }

        $exists = Customer::withTrashed()
            ->where('document_number', $documentNumber)
            ->when($ignore, fn ($query) => $query->where($ignore->getKeyName(), '!=', $ignore->getKey()))
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'documentNumber' => 'El número de documento ya está registrado.',
            ]);
        }
    }
}
