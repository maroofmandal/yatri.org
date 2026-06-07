<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * The destinations field can arrive as a JSON string (built by the form JS).
     */
    protected function prepareForValidation(): void
    {
        $dests = $this->input('destinations');

        if (is_string($dests)) {
            $this->merge(['destinations' => json_decode($dests, true) ?: []]);
        }
    }

    public function rules(): array
    {
        return [
            'origin'                => ['required', 'string', 'max:120'],
            'destinations'          => ['required', 'array', 'min:1', 'max:12'],
            'destinations.*.name'   => ['required', 'string', 'max:120'],
            'destinations.*.nights' => ['nullable', 'integer', 'min:1', 'max:60'],
            'destinations.*.lat'    => ['nullable', 'numeric'],
            'destinations.*.lng'    => ['nullable', 'numeric'],
            'start_date'            => ['nullable', 'date'],
            'end_date'              => ['nullable', 'date', 'after_or_equal:start_date'],
            'travelers'             => ['required', 'integer', 'min:1', 'max:30'],
            'budget_total'          => ['required', 'numeric', 'min:0', 'max:100000000'],
            'currency'              => ['required', 'string', 'size:3'],
            'style'                 => ['required', 'in:budget,mid,luxury'],
            'interests'             => ['nullable', 'array'],
            'interests.*'           => ['string', 'max:40'],
        ];
    }
}
