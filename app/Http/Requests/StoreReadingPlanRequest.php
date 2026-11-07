<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreReadingPlanRequest extends FormRequest
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
             'book_id' => ['required', 'integer', 'exists:books,id'],
             'target_date' => ['required', 'date', 'after_or_equal:today'],
         ];
    }

    public function messages(): array
    {
        return [
            'book_id.required' => '書籍を選択してください。',
            'book_id.integer' => '書籍の指定が正しくありません。',
            'book_id.exists' => '選択された書籍は存在しません。',
            'target_date.required' => '期日を入力してください。',
            'target_date.date' => '期日は正しい日付を入力してください。',
            'target_date.after_or_equal' => '期日は今日以降の日付を指定してください。',
        ];
    }
}
