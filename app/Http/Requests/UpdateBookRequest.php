<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBookRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
        'author' => ['required', 'string', 'max:255'],
        'isbn' => [
            'required',
            'digits:13',
            Rule::unique('books', 'isbn')->ignore($this->route('book')),
        ],
        'published_date' => ['required', 'date'],
        'description' => ['nullable', 'string'],
        'image_url' => ['nullable', 'url', 'max:255'],
        'genres' => ['required', 'array', 'min:1'],
        'genres.*' => ['exists:genres,id'],
        ];
    }
}
